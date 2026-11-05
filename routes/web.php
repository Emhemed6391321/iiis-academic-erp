<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\Auth\LoginController;

// ============================================================
// Authentication Routes (Enterprise Secure Login/Logout)
// ============================================================

// Guest-only routes: authenticated users are redirected to dashboard
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->name('login.post');
});

// Logout — supports both POST (secure forms) and GET (direct link / browser URL)
Route::match(['get', 'post'], '/logout', [LoginController::class, 'logout'])
    ->name('logout');

// ============================================================
// Security & Verification Routes (Password Policy)
// ============================================================
Route::middleware('auth')->group(function () {

    Route::get('/password/change', [\App\Http\Controllers\Auth\PasswordChangeController::class, 'showChangeForm'])->name('password.change');
    Route::post('/password/update', [\App\Http\Controllers\Auth\PasswordChangeController::class, 'updatePassword'])->name('password.update');
});

// ============================================================
// Protected Application Routes (auth + password check)
// ============================================================
Route::middleware(['auth', 'force.password_change'])->group(function () {

    Route::get('/', function (Request $request) {
        $allAcademicYears = \App\Models\AcademicYear::orderBy('id', 'desc')->get();
        $currentAcademicYear = \App\Models\AcademicYear::where('is_current', true)->first() ?: $allAcademicYears->first();

        return view('dashboard.index', [
            'initialSection'      => $request->query('section', 'dashboard'),
            'initialAction'       => $request->query('action', ''),
            'initialCourseId'     => $request->query('course_id', $request->query('id', 1)),
            'initialSettingsTab'  => $request->query('tab', 'calendar'),
            'allAcademicYears'    => $allAcademicYears,
            'currentAcademicYear' => $currentAcademicYear,
            'instituteProfile'    => \App\Services\AdminSettingsService::getInstituteProfile(),
        ]);
    })->name('dashboard');

    Route::get('/settings', function (Request $request) {
        $tab = $request->query('tab', 'calendar');
        return redirect('/?section=settings&tab=' . urlencode($tab));
    });

    Route::get('/settings.php', function (Request $request) {
        $tab = $request->query('tab', 'calendar');
        return redirect('/?section=settings&tab=' . urlencode($tab));
    });

    Route::get('/edit_course', function (Request $request) {
        $id = $request->query('course_id', $request->query('id', 1));
        return redirect('/?section=curriculum&course_id=' . urlencode($id));
    });

    Route::get('/edit_course.php', function (Request $request) {
        $id = $request->query('course_id', $request->query('id', 1));
        return redirect('/?section=curriculum&course_id=' . urlencode($id));
    });

    Route::get('/study_and_exams', function (Request $request) {
        return redirect('/?section=study_and_exams');
    });

    Route::get('/study_and_exams.php', function (Request $request) {
        return redirect('/?section=study_and_exams');
    });

    Route::get('/data_quality', function (Request $request) {
        return redirect('/?section=data_quality');
    });

    Route::get('/data_quality.php', function (Request $request) {
        return redirect('/?section=data_quality');
    });

    Route::get('/student_workflow', function (Request $request) {
        return redirect('/?section=student_workflow');
    });

    Route::get('/student_workflow.php', function (Request $request) {
        return redirect('/?section=student_workflow');
    });

    Route::get('/branches', function (Request $request) {
        return redirect('/?section=branches_directory');
    });

    Route::get('/branches.php', function (Request $request) {
        return redirect('/?section=branches_directory');
    });

    Route::get('/branches_directory', function (Request $request) {
        return redirect('/?section=branches_directory');
    });

    Route::get('/add_branch', function (Request $request) {
        return redirect('/?section=branches_directory&action=new_branch');
    });

    Route::get('/add_branch.php', function (Request $request) {
        return redirect('/?section=branches_directory&action=new_branch');
    });
});

// ============================================================
// Public Cryptographic Document Verification Route (No Auth Required)
// ============================================================
Route::get('/verify/{uuid}', [\App\Http\Controllers\PublicVerificationController::class, 'show'])
    ->middleware('throttle:15,1')
    ->name('document.verify');


// ============================================================
// بوابة الأخطاء المخفية: مسار سري + كلمة سر مستقلة عن حسابات النظام.
// لا تُسجَّل المسارات إطلاقاً ما لم يُضبط ERROR_PORTAL_PASSWORD_HASH (فتعطي 404).
// ============================================================
if (config('error_portal.password_hash') !== '' && config('error_portal.path') !== '') {
    Route::prefix(config('error_portal.path'))->name('error-portal.')->group(function () {
        Route::get('/login', [\App\Http\Controllers\ErrorPortalController::class, 'showLogin'])->name('login');
        Route::post('/login', [\App\Http\Controllers\ErrorPortalController::class, 'login'])->name('login.post');

        Route::middleware(\App\Http\Middleware\EnsureErrorPortalUnlocked::class)->group(function () {
            Route::get('/', [\App\Http\Controllers\ErrorPortalController::class, 'index'])->name('index');
            Route::get('/logs/{log}', [\App\Http\Controllers\ErrorPortalController::class, 'show'])->name('show');
            Route::post('/logs/{log}/status', [\App\Http\Controllers\ErrorPortalController::class, 'updateStatus'])->name('status');
            Route::post('/logout', [\App\Http\Controllers\ErrorPortalController::class, 'logout'])->name('logout');
        });
    });
}
