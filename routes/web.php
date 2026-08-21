<?php

use App\Http\Controllers\Admin\AnnouncementController as AdminAnnouncementController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\SystemController as AdminSystemController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Coordinator\DashboardController as CoordinatorDashboardController;
use App\Http\Controllers\Coordinator\ReportController as CoordinatorReportController;
use App\Http\Controllers\Employer\ApplicantController;
use App\Http\Controllers\Employer\DashboardController as EmployerDashboardController;
use App\Http\Controllers\Employer\InternshipController as EmployerInternshipController;
use App\Http\Controllers\Employer\ProfileController as EmployerProfileController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\Student\CompetencyController;
use App\Http\Controllers\Student\DashboardController as StudentDashboardController;
use App\Http\Controllers\Student\InternshipController as StudentInternshipController;
use App\Http\Controllers\Student\PortfolioController;
use App\Http\Controllers\Student\ProfileController as StudentProfileController;
use App\Http\Controllers\Student\ResumeController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::middleware('guest')->group(function () {
    // LOGIN
    Route::get('/login', [LoginController::class, 'showLoginForm'])
        ->name('login');

    Route::post('/login', [LoginController::class, 'login'])
        ->middleware('throttle:login');

    // REGISTER
    Route::get('/register', [RegisterController::class, 'showRegistrationForm'])
        ->name('register');

    Route::post('/register', [RegisterController::class, 'register'])
        ->name('register.store');
    Route::get('/forgot-password', [ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
    Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLinkEmail'])->name('password.email')->middleware('throttle:password');
    Route::get('/reset-password/{token}', [ResetPasswordController::class, 'showResetForm'])->name('password.reset');
    Route::post('/reset-password', [ResetPasswordController::class, 'reset'])->name('password.update')->middleware('throttle:password');
});

Route::post('/logout', [LoginController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/search', [SearchController::class, 'index'])->name('search');
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
    Route::get('/api/notifications/unread', [NotificationController::class, 'unreadJson'])->name('notifications.unread');
    Route::get('/messages', [MessageController::class, 'index'])->name('messages.index');
    Route::get('/messages/{user}', [MessageController::class, 'show'])->name('messages.show');
    Route::post('/messages/{user}', [MessageController::class, 'store'])->name('messages.store');
    Route::get('/api/analytics/charts', [AnalyticsController::class, 'chartData'])->name('analytics.charts');

    Route::prefix('student')->name('student.')->middleware('role:student')->group(function () {
        Route::get('/dashboard', [StudentDashboardController::class, 'index'])->name('dashboard');
        Route::get('/profile', [\App\Http\Controllers\Student\ProfileController::class, 'edit'])->name('profile.edit');
        Route::put('/profile', [\App\Http\Controllers\Student\ProfileController::class, 'update'])->name('profile.update');
        Route::delete('/profile-picture', [\App\Http\Controllers\Student\ProfileController::class, 'deleteProfilePicture'])->name('profile.delete-picture');
        Route::post('/profile-picture', [\App\Http\Controllers\Student\ProfileController::class, 'updatePicture'])->name('profile.update-picture');
        // Competencies
        Route::get('/competencies', [CompetencyController::class, 'index'])->name('competencies.index');
        Route::get('/competencies/create', [CompetencyController::class, 'create'])->name('competencies.create');
        Route::post('/competencies', [CompetencyController::class, 'store'])->name('competencies.store');
        Route::get('/competencies/{competency}', [CompetencyController::class, 'show'])->name('competencies.show');
        Route::get('/competencies/{competency}/edit', [CompetencyController::class, 'edit'])->name('competencies.edit');
        Route::put('/competencies/{competency}', [CompetencyController::class, 'update'])->name('competencies.update');
        Route::delete('/competencies/{competency}', [CompetencyController::class, 'destroy'])->name('competencies.destroy');

        // Portfolio
        Route::get('/portfolio', [PortfolioController::class, 'index'])->name('portfolio.index');
        Route::get('/portfolio/create', [PortfolioController::class, 'create'])->name('portfolio.create');
        Route::post('/portfolio', [PortfolioController::class, 'storePortfolio'])->name('portfolio.store');
        Route::get('/portfolio/{portfolio}', [PortfolioController::class, 'show'])->name('portfolio.show');
        Route::get('/portfolio/{portfolio}/edit', [PortfolioController::class, 'edit'])->name('portfolio.edit');
        Route::put('/portfolio/{portfolio}', [PortfolioController::class, 'update'])->name('portfolio.update');
        Route::get('/portfolio/{portfolio}/download', [PortfolioController::class, 'download'])->name('portfolio.download');
        Route::get('/portfolio/{portfolio}/preview', [PortfolioController::class, 'preview'])->name('portfolio.preview');
        Route::delete('/portfolio/{portfolio}', [PortfolioController::class, 'destroyPortfolio'])->name('portfolio.destroyPortfolio');

        // Certificates
        Route::post('/certificates', [PortfolioController::class, 'storeCertificate'])->name('certificates.store');
        Route::delete('/certificates/{certificate}', [PortfolioController::class, 'destroyCertificate'])->name('certificates.destroy');
        Route::get('/resume', [ResumeController::class, 'index'])->name('resume.index');
        Route::post('/resume/generate', [ResumeController::class, 'generate'])->name('resume.generate');
        Route::get('/resume/{resume}/view', [ResumeController::class, 'view'])->name('resume.view');
        Route::get('/resume/{resume}/download', [ResumeController::class, 'download'])->name('resume.download');
        Route::get('/internships', [StudentInternshipController::class, 'index'])->name('internships.index');
        Route::get('/internships/{internship}', [StudentInternshipController::class, 'show'])->name('internships.show');
        Route::post('/internships/{internship}/apply', [StudentInternshipController::class, 'apply'])->name('internships.apply');
        Route::get('/applications', [StudentInternshipController::class, 'applications'])->name('applications.index');
        Route::get('/applications/{application}', [StudentInternshipController::class, 'showApplication'])->name('applications.show');
        Route::get('/applications/{application}/edit', [StudentInternshipController::class, 'editApplication'])->name('applications.edit');
        Route::put('/applications/{application}', [StudentInternshipController::class, 'updateApplication'])->name('applications.update');
        Route::delete('/applications/{application}', [StudentInternshipController::class, 'destroyApplication'])->name('applications.destroy');
    });

    Route::prefix('employer')->name('employer.')->middleware('role:employer')->group(function () {
        Route::get('/dashboard', [EmployerDashboardController::class, 'index'])->name('dashboard');
        Route::get('/profile', [\App\Http\Controllers\Employer\ProfileController::class, 'edit'])->name('profile.edit');
        Route::put('/profile', [\App\Http\Controllers\Employer\ProfileController::class, 'update'])->name('profile.update');
        Route::delete('/company-logo', [\App\Http\Controllers\Employer\ProfileController::class, 'deleteCompanyLogo'])->name('profile.delete-logo');
        Route::resource('internships', EmployerInternshipController::class)->except(['show']);
        Route::post('/internships/{internship}/close', [EmployerInternshipController::class, 'close'])->name('internships.close');
        Route::get('/applicants', [ApplicantController::class, 'index'])->name('applicants.index');
        Route::get('/applicants/{application}', [ApplicantController::class, 'show'])->name('applicants.show');
        Route::put('/applicants/{application}/status', [ApplicantController::class, 'updateStatus'])->name('applicants.status');
        Route::get('/applicants/{application}/resume', [ApplicantController::class, 'downloadResume'])->name('applicants.resume');
    });

    Route::prefix('coordinator')->name('coordinator.')->middleware('role:coordinator')->group(function () {
        Route::get('/dashboard', [CoordinatorDashboardController::class, 'index'])->name('dashboard');
        Route::get('/profile', [\App\Http\Controllers\Coordinator\ProfileController::class, 'edit'])->name('profile.edit');
        Route::put('/profile', [\App\Http\Controllers\Coordinator\ProfileController::class, 'update'])->name('profile.update');
        Route::delete('/profile-picture', [\App\Http\Controllers\Coordinator\ProfileController::class, 'deleteProfilePicture'])->name('profile.delete-picture');
        Route::get('/students', [CoordinatorDashboardController::class, 'students'])->name('students.index');
        Route::get('/students/{student}', [CoordinatorDashboardController::class, 'showStudent'])->name('students.show');
        Route::get('/reports', [CoordinatorReportController::class, 'index'])->name('reports.index');
        Route::post('/reports/generate', [CoordinatorReportController::class, 'generate'])->name('reports.generate');
    });

    Route::prefix('admin')->name('admin.')->middleware('role:admin')->group(function () {
        Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
        Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
        Route::get('/users/create', [AdminUserController::class, 'create'])->name('users.create');
        Route::post('/users', [AdminUserController::class, 'store'])->name('users.store');
        Route::put('/users/{user}', [AdminUserController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [AdminUserController::class, 'destroy'])->name('users.destroy');
        Route::get('/announcements', [AdminAnnouncementController::class, 'index'])->name('announcements.index');
        Route::post('/announcements', [AdminAnnouncementController::class, 'store'])->name('announcements.store');
        Route::delete('/announcements/{announcement}', [AdminAnnouncementController::class, 'destroy'])->name('announcements.destroy');
        Route::get('/logs', [AdminSystemController::class, 'logs'])->name('logs');
        Route::match(['get', 'post'], '/reports', [AdminSystemController::class, 'reports'])->name('reports');
        Route::post('/backup', [AdminSystemController::class, 'backup'])->name('backup');
    });
});
