<?php

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Models\Course;
use App\Models\User;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\ModuleController;
use App\Http\Controllers\QuizController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\LeaderboardController;
use App\Http\Controllers\AchievementController;
use App\Http\Controllers\ProgressController;
use App\Http\Controllers\OnboardingController; // <--- [BARU] Import Controller Onboarding

use App\Http\Controllers\AdminController;
use App\Http\Controllers\Admin\SubmissionController;
use App\Http\Controllers\Admin\TaskSubmissionController;

use Illuminate\Http\Request;

Route::get('/', function () {
    return redirect()->route('login');
});

// API Notifikasi (Internal)
Route::get('/notifications', [NotificationController::class, 'index'])
    ->middleware('auth');
Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])
    ->middleware('auth');
Route::patch('/notifications/{id}/read', [NotificationController::class, 'markOneAsRead']);

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

// GROUP AUTH UMUM (Profile & Onboarding)
Route::middleware('auth')->group(function () {
    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])
        ->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])
        ->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])
        ->name('profile.destroy');

    // [BARU] Onboarding - Update Departemen
    // Route ini menangani submit dari Modal Onboarding
    Route::put('/onboarding/department', [OnboardingController::class, 'update'])
        ->name('onboarding.department.update');
});

Route::post('/check-email-exists', function (Request $request) {
    $exists = User::where('email', $request->email)->exists();
    return response()->json(['exists' => $exists]);
});

// ROUTE PESERTA
// MAIN COURSE
Route::get('/course/{id}', [CourseController::class, 'show'])
    ->name('course.show');
Route::get('/my-courses', [CourseController::class, 'index'])
    ->name('my-courses');
Route::post('/course/{id}/enroll', [CourseController::class, 'enroll'])
    ->name('course.enroll');

// MAIN MODULE
Route::get('/module/{id}/read', [ModuleController::class, 'show'])
    ->name('module.show');
Route::post('/module/{id}/complete', [ModuleController::class, 'markAsComplete'])
    ->middleware(['auth'])
    ->name('module.complete');
Route::post('/module/{id}/progress', [ModuleController::class, 'updateProgress'])
    ->name('module.progress');

// MAIN QUIZ
Route::get('/quiz/{id}', [QuizController::class, 'show'])
    ->name('quiz.show');
Route::post('/quiz/{id}/submit', [QuizController::class, 'submit'])
    ->name('quiz.submit');
Route::get('/quiz/{id}/result', [QuizController::class, 'result'])
    ->name('quiz.result');

// MAIN TASK
Route::get('/task/{id}', [TaskController::class, 'show'])
    ->name('task.show');
Route::post('/task/{id}/submit', [TaskController::class, 'submit'])
    ->name('task.submit');
Route::get('/my-tasks', [TaskController::class, 'history'])
    ->name('my-tasks');

// MAIN LEADERBOARD
Route::get('/leaderboard', [LeaderboardController::class, 'index'])->name('leaderboard');

// MAIN ACHIEVEMENTS
Route::get('/achievements', [AchievementController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('achievements');

// MAIN PROGRESS
Route::get('/progress', [ProgressController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('progress');

// Group Route Admin
Route::middleware(['auth', 'verified', 'role:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/dashboard', [AdminController::class, 'dashboard'])
            ->name('dashboard');
        Route::resource('users', \App\Http\Controllers\Admin\UserController::class);
        Route::patch('/users/{user}/toggle-status', [\App\Http\Controllers\Admin\UserController::class, 'toggleStatus'])
            ->name('users.toggle-status');
        Route::resource('departments', \App\Http\Controllers\Admin\DepartmentController::class);
        Route::resource('courses', \App\Http\Controllers\Admin\CourseController::class);
        Route::resource('gamification', \App\Http\Controllers\Admin\GamificationController::class)
            ->except(['show', 'create', 'edit']);
    });

// Group Route Shared Admin & Mentor
Route::middleware(['auth', 'verified', 'role:admin,mentor'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::post('/courses/{course}/content', [\App\Http\Controllers\Admin\CourseContentController::class, 'store'])
            ->name('courses.content.store');
        Route::put('/courses/{course}/reorder', [\App\Http\Controllers\Admin\CourseContentController::class, 'reorder'])
            ->name('courses.reorder');
        Route::put('/modules/{module}', [\App\Http\Controllers\Admin\CourseContentController::class, 'update'])
            ->name('modules.update');
        Route::delete('/modules/{module}', [\App\Http\Controllers\Admin\CourseContentController::class, 'destroy'])
            ->name('modules.destroy');
        Route::get('/quizzes/{quiz}/builder', [\App\Http\Controllers\Admin\QuestionController::class, 'index'])
            ->name('quizzes.builder');
        Route::post('/quizzes/{quiz}/questions', [\App\Http\Controllers\Admin\QuestionController::class, 'store'])
            ->name('quizzes.questions.store');
        Route::put('/questions/{question}', [\App\Http\Controllers\Admin\QuestionController::class, 'update'])
            ->name('questions.update');
        Route::delete('/questions/{question}', [\App\Http\Controllers\Admin\QuestionController::class, 'destroy'])
            ->name('questions.destroy');
        Route::get('/submissions', [SubmissionController::class, 'index'])
            ->name('submissions.index');
        Route::put('/submissions/{id}/grade', [TaskSubmissionController::class, 'update'])
            ->name('submissions.grade');
        Route::get('/reports', [\App\Http\Controllers\Admin\ReportController::class, 'index'])
            ->name('reports.index');
        Route::get('/reports/export', [\App\Http\Controllers\Admin\ReportController::class, 'export'])
            ->name('reports.export');
        Route::get('/reports/{user}/export', [\App\Http\Controllers\Admin\ReportController::class, 'exportUser'])
            ->name('reports.export.user');
        Route::get('/reports/{user}/details', [\App\Http\Controllers\Admin\ReportController::class, 'showUser'])
            ->name('reports.user.details');
    });

require __DIR__ . '/auth.php';

// Group Route Mentor
Route::middleware(['auth', 'verified', 'role:mentor'])
    ->prefix('mentor')
    ->name('mentor.')
    ->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\MentorController::class, 'dashboard'])
            ->name('dashboard');
        Route::get('/my-courses', [\App\Http\Controllers\MentorController::class, 'myCourses'])
            ->name('courses.index');
        Route::post('/my-courses', [\App\Http\Controllers\MentorController::class, 'storeCourse'])
            ->name('courses.store');
        Route::get('/my-courses/{course}/edit', [\App\Http\Controllers\MentorController::class, 'editCourse'])
            ->name('courses.edit');
        Route::put('/my-courses/{course}', [\App\Http\Controllers\MentorController::class, 'updateCourse'])
            ->name('courses.update');
        Route::delete('/my-courses/{course}', [\App\Http\Controllers\MentorController::class, 'destroyCourse'])
            ->name('courses.destroy');
        Route::get('/reports', [\App\Http\Controllers\MentorController::class, 'reports'])
            ->name('reports.index');
        Route::get('/mentor/tasks', [App\Http\Controllers\Admin\TaskSubmissionController::class, 'index'])
            ->name('tasks.index');
        Route::put('/mentor/tasks/{id}', [App\Http\Controllers\Admin\TaskSubmissionController::class, 'update'])
            ->name('tasks.update');
        Route::get('/mentor/reports/student/{id}', [App\Http\Controllers\MentorController::class, 'showStudentReport'])
            ->name('reports.show');
    });
