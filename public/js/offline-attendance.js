/**
 * IIIS Academic ERP - Offline Attendance Engine (IndexedDB + Auto-Sync)
 * محرك رصد الحضور بدون اتصال والمزامنة الذكية عبر IndexedDB
 */

const IIIS_OFFLINE = {
    DB_NAME: 'IIIS_Offline_DB',
    DB_VERSION: 1,
    STORE_NAME: 'attendance_queue',
    db: null,

    // Open or create IndexedDB instance
    async initDB() {
        if (this.db) return this.db;
        return new Promise((resolve, reject) => {
            const request = indexedDB.open(this.DB_NAME, this.DB_VERSION);
            request.onupgradeneeded = (event) => {
                const db = event.target.result;
                if (!db.objectStoreNames.contains(this.STORE_NAME)) {
                    const store = db.createObjectStore(this.STORE_NAME, { keyPath: 'client_uuid' });
                    store.createIndex('sync_status', 'sync_status', { unique: false });
                    store.createIndex('client_recorded_at', 'client_recorded_at', { unique: false });
                }
            };
            request.onsuccess = (event) => {
                this.db = event.target.result;
                resolve(this.db);
            };
            request.onerror = (event) => {
                console.error('[IndexedDB] Failed to open DB:', event.target.error);
                reject(event.target.error);
            };
        });
    },

    // Generate standard UUIDv4
    generateUUID() {
        if (typeof crypto !== 'undefined' && crypto.randomUUID) {
            return crypto.randomUUID();
        }
        return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
            const r = Math.random() * 16 | 0, v = c === 'x' ? r : (r & 0x3 | 0x8);
            return v.toString(16);
        });
    },

    // Save record to local queue
    async queueRecord(record) {
        await this.initDB();
        const clientUuid = record.client_uuid || this.generateUUID();
        const item = {
            client_uuid: clientUuid,
            sync_nonce: clientUuid,
            student_id: record.student_id,
            status: record.status || 'PRESENT',
            late_minutes: record.late_minutes || 0,
            departure_status: record.departure_status || 'NOT_DEPARTED',
            check_in_time: record.check_in_time || null,
            check_out_time: record.check_out_time || null,
            departure_reason: record.departure_reason || null,
            absence_reason: record.absence_reason || null,
            record_date: record.record_date || new Date().toISOString().split('T')[0],
            client_recorded_at: record.client_recorded_at || new Date().toISOString(),
            verification_method: record.verification_method || 'OFFLINE_MANUAL',
            sync_status: 'PENDING',
        };

        return new Promise((resolve, reject) => {
            const tx = this.db.transaction([this.STORE_NAME], 'readwrite');
            const store = tx.objectStore(this.STORE_NAME);
            const req = store.put(item);
            req.onsuccess = () => resolve(item);
            req.onerror = (e) => reject(e.target.error);
        });
    },

    // Save multiple records at once
    async queueBatch(records, recordDate = null) {
        await this.initDB();
        const queuedItems = [];
        const dateStr = recordDate || new Date().toISOString().split('T')[0];

        return new Promise((resolve, reject) => {
            const tx = this.db.transaction([this.STORE_NAME], 'readwrite');
            const store = tx.objectStore(this.STORE_NAME);

            records.forEach(rec => {
                const clientUuid = rec.client_uuid || this.generateUUID();
                const item = {
                    client_uuid: clientUuid,
                    sync_nonce: clientUuid,
                    student_id: rec.student_id,
                    status: rec.status || 'PRESENT',
                    late_minutes: rec.late_minutes || 0,
                    departure_status: rec.departure_status || 'NOT_DEPARTED',
                    check_in_time: rec.check_in_time || null,
                    check_out_time: rec.check_out_time || null,
                    departure_reason: rec.departure_reason || null,
                    absence_reason: rec.absence_reason || null,
                    record_date: rec.record_date || dateStr,
                    client_recorded_at: rec.client_recorded_at || new Date().toISOString(),
                    verification_method: rec.verification_method || 'OFFLINE_MANUAL',
                    sync_status: 'PENDING',
                };
                store.put(item);
                queuedItems.push(item);
            });

            tx.oncomplete = () => resolve(queuedItems);
            tx.onerror = (e) => reject(e.target.error);
        });
    },

    // Get all pending records
    async getPendingRecords() {
        await this.initDB();
        return new Promise((resolve, reject) => {
            const tx = this.db.transaction([this.STORE_NAME], 'readonly');
            const store = tx.objectStore(this.STORE_NAME);
            const req = store.getAll();
            req.onsuccess = () => {
                const pending = (req.result || []).filter(item => item.sync_status === 'PENDING');
                resolve(pending);
            };
            req.onerror = (e) => reject(e.target.error);
        });
    },

    // Count pending records
    async getPendingCount() {
        const records = await this.getPendingRecords();
        return records.length;
    },

    // Clear or mark synced records
    async removeRecords(uuids) {
        await this.initDB();
        return new Promise((resolve, reject) => {
            const tx = this.db.transaction([this.STORE_NAME], 'readwrite');
            const store = tx.objectStore(this.STORE_NAME);
            uuids.forEach(uuid => store.delete(uuid));
            tx.oncomplete = () => resolve(true);
            tx.onerror = (e) => reject(e.target.error);
        });
    },

    // Push pending queue to backend sync endpoint
    async syncQueue(csrfToken = '') {
        const pending = await this.getPendingRecords();
        if (pending.length === 0) {
            return { success: true, count: 0, message: 'لا توجد حركات معلقة للمزامنة' };
        }

        const batchId = this.generateUUID();
        const payload = {
            batch_id: batchId,
            device_uuid: localStorage.getItem('iiis_device_uuid') || (function() {
                const id = IIIS_OFFLINE.generateUUID();
                localStorage.setItem('iiis_device_uuid', id);
                return id;
            })(),
            records: pending,
        };

        const res = await fetch('/api/v1/attendance/sync', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                'X-Idempotency-Key': batchId,
            },
            body: JSON.stringify(payload)
        });

        const data = await res.json();
        if (data.success) {
            // Delete successfully synced records from IndexedDB
            const syncedUuids = pending.map(p => p.client_uuid);
            await this.removeRecords(syncedUuids);
            return {
                success: true,
                count: pending.length,
                synced: data.data?.synced ?? pending.length,
                duplicates: data.data?.duplicates ?? 0,
                conflicts: data.data?.conflicts ?? 0,
                message: data.message || 'تمت مزامنة الحركات المعلقة بنجاح'
            };
        } else {
            throw new Error(data.message || 'فشلت المزامنة مع الخادم الرئيسي');
        }
    }
};

// Register Service Worker if supported
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js')
            .then(reg => console.log('[SW] Service Worker registered with scope:', reg.scope))
            .catch(err => console.warn('[SW] Registration failed:', err));
    });
}

// Auto-sync when network reconnects
window.addEventListener('online', async () => {
    console.log('[Offline Sync] Device is back online. Initiating auto-sync...');
    try {
        const count = await IIIS_OFFLINE.getPendingCount();
        if (count > 0) {
            const result = await IIIS_OFFLINE.syncQueue();
            if (window.dispatchEvent) {
                window.dispatchEvent(new CustomEvent('iiis:offline-synced', { detail: result }));
            }
        }
    } catch (e) {
        console.warn('[Offline Sync] Auto-sync attempt deferred:', e);
    }
});

window.IIIS_OFFLINE = IIIS_OFFLINE;
