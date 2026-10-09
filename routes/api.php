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

    // نماذج استيراد البيانات القياسية المعتمدة (قوالب إكسل و CSV عامة لا تحتوي بيانات خاصة)
    Route::get('/branches/sample-template-xlsx', [BranchOperationsController::class, 'downloadBranchesSampleXlsx']);
    Route::get('/students/sample-template', [StudentController::class, 'downloadSampleTemplate']);
    Route::get('/students/sample-template-xlsx', [StudentController::class, 'downloadSampleXlsx']);
    Route::get('/students/sample-template-csv', [StudentController::class, 'downloadSampleCsv']);
});

// ============================================================
// 🔒 Protected API Routes — تتطلب مصادقة (auth middleware)
// ============================================================

// MED-7: Rate limiting — 60 requests per minute for all authenticated routes
Route::prefix('v1')->middleware(['auth', 'enforce.mfa', 'force.password_change', 'throttle:60,1'])->group(function () {

    // 0. Central Auth & User Context Payload for RBAC & SPA State
    Route::get('/auth/me', [\App\Http\Controllers\Api\UserProfileController::class, 'me']);

    // 0b. Secure File Vault Signed Download
    Route::get('/secure-vault/download', [\App\Http\Controllers\Api\SecureVaultDownloadController::class, 'download'])->name('secure.vault.download');

    // 1. HQ Central Command & Dashboard
    Route::get('/hq/dashboard', [HQDashboardController::class, 'getSummary'])->middleware('permission:branches.view|windows.view');

    // 2. Permission Matrix Module
    Route::get('/permissions/matrix', [PermissionMatrixController::class, 'getMatrix'])->middleware('permission:permissions.manage|MANAGE_ROLES');
    Route::post('/permissions/toggle', [PermissionMatrixController::class, 'toggleRolePermission'])->middleware('permission:permissions.manage|MANAGE_ROLES');

    // 3. Central Student Registry & Official Documentation Engine (سجل الطلاب العام والخدمات المستندية الرسمية)
    Route::get('/students/registry', [StudentRegistryReportController::class, 'index'])->middleware('permission:students.view');
    Route::get('/students/registry/export', [StudentRegistryReportController::class, 'exportRegistry'])->middleware('permission:students.view');
    Route::get('/students/{student}/confidential-report', [StudentRegistryReportController::class, 'getConfidentialReport'])->middleware('permission:reports.print_official');
    Route::get('/students/{student}/certificates/enrollment', [StudentRegistryReportController::class, 'getEnrollmentCertificate'])->middleware('permission:reports.print_official');
    Route::get('/students/{student}/certificates/conduct', [StudentRegistryReportController::class, 'getGoodConductCertificate'])->middleware('permission:reports.print_official');

    // Shielded Documentation & Examination Routes (حجب الاستدعاء المباشر لوحدات قريباً وفق البند 15 و 16)
    Route::middleware(\App\Http\Middleware\ShieldedFeatureMiddleware::class)->group(function () {
        Route::get('/students/{student}/transcript', [\App\Http\Controllers\Api\TranscriptEngineController::class, 'generate'])->middleware('permission:reports.print_official');
        Route::get('/transcripts/students/{student}', [\App\Http\Controllers\Api\TranscriptEngineController::class, 'generate'])->middleware('permission:reports.print_official');
        Route::get('/exams/pending-batches', [\App\Http\Controllers\Api\ExamApprovalController::class, 'getPendingBatches'])->middleware('permission:grades.approve_hq');
        Route::post('/exams/batches/{batch}/reject', [\App\Http\Controllers\Api\ExamApprovalController::class, 'rejectBatch'])->middleware('permission:grades.approve_hq');
    });

    // 3a. Central Student Attendance & Departure Hub (وحدة حضور وانصراف الطلاب المركزية)
    Route::prefix('attendance')->group(function () {
        Route::get('/sheet', [StudentAttendanceController::class, 'getDailySheet'])->middleware('permission:attendance.view');
        Route::post('/batch-save', [StudentAttendanceController::class, 'batchSave'])->middleware('permission:attendance.record');
        Route::post('/single-record/{student}', [StudentAttendanceController::class, 'singleRecord'])->middleware('permission:attendance.record');
        Route::post('/batch-departure', [StudentAttendanceController::class, 'batchDeparture'])->middleware('permission:attendance.record');
        Route::get('/student/{student}', [StudentAttendanceController::class, 'getStudentAttendanceHistory'])->middleware('permission:attendance.view');
        Route::get('/dashboard-stats', [StudentAttendanceController::class, 'getDashboardStats'])->middleware('permission:attendance.view');
        Route::get('/at-risk-students', [StudentAttendanceController::class, 'getAtRiskStudents'])->middleware('permission:attendance.view');
        Route::post('/issue-warning/{student}', [StudentAttendanceController::class, 'issueWarningNotice'])->middleware('permission:attendance.reports');
        Route::get('/reports', [StudentAttendanceController::class, 'getOfficialAttendanceReport'])->middleware('permission:attendance.view');
        Route::post('/scan', [StudentAttendanceController::class, 'scanQrOrBarcode'])->middleware('permission:attendance.record');
        Route::post('/early-permission/{student}', [StudentAttendanceController::class, 'issueEarlyDeparturePermission'])->middleware('permission:attendance.record');
        Route::post('/biometric-sync', [StudentAttendanceController::class, 'syncBiometricLogs'])->middleware('permission:attendance.record');
        Route::post('/batch-sync', [StudentAttendanceController::class, 'batchSync'])->middleware('permission:attendance.record');
        Route::post('/sync', [StudentAttendanceController::class, 'sync'])->middleware('permission:attendance.record');
    });

    // 3b. Student Affairs & Lifecycle (List + Register + Batch Import + Card + HQ Workflow)
    Route::get('/students', [StudentController::class, 'index'])->middleware('permission:students.view');
    Route::post('/students', [StudentController::class, 'store'])->middleware(['academic.year.active', 'check.window:REGISTRATION', 'permission:students.create']);
    Route::post('/students/import-batch', [StudentController::class, 'importBatch'])->middleware(['academic.year.active', 'permission:students.create']);
    Route::get('/students/{student}', [StudentController::class, 'show'])->middleware('permission:students.view');
    Route::match(['put', 'patch', 'post'], '/students/{student}', [StudentController::class, 'update'])->middleware('permission:students.create');
    Route::get('/students/{student}/card', [StudentController::class, 'getCard'])->middleware('permission:students.view');
    Route::post('/students/{student}/archive', [StudentController::class, 'archive'])->middleware('permission:students.archive|students.create');
    Route::post('/students/{student}/restore', [StudentController::class, 'restore'])->middleware('permission:students.archive|students.create');
    Route::delete('/students/{student}', [StudentController::class, 'destroy'])->middleware('permission:students.delete');
    Route::post('/students/{student}/submit-hq', [StudentController::class, 'submitToHq'])->middleware('permission:students.submit_hq');
    Route::post('/students/{student}/approve-hq', [StudentController::class, 'approveByHq'])->middleware('permission:students.approve_hq');
    Route::post('/students/{student}/reject-hq', [StudentController::class, 'rejectByHq'])->middleware('permission:students.approve_hq');

    // 3c. Central Excuses Management Hub
    Route::get('/excuses', [StudentFileController::class, 'getAllExcuses'])->middleware('permission:students.view');
    Route::post('/excuses/{excuse}/review', [StudentFileController::class, 'reviewExcuse'])->middleware('permission:APPROVE_STUDENT_DATA|students.approve_hq');

    // =========================================================
    // 3d. Student File Management Module — وحدة إدارة ملف الطالب
    // =========================================================
    Route::prefix('students/{student}/file')->group(function () {
        Route::get('/', [StudentFileController::class, 'show'])->middleware('permission:students.view');
        Route::get('/timeline', [StudentFileController::class, 'getTimeline'])->middleware('permission:students.view');
        Route::get('/notes', [StudentFileController::class, 'getNotes'])->middleware('permission:students.view');
        Route::post('/notes', [StudentFileController::class, 'addNote'])->middleware('permission:students.create');
        Route::get('/behaviors', [StudentFileController::class, 'getBehaviors'])->middleware('permission:students.view');
        Route::post('/behaviors', [StudentFileController::class, 'addBehavior'])->middleware('permission:students.create');
        Route::get('/excuses', [StudentFileController::class, 'getExcuses'])->middleware('permission:students.view');
        Route::post('/excuses', [StudentFileController::class, 'addExcuse'])->middleware('permission:students.create');
        Route::get('/attendance', [StudentFileController::class, 'getAttendance'])->middleware('permission:students.view');
        Route::post('/attendance', [StudentFileController::class, 'addAttendance'])->middleware('permission:attendance.record');
        Route::post('/documents', [StudentFileController::class, 'uploadDocument'])->middleware('permission:students.create');
        Route::delete('/documents/{document}', [StudentFileController::class, 'deleteDocument'])->middleware('permission:students.create|students.archive');
        Route::post('/change-status', [StudentFileController::class, 'changeStatus'])->middleware('permission:CHANGE_STUDENT_STATUS');
        Route::post('/change-study-type', [StudentFileController::class, 'changeStudyType'])->middleware('permission:CHANGE_STUDENT_STATUS');
        Route::post('/change-placement', [StudentFileController::class, 'changeAcademicPlacement'])->middleware('permission:CHANGE_STUDENT_STATUS');
        Route::get('/status-requests', [StudentFileController::class, 'getStatusRequests'])->middleware('permission:students.view');
        Route::post('/status-requests', [StudentFileController::class, 'submitStatusRequest'])->middleware('permission:students.create');
        Route::post('/approve', [StudentFileController::class, 'approveData'])->middleware('permission:APPROVE_STUDENT_DATA');
        Route::post('/revoke', [StudentFileController::class, 'revokeData'])->middleware('permission:APPROVE_STUDENT_DATA');
        Route::post('/verify', [StudentFileController::class, 'verifyData'])->middleware('permission:APPROVE_STUDENT_DATA');
        Route::post('/transfer', [StudentFileController::class, 'transferStudent'])->middleware('permission:students.transfer');
        Route::post('/send-guardian-sms', [StudentFileController::class, 'sendGuardianCredentials'])->middleware('permission:students.view');
    });

    // 4. Control & Fast Tabular Grade Entry
    Route::get('/grades/sheet', [FastGradeEntryController::class, 'getGradeSheet'])->middleware('permission:grades.view');
    Route::post('/grades/batch-save', [FastGradeEntryController::class, 'saveBatchGrades'])->middleware(['academic.year.active', 'permission:grades.enter_coursework|grades.enter_final']);
    Route::post('/grades/batches/{batch}/submit-hq', [FastGradeEntryController::class, 'submitBatchToHq'])->middleware(['academic.year.active', 'permission:grades.submit_batch']);
    Route::post('/grades/batches/{batch}/approve-hq', [FastGradeEntryController::class, 'approveBatchByHq'])->middleware('permission:grades.approve_hq');

    // 5. User Management & IAM
    Route::get('/users', [UserController::class, 'index'])->middleware('permission:users.view|users.manage');
    Route::post('/users', [UserController::class, 'store'])->middleware('permission:users.manage');
    Route::match(['put', 'patch', 'post'], '/users/{user}', [UserController::class, 'update'])->middleware('permission:users.manage');
    Route::post('/users/{user}/toggle-status', [UserController::class, 'toggleStatus'])->middleware('permission:users.manage');
    // MED-7: Tighter rate limit on password reset (max 10 per minute)
    Route::post('/users/{user}/reset-password', [UserController::class, 'resetPassword'])->middleware(['throttle:10,1', 'permission:users.manage']);
    Route::get('/users/{user}/permissions', [UserController::class, 'getUserPermissions'])->middleware('permission:MANAGE_USER_PERMISSIONS');
    Route::post('/users/{user}/permissions', [UserController::class, 'updateUserPermissions'])->middleware('permission:MANAGE_USER_PERMISSIONS');

    // 6. Forensic Audit Logs & Activity Monitoring
    Route::get('/audit/grade-logs', [AuditLogController::class, 'getGradeLogs'])->middleware('permission:audit.view');
    Route::get('/audit/system-trails', [AuditLogController::class, 'getSystemAuditTrails'])->middleware('permission:audit.view');
    Route::get('/audit/live-notifications', [AuditLogController::class, 'getLiveNotificationsAndAlerts'])->middleware('permission:audit.view');

    // 9. Branch Operations & Infrastructure Integration
    Route::get('/branches/overview', [BranchOperationsController::class, 'getOverview'])->middleware('permission:branches.view');
    Route::get('/branches/directory', [BranchOperationsController::class, 'getBranches'])->middleware('permission:branches.view');
    Route::post('/branches/import-excel', [BranchOperationsController::class, 'importBranchesExcel'])->middleware('permission:branches.manage');
    Route::post('/branches', [BranchOperationsController::class, 'storeBranch'])->middleware('permission:branches.manage');
    Route::put('/branches/{id}', [BranchOperationsController::class, 'updateBranch'])->middleware('permission:branches.manage');
    Route::post('/branches/{id}', [BranchOperationsController::class, 'updateBranch'])->middleware('permission:branches.manage');
    Route::post('/branches/{id}/photos', [BranchOperationsController::class, 'uploadBranchPhoto'])->middleware('permission:branches.manage');
    Route::delete('/branches/{id}/photos/{photoIndex}', [BranchOperationsController::class, 'deleteBranchPhoto'])->middleware('permission:branches.manage');
    Route::post('/branches/{id}/classes', [BranchOperationsController::class, 'storeBranchClass'])->middleware('permission:branches.manage');
    Route::put('/branches/{id}/classes/{classId}', [BranchOperationsController::class, 'updateBranchClass'])->middleware('permission:branches.manage');
    Route::delete('/branches/{id}/classes/{classId}', [BranchOperationsController::class, 'deleteBranchClass'])->middleware('permission:branches.manage');
    Route::post('/branches/{id}/suspend', [BranchOperationsController::class, 'suspendBranch'])->middleware('permission:branches.manage');
    Route::post('/branches/{id}/activate', [BranchOperationsController::class, 'activateBranch'])->middleware('permission:branches.manage');
    Route::delete('/branches/{id}', [BranchOperationsController::class, 'safeDeleteBranch'])->middleware('permission:branches.manage');
    Route::get('/branches/{id}/details', [BranchOperationsController::class, 'getBranchDetails'])->middleware('permission:branches.view');
    Route::post('/branches/{id}/assessments', [BranchOperationsController::class, 'storeAssessment'])->middleware('permission:branches.manage');
    Route::post('/branches/{id}/coordinates', [BranchOperationsController::class, 'updateCoordinates'])->middleware('permission:branches.manage');
    Route::get('/branches/requests', [BranchOperationsController::class, 'getRequests'])->middleware('permission:branches.view');
    Route::post('/branches/requests', [BranchOperationsController::class, 'storeRequest'])->middleware('permission:branches.view');
    Route::patch('/branches/requests/{id}/status', [BranchOperationsController::class, 'updateRequestStatus'])->middleware('permission:branches.manage');
    Route::get('/branches/contracts', [BranchOperationsController::class, 'getContracts'])->middleware('permission:branches.view');

    // 9b. Properties & Buildings Module (العقارات والمقرات)
    Route::get('/properties', [\App\Http\Controllers\Api\PropertyContractController::class, 'listProperties'])->middleware('permission:branches.view');
    Route::post('/properties', [\App\Http\Controllers\Api\PropertyContractController::class, 'storeProperty'])->middleware('permission:branches.manage');
    Route::get('/properties/{id}', [\App\Http\Controllers\Api\PropertyContractController::class, 'showProperty'])->middleware('permission:branches.view');
    Route::put('/properties/{id}', [\App\Http\Controllers\Api\PropertyContractController::class, 'updateProperty'])->middleware('permission:branches.manage');
    Route::delete('/properties/{id}', [\App\Http\Controllers\Api\PropertyContractController::class, 'deleteProperty'])->middleware('permission:branches.manage');

    // 9c. Contracts Lifecycle & Financial Management (نظام العقود والمالية)
    Route::get('/contracts', [\App\Http\Controllers\Api\PropertyContractController::class, 'listContracts'])->middleware('permission:branches.view');
    Route::post('/contracts', [\App\Http\Controllers\Api\PropertyContractController::class, 'storeContract'])->middleware('permission:branches.manage');
    Route::get('/contracts/alerts', [\App\Http\Controllers\Api\PropertyContractController::class, 'getAlerts'])->middleware('permission:branches.view');
    Route::get('/contracts/{id}', [\App\Http\Controllers\Api\PropertyContractController::class, 'showContract'])->middleware('permission:branches.view');
    Route::post('/contracts/{id}/approve', [\App\Http\Controllers\Api\PropertyContractController::class, 'approveContract'])->middleware('permission:branches.manage');
    Route::post('/contracts/{id}/suspend', [\App\Http\Controllers\Api\PropertyContractController::class, 'suspendContract'])->middleware('permission:branches.manage');
    Route::post('/contracts/{id}/activate', [\App\Http\Controllers\Api\PropertyContractController::class, 'activateContract'])->middleware('permission:branches.manage');
    Route::post('/contracts/{id}/terminate', [\App\Http\Controllers\Api\PropertyContractController::class, 'terminateContract'])->middleware('permission:branches.manage');
    Route::post('/contracts/{id}/payments', [\App\Http\Controllers\Api\PropertyContractController::class, 'recordPayment'])->middleware('permission:branches.manage');

    // 9d. Financial Statements Engine
    Route::get('/financial/property/{id}', [\App\Http\Controllers\Api\PropertyContractController::class, 'getPropertyStatement'])->middleware('permission:branches.view');
    Route::get('/financial/branch/{id}', [\App\Http\Controllers\Api\PropertyContractController::class, 'getBranchStatement'])->middleware('permission:branches.view');
    Route::get('/financial/central', [\App\Http\Controllers\Api\PropertyContractController::class, 'getCentralStatement'])->middleware('permission:branches.view');

    // 10. Universal Command Palette Search (Ctrl + K)
    Route::get('/search/universal', [BranchOperationsController::class, 'universalSearch']);

    // 11. Courses & Curriculum Management
    Route::get('/courses', [\App\Http\Controllers\Api\CourseController::class, 'index'])->middleware('permission:curriculum.view');
    Route::post('/courses', [\App\Http\Controllers\Api\CourseController::class, 'store'])->middleware(['academic.year.active', 'permission:curriculum.manage']);
    Route::get('/courses/{id}', [\App\Http\Controllers\Api\CourseController::class, 'show'])->middleware('permission:curriculum.view');
    Route::post('/courses/{id}', [\App\Http\Controllers\Api\CourseController::class, 'update'])->middleware(['academic.year.active', 'permission:curriculum.manage']);
    Route::put('/courses/{id}', [\App\Http\Controllers\Api\CourseController::class, 'update'])->middleware(['academic.year.active', 'permission:curriculum.manage']);
    Route::delete('/courses/{id}', [\App\Http\Controllers\Api\CourseController::class, 'destroy'])->middleware(['academic.year.active', 'permission:curriculum.manage']);

    // 12. Central Settings & Academic Calendar
    Route::get('/settings/calendar-data', [\App\Http\Controllers\Api\SettingsController::class, 'getCalendarData'])->middleware('permission:windows.view|windows.manage');
    Route::post('/settings/update-year-dates', [\App\Http\Controllers\Api\SettingsController::class, 'updateYearDates'])->middleware('permission:windows.manage');
    Route::post('/settings/calendar-event', [\App\Http\Controllers\Api\SettingsController::class, 'addCalendarEvent'])->middleware('permission:windows.manage');
    Route::delete('/settings/calendar-event/{id}', [\App\Http\Controllers\Api\SettingsController::class, 'deleteCalendarEvent'])->middleware('permission:windows.manage');
    Route::post('/settings/schedule-event', [\App\Http\Controllers\Api\SettingsController::class, 'addScheduleEvent'])->middleware('permission:windows.manage');
    Route::delete('/settings/schedule-event/{id}', [\App\Http\Controllers\Api\SettingsController::class, 'deleteScheduleEvent'])->middleware('permission:windows.manage');
    Route::post('/settings/set-active-year', [\App\Http\Controllers\Api\SettingsController::class, 'setActiveYear'])->middleware('permission:windows.manage');
    Route::post('/settings/create-year', [\App\Http\Controllers\Api\SettingsController::class, 'createYear'])->middleware('permission:windows.manage');
    Route::post('/settings/academic-years', [\App\Http\Controllers\Api\SettingsController::class, 'createYear'])->middleware('permission:windows.manage');
    Route::put('/settings/years/{id}', [\App\Http\Controllers\Api\SettingsController::class, 'updateYear'])->middleware('permission:windows.manage');
    Route::get('/settings/years/{id}/inspect', [\App\Http\Controllers\Api\SettingsController::class, 'inspectYear'])->middleware('permission:windows.view|windows.manage');
    Route::delete('/settings/years/{id}', [\App\Http\Controllers\Api\SettingsController::class, 'deleteYear'])->middleware('permission:windows.manage');

    // Academic Year Engine & Lifecycle
    Route::post('/settings/activate-year', [\App\Http\Controllers\Api\SettingsController::class, 'activateYear'])->middleware('permission:windows.manage');
    Route::post('/settings/lock-year', [\App\Http\Controllers\Api\SettingsController::class, 'lockYear'])->middleware('permission:windows.manage');
    Route::post('/settings/duplicate-courses', [\App\Http\Controllers\Api\SettingsController::class, 'duplicateCourses'])->middleware('permission:windows.manage');
    Route::get('/settings/year-status', [\App\Http\Controllers\Api\SettingsController::class, 'getYearStatus'])->middleware('permission:windows.view|windows.manage');

    // Operational Windows Engine & Branch Exceptions
    Route::get('/settings/operational-windows', [\App\Http\Controllers\Api\SettingsController::class, 'getOperationalWindows'])->middleware('permission:windows.view|windows.manage');
    Route::post('/settings/operational-windows', [\App\Http\Controllers\Api\SettingsController::class, 'saveOperationalWindow'])->middleware('permission:windows.manage');
    Route::post('/settings/operational-windows/toggle', [\App\Http\Controllers\Api\SettingsController::class, 'toggleOperationalWindow'])->middleware('permission:windows.manage');
    Route::post('/settings/operational-windows/exception', [\App\Http\Controllers\Api\SettingsController::class, 'addWindowException'])->middleware('permission:windows.grant_exception|windows.manage');
    Route::delete('/settings/operational-windows/exception/{id}', [\App\Http\Controllers\Api\SettingsController::class, 'deleteWindowException'])->middleware('permission:windows.grant_exception|windows.manage');

    // Student Progression & Rollover Engine
    Route::post('/settings/progression/simulate', [\App\Http\Controllers\Api\SettingsController::class, 'simulateStudentProgression'])->middleware('permission:windows.manage');
    Route::post('/settings/progression/execute', [\App\Http\Controllers\Api\SettingsController::class, 'executeStudentProgression'])->middleware('permission:windows.manage');
    Route::get('/settings/progression/logs', [\App\Http\Controllers\Api\SettingsController::class, 'getRolloverLogs'])->middleware('permission:windows.view|windows.manage');

    Route::post('/settings/student-services', [\App\Http\Controllers\Api\SettingsController::class, 'saveStudentServices'])->middleware('permission:windows.manage');
    Route::post('/settings/admin-periods', [\App\Http\Controllers\Api\SettingsController::class, 'saveAdminPeriods'])->middleware('permission:windows.manage');
    Route::post('/settings/results-gateways', [\App\Http\Controllers\Api\SettingsController::class, 'updateResultsGateways'])->middleware('permission:windows.manage');
    Route::post('/settings/positions', [\App\Http\Controllers\Api\SettingsController::class, 'addPosition'])->middleware('permission:admin_settings.manage');
    Route::put('/settings/positions/{id}', [\App\Http\Controllers\Api\SettingsController::class, 'updatePosition'])->middleware('permission:admin_settings.manage');
    Route::delete('/settings/positions/{id}', [\App\Http\Controllers\Api\SettingsController::class, 'deletePosition'])->middleware('permission:admin_settings.manage');

    // 13. Study and Examinations Department (قسم الدراسة والامتحانات)
    Route::get('/study-and-exams/dashboard', [\App\Http\Controllers\Api\StudyAndExamsController::class, 'getDashboardData'])->middleware('permission:grades.view');

    // 14. Student Data Quality & Deficiency Audit Hub (جودة البيانات ونواقص الطلاب)
    Route::post('/students/data-quality/audit', [\App\Http\Controllers\Api\StudentDataQualityController::class, 'auditDeficiencies'])->middleware('permission:students.view');
    Route::get('/students/data-quality/export', [\App\Http\Controllers\Api\StudentDataQualityController::class, 'exportCsv'])->middleware('permission:students.view');

    // 15. Student Administrative & Academic Workflow Center (مركز قيادة وسير عمل طلبات الطلاب)
    Route::get('/student-workflow/summary', [\App\Http\Controllers\Api\StudentWorkflowController::class, 'getSummaryCounters'])->middleware('permission:students.view');
    Route::get('/student-workflow/requests', [\App\Http\Controllers\Api\StudentWorkflowController::class, 'getRequests'])->middleware('permission:students.view');
    Route::post('/student-workflow/status-request/{id}/action', [\App\Http\Controllers\Api\StudentWorkflowController::class, 'handleStatusAction'])->middleware('permission:CHANGE_STUDENT_STATUS|students.approve_hq');
    Route::post('/student-workflow/system-request/{id}/action', [\App\Http\Controllers\Api\StudentWorkflowController::class, 'handleSystemAction'])->middleware('permission:CHANGE_STUDENT_STATUS|students.approve_hq');
    Route::post('/student-workflow/transfer-request/{id}/step', [\App\Http\Controllers\Api\StudentWorkflowController::class, 'handleTransferStep'])->middleware('permission:students.transfer');
    Route::get('/student-workflow/requests/{type}/{id}/comments', [\App\Http\Controllers\Api\StudentWorkflowController::class, 'getComments'])->middleware('permission:students.view');
    Route::post('/student-workflow/requests/{type}/{id}/comments', [\App\Http\Controllers\Api\StudentWorkflowController::class, 'addComment'])->middleware('permission:students.view');
    Route::get('/student-workflow/transfers/export', [\App\Http\Controllers\Api\StudentWorkflowController::class, 'exportTransferFollowup'])->middleware('permission:students.view');
    Route::post('/student-workflow/submit', [\App\Http\Controllers\Api\StudentWorkflowController::class, 'submitBranchRequest'])->middleware('permission:students.view');

    // 16. Central Administrative Settings & Org Structure Engine (الإعدادات الإدارية المركزية والهيكل التنظيمي)
    Route::prefix('admin/settings')->group(function () {
        Route::get('/all', [\App\Http\Controllers\Api\AdminSettingsController::class, 'getMasterSettings'])->middleware('permission:admin_settings.view|admin_settings.manage');
        Route::post('/institute-profile', [\App\Http\Controllers\Api\AdminSettingsController::class, 'updateInstituteProfile'])->middleware('permission:admin_settings.manage');
        Route::post('/org-unit/save', [\App\Http\Controllers\Api\AdminSettingsController::class, 'saveOrgUnit'])->middleware('permission:admin_settings.manage');
        Route::delete('/org-unit/{id}', [\App\Http\Controllers\Api\AdminSettingsController::class, 'deleteOrgUnit'])->middleware('permission:admin_settings.manage');
        Route::post('/job-position/save', [\App\Http\Controllers\Api\AdminSettingsController::class, 'saveJobPosition'])->middleware('permission:admin_settings.manage');
        Route::delete('/job-position/{id}', [\App\Http\Controllers\Api\AdminSettingsController::class, 'deleteJobPosition'])->middleware('permission:admin_settings.manage');
        Route::post('/placement/save', [\App\Http\Controllers\Api\AdminSettingsController::class, 'saveEmployeePlacement'])->middleware('permission:admin_settings.manage');
        Route::delete('/placement/{id}', [\App\Http\Controllers\Api\AdminSettingsController::class, 'deleteEmployeePlacement'])->middleware('permission:admin_settings.manage');
        Route::post('/signatories/save', [\App\Http\Controllers\Api\AdminSettingsController::class, 'saveDocumentSignatories'])->middleware('permission:admin_settings.manage');
    });

    // =========================================================================
    // 17. Academic Structure, Stages, Departments & Classes (الهيكل الأكاديمي والمراحل والشعب)
    // =========================================================================
    Route::prefix('academic-structure')->group(function () {
        Route::get('/overview', [\App\Http\Controllers\Api\AcademicStructureController::class, 'getOverview'])->middleware('permission:curriculum.view');

        // Study Years / Stages
        Route::post('/study-years', [\App\Http\Controllers\Api\AcademicStructureController::class, 'storeStudyYear'])->middleware('permission:curriculum.manage');
        Route::put('/study-years/{id}', [\App\Http\Controllers\Api\AcademicStructureController::class, 'updateStudyYear'])->middleware('permission:curriculum.manage');
        Route::delete('/study-years/{id}', [\App\Http\Controllers\Api\AcademicStructureController::class, 'destroyStudyYear'])->middleware('permission:curriculum.manage');

        // Departments & Majors
        Route::post('/departments', [\App\Http\Controllers\Api\AcademicStructureController::class, 'storeDepartment'])->middleware('permission:curriculum.manage');
        Route::put('/departments/{id}', [\App\Http\Controllers\Api\AcademicStructureController::class, 'updateDepartment'])->middleware('permission:curriculum.manage');
        Route::post('/departments/{id}/toggle-status', [\App\Http\Controllers\Api\AcademicStructureController::class, 'toggleDepartmentStatus'])->middleware('permission:curriculum.manage');
        Route::delete('/departments/{id}', [\App\Http\Controllers\Api\AcademicStructureController::class, 'destroyDepartment'])->middleware('permission:curriculum.manage');

        // Branch Classes & Sections
        Route::post('/classes', [\App\Http\Controllers\Api\AcademicStructureController::class, 'storeBranchClass'])->middleware('permission:curriculum.manage');
        Route::put('/classes/{id}', [\App\Http\Controllers\Api\AcademicStructureController::class, 'updateBranchClass'])->middleware('permission:curriculum.manage');
        Route::delete('/classes/{id}', [\App\Http\Controllers\Api\AcademicStructureController::class, 'destroyBranchClass'])->middleware('permission:curriculum.manage');
    });

    // =========================================================================
    // 18. System Error Monitoring Center (مركز مراقبة أخطاء النظام - حصري للمستخدم الرئيسي)
    // =========================================================================
    Route::prefix('system-errors')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\SystemErrorMonitoringController::class, 'index'])->middleware('permission:system.monitor');
        Route::get('/{id}', [\App\Http\Controllers\Api\SystemErrorMonitoringController::class, 'show'])->where('id', '[0-9]+')->middleware('permission:system.monitor');
        Route::post('/{id}/status', [\App\Http\Controllers\Api\SystemErrorMonitoringController::class, 'updateStatus'])->where('id', '[0-9]+')->middleware('permission:system.monitor');
        Route::post('/trigger-test', [\App\Http\Controllers\Api\SystemErrorMonitoringController::class, 'triggerTestError'])->middleware('permission:system.monitor');
        Route::delete('/clear-test-logs', [\App\Http\Controllers\Api\SystemErrorMonitoringController::class, 'clearTestLogs'])->middleware('permission:system.monitor');
    });

    // 19. User Profile & Account Management
    Route::get('/user/profile', [\App\Http\Controllers\Api\UserProfileController::class, 'getProfile']);
    Route::post('/user/profile', [\App\Http\Controllers\Api\UserProfileController::class, 'updateProfile']);
    Route::post('/user/password', [\App\Http\Controllers\Api\UserProfileController::class, 'updatePassword']);
    Route::post('/user/2fa/toggle', [\App\Http\Controllers\Api\UserProfileController::class, 'toggleTwoFactor']);

    // 20. Enterprise System Health & Ops Monitoring
    Route::get('/health', [\App\Http\Controllers\Api\SystemHealthController::class, 'check']);

    // 21. System Changelog (سجل الإصدارات)
    Route::get('/changelog', [\App\Http\Controllers\Api\SystemChangelogController::class, 'index']);
    Route::post('/changelog', [\App\Http\Controllers\Api\SystemChangelogController::class, 'store'])->middleware('permission:system.monitor');
    Route::patch('/changelog/{changelog}', [\App\Http\Controllers\Api\SystemChangelogController::class, 'update'])->middleware('permission:system.monitor');
    Route::delete('/changelog/{changelog}', [\App\Http\Controllers\Api\SystemChangelogController::class, 'destroy'])->middleware('permission:system.monitor');

    // 22. Bug Reports (بلاغات الأخطاء)
    Route::get('/bug-reports', [\App\Http\Controllers\Api\BugReportController::class, 'index']);
    Route::post('/bug-reports', [\App\Http\Controllers\Api\BugReportController::class, 'store']);
    Route::patch('/bug-reports/{bugReport}', [\App\Http\Controllers\Api\BugReportController::class, 'update'])->middleware('permission:system.monitor');
    Route::delete('/bug-reports/{bugReport}', [\App\Http\Controllers\Api\BugReportController::class, 'destroy'])->middleware('permission:system.monitor');

    // =========================================================================
    // 23. Enterprise Backup & Disaster Recovery (منظومة النسخ الاحتياطي المشفر)
    // =========================================================================
    Route::prefix('backups')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\BackupController::class, 'index'])->middleware('permission:backups.manage');
        Route::post('/manual', [\App\Http\Controllers\Api\BackupController::class, 'createManualBackup'])->middleware('permission:backups.manage');
        Route::get('/{id}/download', [\App\Http\Controllers\Api\BackupController::class, 'downloadBackup'])->where('id', '[0-9]+')->middleware('permission:backups.manage');
        Route::get('/{id}/verify', [\App\Http\Controllers\Api\BackupController::class, 'verifyIntegrity'])->where('id', '[0-9]+')->middleware('permission:backups.manage');
        Route::post('/{id}/restore', [\App\Http\Controllers\Api\BackupController::class, 'restoreBackup'])->where('id', '[0-9]+')->middleware('permission:backups.manage');
        Route::delete('/{id}', [\App\Http\Controllers\Api\BackupController::class, 'destroy'])->where('id', '[0-9]+')->middleware('permission:backups.manage');
    });
});

