<?php

namespace App\Services;

use App\Models\OfflineSyncLog;
use App\Models\Student;
use App\Models\StudentAttendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class OfflineAttendanceSyncService
{
    /**
     * Process an offline attendance batch from a branch device.
     */
    public function processBatch(array $payload, User $user): array
    {
        $batchId    = $payload['batch_id'] ?? (string) Str::uuid();
        $deviceUuid = $payload['device_uuid'] ?? 'unknown_device';
        $records    = $payload['records'] ?? [];

        // Determine branch isolation
        $branchId = $user->isHQ()
            ? ($payload['branch_id'] ?? $user->branch_id)
            : $user->branch_id;

        $results = [
            'batch_id'           => $batchId,
            'total'              => count($records),
            'synced'             => 0,
            'duplicates'         => 0,
            'conflicts'          => 0,
            'failed'             => 0,
            'records_processed'  => [],
        ];

        DB::beginTransaction();
        try {
            foreach ($records as $index => $item) {
                $syncNonce        = $item['sync_nonce'] ?? null;
                $studentId        = $item['student_id'] ?? null;
                $recordDate       = isset($item['record_date']) ? Carbon::parse($item['record_date'])->format('Y-m-d') : Carbon::today()->format('Y-m-d');
                $clientRecordedAt = isset($item['client_recorded_at']) ? Carbon::parse($item['client_recorded_at']) : Carbon::now();

                if (!$studentId) {
                    $results['failed']++;
                    $results['records_processed'][] = [
                        'nonce'   => $syncNonce,
                        'status'  => 'failed',
                        'message' => 'معرف الطالب مفقود في السجل',
                    ];
                    continue;
                }

                // Verify student belongs to this branch
                $student = Student::where('id', $studentId)
                    ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
                    ->first();

                if (!$student) {
                    $results['failed']++;
                    $results['records_processed'][] = [
                        'nonce'   => $syncNonce,
                        'student_id' => $studentId,
                        'status'  => 'failed',
                        'message' => 'الطالب غير موجود أو لا ينتمي لهذا الفرع',
                    ];
                    continue;
                }

                // 1. Check idempotency by sync_nonce
                if ($syncNonce) {
                    $existingByNonce = StudentAttendance::withoutGlobalScopes()->where('sync_nonce', $syncNonce)->first();
                    if ($existingByNonce) {
                        $results['duplicates']++;
                        $results['records_processed'][] = [
                            'nonce'         => $syncNonce,
                            'attendance_id' => $existingByNonce->id,
                            'status'        => 'duplicate_ignored',
                            'message'       => 'تم تسجيل هذه الحركة مسبقاً بنفس المفتاح الرقمي (Idempotent)',
                        ];
                        continue;
                    }
                }

                // 2. Check existing attendance for this student on the same date
                $existingRecord = StudentAttendance::withoutGlobalScopes()
                    ->where('student_id', $student->id)
                    ->where(function ($q) use ($recordDate) {
                        $q->whereDate('record_date', $recordDate)
                          ->orWhere('record_date', $recordDate)
                          ->orWhere('record_date', 'like', "$recordDate%");
                    })
                    ->first();

                if ($existingRecord) {
                    // Conflict or Merge Resolution:
                    // If existing has check-in and offline brings check-out/departure:
                    $offlineCheckOut = $item['check_out_time'] ?? null;
                    $offlineCheckIn  = $item['check_in_time'] ?? null;

                    $rawStatus = strtoupper($item['status'] ?? $existingRecord->status);
                    $offlineStatus = in_array($rawStatus, ['PRESENT', 'ABSENT', 'LATE', 'EXCUSED']) ? $rawStatus : 'PRESENT';

                    $rawDeparture = strtoupper($item['departure_status'] ?? 'NOT_DEPARTED');
                    $offlineDeparture = in_array($rawDeparture, ['DEPARTED', 'EARLY_DEPARTURE', 'NOT_DEPARTED']) 
                        ? $rawDeparture 
                        : (!empty($offlineCheckOut) ? 'DEPARTED' : 'NOT_DEPARTED');

                    if ($offlineCheckOut && empty($existingRecord->check_out_time)) {
                        // Merge departure into existing check-in
                        $existingRecord->update([
                            'check_out_time'   => $offlineCheckOut,
                            'departure_status' => $offlineDeparture,
                            'departure_reason' => $item['departure_reason'] ?? $existingRecord->departure_reason,
                            'updated_by'       => $user->id,
                            'is_offline_sync'  => true,
                        ]);

                        $results['synced']++;
                        $results['records_processed'][] = [
                            'nonce'         => $syncNonce,
                            'attendance_id' => $existingRecord->id,
                            'status'        => 'merged_departure',
                            'message'       => 'تم دمج حركة الانصراف مع الحضور المسجل سابقاً',
                        ];
                        continue;
                    }

                    // If existing attendance was modified with an admin reason centrally, preserve central authority
                    if (!empty($existingRecord->modification_reason)) {
                        $results['conflicts']++;
                        $results['records_processed'][] = [
                            'nonce'         => $syncNonce,
                            'attendance_id' => $existingRecord->id,
                            'status'        => 'conflict_preserved_central',
                            'message'       => 'السجل معتمد مركزياً بسبب تعديل إداري مسبق، تم حفظ الأصل وإشعار الإدارة',
                        ];
                        continue;
                    }

                    // Update existing record if offline sync is more detailed or new state
                    $existingRecord->update([
                        'status'              => $offlineStatus,
                        'check_in_time'       => $offlineCheckIn ?? $existingRecord->check_in_time,
                        'check_out_time'      => $offlineCheckOut ?? $existingRecord->check_out_time,
                        'departure_status'    => $offlineDeparture,
                        'departure_reason'    => $item['departure_reason'] ?? $existingRecord->departure_reason,
                        'late_minutes'        => $item['late_minutes'] ?? $existingRecord->late_minutes,
                        'verification_method' => $item['verification_method'] ?? 'offline_sync',
                        'is_offline_sync'     => true,
                        'sync_nonce'          => $syncNonce,
                        'client_recorded_at'  => $clientRecordedAt,
                        'updated_by'          => $user->id,
                    ]);

                    $results['synced']++;
                    $results['records_processed'][] = [
                        'nonce'         => $syncNonce,
                        'attendance_id' => $existingRecord->id,
                        'status'        => 'updated',
                        'message'       => 'تم تحديث سجل الحضور بالبيانات الأحدث دون تكرار',
                    ];
                } else {
                    $rawStatus = strtoupper($item['status'] ?? 'PRESENT');
                    $offlineStatus = in_array($rawStatus, ['PRESENT', 'ABSENT', 'LATE', 'EXCUSED']) ? $rawStatus : 'PRESENT';

                    $rawDeparture = strtoupper($item['departure_status'] ?? 'NOT_DEPARTED');
                    $offlineDeparture = in_array($rawDeparture, ['DEPARTED', 'EARLY_DEPARTURE', 'NOT_DEPARTED']) 
                        ? $rawDeparture 
                        : (!empty($item['check_out_time']) ? 'DEPARTED' : 'NOT_DEPARTED');

                    // Create fresh attendance record
                    $attendance = StudentAttendance::create([
                        'student_id'          => $student->id,
                        'branch_id'           => $student->branch_id,
                        'academic_year_id'    => $student->enrolled_academic_year_id ?? $student->academic_year_id ?? 1,
                        'study_year_id'       => $student->current_study_year_id ?? $student->study_year_id ?? 1,
                        'department_id'       => $student->department_id,
                        'record_date'         => $recordDate,
                        'day_of_week'         => Carbon::parse($recordDate)->locale('ar')->dayName,
                        'check_in_time'       => $item['check_in_time'] ?? null,
                        'check_out_time'      => $item['check_out_time'] ?? null,
                        'status'              => $offlineStatus,
                        'late_minutes'        => $item['late_minutes'] ?? 0,
                        'departure_status'    => $offlineDeparture,
                        'departure_reason'    => $item['departure_reason'] ?? null,
                        'absence_reason'      => $item['absence_reason'] ?? null,
                        'verification_method' => $item['verification_method'] ?? 'offline_sync',
                        'device_id'           => $deviceUuid,
                        'sync_nonce'          => $syncNonce ?: (string) Str::uuid(),
                        'client_recorded_at'  => $clientRecordedAt,
                        'is_offline_sync'     => true,
                        'recorded_by'         => $user->id,
                    ]);

                    $results['synced']++;
                    $results['records_processed'][] = [
                        'nonce'         => $syncNonce,
                        'attendance_id' => $attendance->id,
                        'status'        => 'created',
                        'message'       => 'تم تثبيت حركة الحضور غير المتصلة بنجاح',
                    ];
                }
            }

            // Log or update batch summary into offline_sync_logs (Idempotent tracking)
            OfflineSyncLog::updateOrCreate(
                ['batch_uuid' => $batchId],
                [
                    'branch_id'          => $branchId,
                    'user_id'            => $user->id,
                    'client_device_info' => $deviceUuid,
                    'total_records'      => $results['total'],
                    'synced_count'       => $results['synced'],
                    'conflicts_count'    => $results['conflicts'],
                    'skipped_count'      => $results['duplicates'],
                    'sync_status'        => ($results['failed'] > 0 && $results['synced'] === 0) ? 'FAILED' : 'COMPLETED',
                ]
            );

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Offline attendance batch sync failed: ' . $e->getMessage(), [
                'batch_id' => $batchId,
                'trace'    => $e->getTraceAsString(),
            ]);

            try {
                OfflineSyncLog::updateOrCreate(
                    ['batch_uuid' => $batchId],
                    [
                        'branch_id'          => $branchId,
                        'user_id'            => $user->id,
                        'client_device_info' => $deviceUuid,
                        'total_records'      => count($records),
                        'synced_count'       => 0,
                        'conflicts_count'    => 0,
                        'skipped_count'      => 0,
                        'sync_status'        => 'FAILED',
                    ]
                );
            } catch (\Throwable $_) {}

            throw $e;
        }

        return $results;
    }
}
