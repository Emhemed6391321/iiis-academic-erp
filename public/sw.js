/**
 * IIIS Academic ERP - Service Worker (Offline-First Attendance & Core Assets Cache)
 * منظومة المعهد التخصصي للدراسات الإسلامية - عامل الخدمة للعمل دون اتصال
 */

const CACHE_NAME = 'iiis-academic-v1.2';
const STATIC_ASSETS = [
    '/',
    '/favicon.ico',
    '/js/alpine.min.js',
    '/js/tailwind.min.js',
    '/js/alpine-collapse.min.js',
];

// Install Event - Precache critical assets
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            return cache.addAll(STATIC_ASSETS).catch((err) => {
                console.warn('[SW] Precache partial warning:', err);
            });
        }).then(() => self.skipWaiting())
    );
});

// Activate Event - Clean up stale caches
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) => {
            return Promise.all(
                keys.filter((key) => key !== CACHE_NAME).map((key) => caches.delete(key))
            );
        }).then(() => self.clients.claim())
    );
});

// Fetch Event - NetworkFirst for API attendance sheet, CacheFirst for static assets
self.addEventListener('fetch', (event) => {
    const request = event.request;
    const url = new URL(request.url);

    // Skip non-GET requests
    if (request.method !== 'GET') {
        return;
    }

    // 1. Attendance Sheet API - Network First, fallback to cached response if offline
    if (url.pathname.includes('/api/v1/attendance/sheet')) {
        event.respondWith(
            fetch(request)
                .then((networkResponse) => {
                    if (networkResponse && networkResponse.status === 200) {
                        const responseClone = networkResponse.clone();
                        caches.open(CACHE_NAME).then((cache) => {
                            cache.put(request, responseClone);
                        });
                    }
                    return networkResponse;
                })
                .catch(() => {
                    return caches.match(request).then((cachedResponse) => {
                        if (cachedResponse) {
                            return cachedResponse;
                        }
                        return new Response(JSON.stringify({
                            success: false,
                            offline: true,
                            message: 'أنت تعمل حالياً بدون اتصال بالإنترنت. يتم استخدام البيانات المخزنة محلياً.',
                            students: []
                        }), {
                            headers: { 'Content-Type': 'application/json' }
                        });
                    });
                })
        );
        return;
    }

    // 2. Static Assets (JS, CSS, Fonts, Images) - Cache First, fallback to Network
    if (
        url.pathname.startsWith('/js/') ||
        url.pathname.startsWith('/images/') ||
        url.pathname.endsWith('.js') ||
        url.pathname.endsWith('.css') ||
        url.pathname.endsWith('.woff2') ||
        url.pathname.endsWith('.ttf')
    ) {
        event.respondWith(
            caches.match(request).then((cachedResponse) => {
                if (cachedResponse) {
                    return cachedResponse;
                }
                return fetch(request).then((networkResponse) => {
                    if (networkResponse && networkResponse.status === 200) {
                        const responseClone = networkResponse.clone();
                        caches.open(CACHE_NAME).then((cache) => {
                            cache.put(request, responseClone);
                        });
                    }
                    return networkResponse;
                });
            })
        );
        return;
    }

    // 3. Navigation requests (SSR HTML) - Network First with Cache fallback
    if (request.mode === 'navigate') {
        event.respondWith(
            fetch(request).catch(() => {
                return caches.match(request).then((cached) => {
                    return cached || caches.match('/');
                });
            })
        );
        return;
    }

    // Default fetch
    event.respondWith(
        fetch(request).catch(() => caches.match(request))
    );
});
