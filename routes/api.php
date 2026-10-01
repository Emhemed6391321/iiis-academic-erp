<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\HQDashboardController;
use App\Http\Controllers\Api\StudentController;
use App\Http\Controllers\Api\StudentFileController;
use App\Http\Controllers\Api\FastGradeEntryController;
use App\Http\Controllers\Api\PermissionMatrixController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\AuditLogController;
use App\Http\Controllers\Api\ExamApprovalController;
use App\Http\Controllers\Api\TranscriptEngineController;
use App\Http\Controllers\Api\BranchOperationsController;
use App\Http\Controllers\Api\StudentRegistryReportController;
use App\Http\Controllers\Api\StudentAttendanceController;

// ============================================================
// 🔓 Public API Routes — لا تتطلب مصادقة
// ============================================================

Route::prefix('v1')->middleware('throttle:30,1')->group(function () {
    // مسار التحقق من صحة الوثائق الرسمية (عام - بدون تسجيل دخول)
    Route::get('/verify/{uuid}', [\App\Http\Controllers\PublicVerificationController::class, 'show']);

    // استقبال أخطاء الواجهة الأمامية (يُسمح دون مصادقة لضمان التسجيل دائماً)
    Route::post('/system-errors/report-js', [\App\Http\Controllers\Api\SystemErrorMonitoringController::class, 'reportJs']);
});

// ============================================================
// 🔒 Protected API Routes — تتطلب مصادقة (auth middleware)
// ============================================================

// MED-7: Rate limiting — 60 requests per minute for all authenticated routes
Route::prefix('v1')->middleware(['auth', 'throttle:60,1'])->group(function () {

    // 0. Secure File Vault Signed Download
    Route::get('/secure-vault/download', [\App\Http\Controllers\Api\SecureVaultDownloadController::class, 'download'])->name('secure.vault.download');

    // 1. HQ Central Command & Dashboard
    Route::get('/hq/dashboard', [HQDashboardController::class, 'getSummary']);

    // 2. Permission Matrix Module
    Route::get('/permissions/matrix', [PermissionMatrixController::class, 'getMatrix']);
    Route::post('/permissions/toggle', [PermissionMatrixController::class, 'toggleRolePermission']);

    // 3. Central Student Registry & Official Documentation Engine (سجل الطلاب العام والخدمات المستندية الرسمية)
    Route::get('/students/registry', [StudentRegistryReportController::class, 'index']);
    Route::get('/students/registry/export', [StudentRegistryReportController::class, 'exportRegistry']);
    Route::get('/students/{student}/confidential-report', [StudentRegistryReportController::class, 'getConfidentialReport']);

    // Shielded Documentation & Examination Routes (حجب الاستدعاء المباشر لوحدات قريباً وفق البند 15 و 16)
    Route::middleware(\App\Http\Middleware\ShieldedFeatureMiddleware::class)->group(function () {
        Route::get('/students/{student}/certificates/enrollment', [StudentRegistryReportController::class, 'getEnrollmentCertificate']);
        Route::get('/students/{student}/certificates/conduct', [StudentRegistryReportController::class, 'getGoodConductCertificate']);
        Route::get('/students/{student}/transcript', [\App\Http\Controllers\Api\TranscriptEngineController::class, 'generate']);
        Route::get('/transcripts/students/{student}', [\App\Http\Controllers\Api\TranscriptEngineController::class, 'generate']);
        Route::get('/exams/pending-batches', [\App\Http\Controllers\Api\ExamApprovalController::class, 'getPendingBatches']);
        Route::post('/exams/batches/{batch}/reject', [\App\Http\Controllers\Api\ExamApprovalController::class, 'rejectBatch']);
    });

    // 3a. Central Student Attendance & Departure Hub (وحدة حضور وانصراف الطلاب المركزية)
    Route::prefix('attendance')->group(function () {
        Route::get('/sheet', [StudentAttendanceController::class, 'getDailySheet']);
        Route::post('/batch-save', [StudentAttendanceController::class, 'batchSave']);
        Route::post('/single-record/{student}', [StudentAttendanceController::class, 'singleRecord']);
        Route::post('/batch-departure', [StudentAttendanceController::class, 'batchDeparture']);
        Route::get('/student/{student}', [StudentAttendanceController::class, 'getStudentAttendanceHistory']);
        Route::get('/dashboard-stats', [StudentAttendanceController::class, 'getDashboardStats']);
        Route::get('/at-risk-students', [StudentAttendanceController::class, 'getAtRiskStudents']);
        Route::post('/issue-warning/{student}', [StudentAttendanceController::class, 'issueWarningNotice']);
        Route::get('/reports', [StudentAttendanceController::class, 'getOfficialAttendanceReport']);
        Route::post('/scan', [StudentAttendanceController::class, 'scanQrOrBarcode']);
        Route::post('/early-permission/{student}', [StudentAttendanceController::class, 'issueEarlyDeparturePermission']);
        Route::post('/biometric-sync', [StudentAttendanceController::class, 'syncBiometricLogs']);
        Route::post('/batch-sync', [StudentAttendanceController::class, 'batchSync']);
    });

    // 3b. Student Affairs & Lifecycle (List + Register + Batch Import + Card + HQ Workflow)
    Route::get('/students', [StudentController::class, 'index']);
    Route::post('/students', [StudentController::class, 'store'])->middleware(['academic.year.active', 'check.window:REGISTRATION']);
    Route::post('/students/import-batch', [StudentController::class, 'importBatch'])->middleware(['academic.year.active']);
    Route::get('/students/sample-template', [StudentController::class, 'downloadSampleCsv']);
    Route::get('/students/{student}', [StudentController::class, 'show']);
    Route::match(['put', 'patch', 'post'], '/students/{student}', [StudentController::class, 'update']);
    Route::get('/students/{student}/card', [StudentController::class, 'getCard']);
    Route::post('/students/{student}/submit-hq', [StudentController::class, 'submitToHq']);
    Route::post('/students/{student}/approve-hq', [StudentController::class, 'approveByHq']);
    Route::post('/students/{student}/reject-hq', [StudentController::class, 'rejectByHq']);

    // 3c. Central Excuses Management Hub
    Route::get('/excuses', [StudentFileController::class, 'getAllExcuses']);
    Route::post('/excuses/{excuse}/review', [StudentFileController::class, 'reviewExcuse']);

    // =========================================================
    // 3d. Student File Management Module — وحدة إدارة ملف الطالب
    // =========================================================
    Route::prefix('students/{student}/file')->group(function () {
        Route::get('/', [StudentFileController::class, 'show']);
        Route::get('/timeline', [StudentFileController::class, 'getTimeline']);
        Route::get('/notes', [StudentFileController::class, 'getNotes']);
        Route::post('/notes', [StudentFileController::class, 'addNote']);
        Route::get('/behaviors', [StudentFileController::class, 'getBehaviors']);
        Route::post('/behaviors', [StudentFileController::class, 'addBehavior']);
        Route::get('/excuses', [StudentFileController::class, 'getExcuses']);
        Route::post('/excuses', [StudentFileController::class, 'addExcuse']);
        Route::get('/attendance', [StudentFileController::class, 'getAttendance']);
        Route::post('/attendance', [StudentFileController::class, 'addAttendance']);
        Route::post('/documents', [StudentFileController::class, 'uploadDocument']);
        Route::delete('/documents/{document}', [StudentFileController::class, 'deleteDocument']);
        Route::post('/change-status', [StudentFileController::class, 'changeStatus']);
        Route::post('/change-study-type', [StudentFileController::class, 'changeStudyType']);
        Route::post('/change-placement', [StudentFileController::class, 'changeAcademicPlacement']);
        Route::get('/status-requests', [StudentFileController::class, 'getStatusRequests']);
        Route::post('/status-requests', [StudentFileController::class, 'submitStatusRequest']);
        Route::post('/approve', [StudentFileController::class, 'approveData']);
        Route::post('/revoke', [StudentFileController::class, 'revokeData']);
        Route::post('/verify', [StudentFileController::class, 'verifyData']);
        Route::post('/transfer', [StudentFileController::class, 'transferStudent']);
        Route::post('/send-guardian-sms', [StudentFileController::class, 'sendGuardianCredentials']);
    });

    // 4. Control & Fast Tabular Grade Entry
    Route::get('/grades/sheet', [FastGradeEntryController::class, 'getGradeSheet']);
    Route::post('/grades/batch-save', [FastGradeEntryController::class, 'saveBatchGrades'])->middleware('academic.year.active');
    Route::post('/grades/batches/{batch}/submit-hq', [FastGradeEntryController::class, 'submitBatchToHq'])->middleware('academic.year.active');
    Route::post('/grades/batches/{batch}/approve-hq', [FastGradeEntryController::class, 'approveBatchByHq']);

    // 5. User Management & IAM
    Route::get('/users', [UserController::class, 'index']);
    Route::post('/users', [UserController::class, 'store']);
    Route::match(['put', 'patch', 'post'], '/users/{user}', [UserController::class, 'update']);
    Route::post('/users/{user}/toggle-status', [UserController::class, 'toggleStatus']);
    // MED-7: Tighter rate limit on password reset (max 10 per minute)
    Route::post('/users/{user}/reset-password', [UserController::class, 'resetPassword'])->middleware('throttle:10,1');
    Route::get('/users/{user}/permissions', [UserController::class, 'getUserPermissions']);
    Route::post('/users/{user}/permissions', [UserController::class, 'updateUserPermissions']);

    // 6. Forensic Audit Logs & Activity Monitoring
    Route::get('/audit/grade-logs', [AuditLogController::class, 'getGradeLogs']);
    Route::get('/audit/system-trails', [AuditLogController::class, 'getSystemAuditTrails']);
    Route::get('/audit/live-notifications', [AuditLogController::class, 'getLiveNotificationsAndAlerts']);

    // 9. Branch Operations & Infrastructure Integration
    Route::get('/branches/overview', [BranchOperationsController::class, 'getOverview']);
    Route::get('/branches/directory', [BranchOperationsController::class, 'getBranches']);
    Route::post('/branches', [BranchOperationsController::class, 'storeBranch']);
    Route::put('/branches/{id}', [BranchOperationsController::class, 'updateBranch']);
    Route::post('/branches/{id}/suspend', [BranchOperationsController::class, 'suspendBranch']);
    Route::post('/branches/{id}/activate', [BranchOperationsController::class, 'activateBranch']);
    Route::delete('/branches/{id}', [BranchOperationsController::class, 'safeDeleteBranch']);
    Route::get('/branches/{id}/details', [BranchOperationsController::class, 'getBranchDetails']);
    Route::post('/branches/{id}/assessments', [BranchOperationsController::class, 'storeAssessment']);
    Route::post('/branches/{id}/coordinates', [BranchOperationsController::class, 'updateCoordinates']);
    Route::get('/branches/requests', [BranchOperationsController::class, 'getRequests']);
    Route::post('/branches/requests', [BranchOperationsController::class, 'storeRequest']);
    Route::patch('/branches/requests/{id}/status', [BranchOperationsController::class, 'updateRequestStatus']);
    Route::get('/branches/contracts', [BranchOperationsController::class, 'getContracts']);

    // 9b. Properties & Buildings Module (العقارات والمقرات)
    Route::get('/properties', [\App\Http\Controllers\Api\PropertyContractController::class, 'listProperties']);
    Route::post('/properties', [\App\Http\Controllers\Api\PropertyContractController::class, 'storeProperty']);
    Route::get('/properties/{id}', [\App\Http\Controllers\Api\PropertyContractController::class, 'showProperty']);
    Route::put('/properties/{id}', [\App\Http\Controllers\Api\PropertyContractController::class, 'updateProperty']);
    Route::delete('/properties/{id}', [\App\Http\Controllers\Api\PropertyContractController::class, 'deleteProperty']);

    // 9c. Contracts Lifecycle & Financial Management (نظام العقود والمالية)
    Route::get('/contracts', [\App\Http\Controllers\Api\PropertyContractController::class, 'listContracts']);
    Route::post('/contracts', [\App\Http\Controllers\Api\PropertyContractController::class, 'storeContract']);
    Route::get('/contracts/alerts', [\App\Http\Controllers\Api\PropertyContractController::class, 'getAlerts']);
    Route::get('/contracts/{id}', [\App\Http\Controllers\Api\PropertyContractController::class, 'showContract']);
    Route::post('/contracts/{id}/approve', [\App\Http\Controllers\Api\PropertyContractController::class, 'approveContract']);
    Route::post('/contracts/{id}/suspend', [\App\Http\Controllers\Api\PropertyContractController::class, 'suspendContract']);
    Route::post('/contracts/{id}/activate', [\App\Http\Controllers\Api\PropertyContractController::class, 'activateContract']);
    Route::post('/contracts/{id}/terminate', [\App\Http\Controllers\Api\PropertyContractController::class, 'terminateContract']);
    Route::post('/contracts/{id}/payments', [\App\Http\Controllers\Api\PropertyContractController::class, 'recordPayment']);

    // 9d. Financial Statements Engine
    Route::get('/financial/property/{id}', [\App\Http\Controllers\Api\PropertyContractController::class, 'getPropertyStatement']);
    Route::get('/financial/branch/{id}', [\App\Http\Controllers\Api\PropertyContractController::class, 'getBranchStatement']);
    Route::get('/financial/central', [\App\Http\Controllers\Api\PropertyContractController::class, 'getCentralStatement']);

    // 10. Universal Command Palette Search (Ctrl + K)
    Route::get('/search/universal', [BranchOperationsController::class, 'universalSearch']);

    // 11. Courses & Curriculum Management
    Route::get('/courses', [\App\Http\Controllers\Api\CourseController::class, 'index']);
    Route::post('/courses', [\App\Http\Controllers\Api\CourseController::class, 'store'])->middleware('academic.year.active');
    Route::get('/courses/{id}', [\App\Http\Controllers\Api\CourseController::class, 'show']);
    Route::post('/courses/{id}', [\App\Http\Controllers\Api\CourseController::class, 'update'])->middleware('academic.year.active');
    Route::put('/courses/{id}', [\App\Http\Controllers\Api\CourseController::class, 'update'])->middleware('academic.year.active');
    Route::delete('/courses/{id}', [\App\Http\Controllers\Api\CourseController::class, 'destroy'])->middleware('academic.year.active');

    // 12. Central Settings & Academic Calendar
    Route::get('/settings/calendar-data', [\App\Http\Controllers\Api\SettingsController::class, 'getCalendarData']);
    Route::post('/settings/update-year-dates', [\App\Http\Controllers\Api\SettingsController::class, 'updateYearDates']);
    Route::post('/settings/calendar-event', [\App\Http\Controllers\Api\SettingsController::class, 'addCalendarEvent']);
    Route::delete('/settings/calendar-event/{id}', [\App\Http\Controllers\Api\SettingsController::class, 'deleteCalendarEvent']);
    Route::post('/settings/schedule-event', [\App\Http\Controllers\Api\SettingsController::class, 'addScheduleEvent']);
    Route::delete('/settings/schedule-event/{id}', [\App\Http\Controllers\Api\SettingsController::class, 'deleteScheduleEvent']);
    Route::post('/settings/set-active-year', [\App\Http\Controllers\Api\SettingsController::class, 'setActiveYear']);
    Route::post('/settings/create-year', [\App\Http\Controllers\Api\SettingsController::class, 'createYear']);
    Route::post('/settings/academic-years', [\App\Http\Controllers\Api\SettingsController::class, 'createYear']);
    Route::put('/settings/years/{id}', [\App\Http\Controllers\Api\SettingsController::class, 'updateYear']);
    Route::get('/settings/years/{id}/inspect', [\App\Http\Controllers\Api\SettingsController::class, 'inspectYear']);
    Route::delete('/settings/years/{id}', [\App\Http\Controllers\Api\SettingsController::class, 'deleteYear']);

    // Academic Year Engine & Lifecycle
    Route::post('/settings/activate-year', [\App\Http\Controllers\Api\SettingsController::class, 'activateYear']);
    Route::post('/settings/lock-year', [\App\Http\Controllers\Api\SettingsController::class, 'lockYear']);
    Route::post('/settings/duplicate-courses', [\App\Http\Controllers\Api\SettingsController::class, 'duplicateCourses']);
    Route::get('/settings/year-status', [\App\Http\Controllers\Api\SettingsController::class, 'getYearStatus']);

    // Operational Windows Engine & Branch Exceptions
    Route::get('/settings/operational-windows', [\App\Http\Controllers\Api\SettingsController::class, 'getOperationalWindows']);
    Route::post('/settings/operational-windows', [\App\Http\Controllers\Api\SettingsController::class, 'saveOperationalWindow']);
    Route::post('/settings/operational-windows/toggle', [\App\Http\Controllers\Api\SettingsController::class, 'toggleOperationalWindow']);
    Route::post('/settings/operational-windows/exception', [\App\Http\Controllers\Api\SettingsController::class, 'addWindowException']);
    Route::delete('/settings/operational-windows/exception/{id}', [\App\Http\Controllers\Api\SettingsController::class, 'deleteWindowException']);

    // Student Progression & Rollover Engine
    Route::post('/settings/progression/simulate', [\App\Http\Controllers\Api\SettingsController::class, 'simulateStudentProgression']);
    Route::post('/settings/progression/execute', [\App\Http\Controllers\Api\SettingsController::class, 'executeStudentProgression']);
    Route::get('/settings/progression/logs', [\App\Http\Controllers\Api\SettingsController::class, 'getRolloverLogs']);

    Route::post('/settings/student-services', [\App\Http\Controllers\Api\SettingsController::class, 'saveStudentServices']);
    Route::post('/settings/admin-periods', [\App\Http\Controllers\Api\SettingsController::class, 'saveAdminPeriods']);
    Route::post('/settings/results-gateways', [\App\Http\Controllers\Api\SettingsController::class, 'updateResultsGateways']);
    Route::post('/settings/positions', [\App\Http\Controllers\Api\SettingsController::class, 'addPosition']);
    Route::put('/settings/positions/{id}', [\App\Http\Controllers\Api\SettingsController::class, 'updatePosition']);
    Route::delete('/settings/positions/{id}', [\App\Http\Controllers\Api\SettingsController::class, 'deletePosition']);

    // 13. Study and Examinations Department (قسم الدراسة والامتحانات)
    Route::get('/study-and-exams/dashboard', [\App\Http\Controllers\Api\StudyAndExamsController::class, 'getDashboardData']);

    // 14. Student Data Quality & Deficiency Audit Hub (جودة البيانات ونواقص الطلاب)
    Route::post('/students/data-quality/audit', [\App\Http\Controllers\Api\StudentDataQualityController::class, 'auditDeficiencies']);
    Route::get('/students/data-quality/export', [\App\Http\Controllers\Api\StudentDataQualityController::class, 'exportCsv']);

    // 15. Student Administrative & Academic Workflow Center (مركز قيادة وسير عمل طلبات الطلاب)
    Route::get('/student-workflow/summary', [\App\Http\Controllers\Api\StudentWorkflowController::class, 'getSummaryCounters']);
    Route::get('/student-workflow/requests', [\App\Http\Controllers\Api\StudentWorkflowController::class, 'getRequests']);
    Route::post('/student-workflow/status-request/{id}/action', [\App\Http\Controllers\Api\StudentWorkflowController::class, 'handleStatusAction']);
    Route::post('/student-workflow/system-request/{id}/action', [\App\Http\Controllers\Api\StudentWorkflowController::class, 'handleSystemAction']);
    Route::post('/student-workflow/transfer-request/{id}/step', [\App\Http\Controllers\Api\StudentWorkflowController::class, 'handleTransferStep']);
    Route::get('/student-workflow/requests/{type}/{id}/comments', [\App\Http\Controllers\Api\StudentWorkflowController::class, 'getComments']);
    Route::post('/student-workflow/requests/{type}/{id}/comments', [\App\Http\Controllers\Api\StudentWorkflowController::class, 'addComment']);
    Route::get('/student-workflow/transfers/export', [\App\Http\Controllers\Api\StudentWorkflowController::class, 'exportTransferFollowup']);
    Route::post('/student-workflow/submit', [\App\Http\Controllers\Api\StudentWorkflowController::class, 'submitBranchRequest']);

    // 16. Central Administrative Settings & Org Structure Engine (الإعدادات الإدارية المركزية والهيكل التنظيمي)
    Route::prefix('admin/settings')->group(function () {
        Route::get('/all', [\App\Http\Controllers\Api\AdminSettingsController::class, 'getMasterSettings']);
        Route::post('/institute-profile', [\App\Http\Controllers\Api\AdminSettingsController::class, 'updateInstituteProfile']);
        Route::post('/org-unit/save', [\App\Http\Controllers\Api\AdminSettingsController::class, 'saveOrgUnit']);
        Route::delete('/org-unit/{id}', [\App\Http\Controllers\Api\AdminSettingsController::class, 'deleteOrgUnit']);
        Route::post('/job-position/save', [\App\Http\Controllers\Api\AdminSettingsController::class, 'saveJobPosition']);
        Route::delete('/job-position/{id}', [\App\Http\Controllers\Api\AdminSettingsController::class, 'deleteJobPosition']);
        Route::post('/placement/save', [\App\Http\Controllers\Api\AdminSettingsController::class, 'saveEmployeePlacement']);
        Route::delete('/placement/{id}', [\App\Http\Controllers\Api\AdminSettingsController::class, 'deleteEmployeePlacement']);
        Route::post('/signatories/save', [\App\Http\Controllers\Api\AdminSettingsController::class, 'saveDocumentSignatories']);
    });

    // =========================================================================
    // 17. Academic Structure, Stages, Departments & Classes (الهيكل الأكاديمي والمراحل والشعب)
    // =========================================================================
    Route::prefix('academic-structure')->group(function () {
        Route::get('/overview', [\App\Http\Controllers\Api\AcademicStructureController::class, 'getOverview']);

        // Study Years / Stages
        Route::post('/study-years', [\App\Http\Controllers\Api\AcademicStructureController::class, 'storeStudyYear']);
        Route::put('/study-years/{id}', [\App\Http\Controllers\Api\AcademicStructureController::class, 'updateStudyYear']);
        Route::delete('/study-years/{id}', [\App\Http\Controllers\Api\AcademicStructureController::class, 'destroyStudyYear']);

        // Departments & Majors
        Route::post('/departments', [\App\Http\Controllers\Api\AcademicStructureController::class, 'storeDepartment']);
        Route::put('/departments/{id}', [\App\Http\Controllers\Api\AcademicStructureController::class, 'updateDepartment']);
        Route::post('/departments/{id}/toggle-status', [\App\Http\Controllers\Api\AcademicStructureController::class, 'toggleDepartmentStatus']);
        Route::delete('/departments/{id}', [\App\Http\Controllers\Api\AcademicStructureController::class, 'destroyDepartment']);

        // Branch Classes & Sections
        Route::post('/classes', [\App\Http\Controllers\Api\AcademicStructureController::class, 'storeBranchClass']);
        Route::put('/classes/{id}', [\App\Http\Controllers\Api\AcademicStructureController::class, 'updateBranchClass']);
        Route::delete('/classes/{id}', [\App\Http\Controllers\Api\AcademicStructureController::class, 'destroyBranchClass']);
    });

    // =========================================================================
    // 18. System Error Monitoring Center (مركز مراقبة أخطاء النظام - حصري للمستخدم الرئيسي)
    // =========================================================================
    Route::prefix('system-errors')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\SystemErrorMonitoringController::class, 'index']);
        Route::get('/{id}', [\App\Http\Controllers\Api\SystemErrorMonitoringController::class, 'show'])->where('id', '[0-9]+');
        Route::post('/{id}/status', [\App\Http\Controllers\Api\SystemErrorMonitoringController::class, 'updateStatus'])->where('id', '[0-9]+');
        Route::post('/trigger-test', [\App\Http\Controllers\Api\SystemErrorMonitoringController::class, 'triggerTestError']);
        Route::delete('/clear-test-logs', [\App\Http\Controllers\Api\SystemErrorMonitoringController::class, 'clearTestLogs']);
    });

    // 19. Enterprise System Health & Ops Monitoring
    Route::get('/health', [\App\Http\Controllers\Api\SystemHealthController::class, 'check']);
});
