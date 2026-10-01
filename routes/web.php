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

// Logout — requires active session
Route::post('/logout', [LoginController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

// ============================================================
// Protected Application Routes (auth middleware)
// ============================================================

Route::middleware('auth')->group(function () {

    Route::get('/', function (Request $request) {
        return view('dashboard', [
            'initialSection'    => $request->query('section', 'dashboard'),
            'initialAction'     => $request->query('action', ''),
            'initialCourseId'   => $request->query('course_id', $request->query('id', 1)),
            'initialSettingsTab'=> $request->query('tab', 'calendar'),
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
Route::get('/verify/{uuid}', [\App\Http\Controllers\PublicVerificationController::class, 'show'])->name('document.verify');

