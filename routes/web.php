<?php

use App\Http\Controllers\Admin\AnnouncementController as AdminAnnouncementController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\HealthController;
use App\Http\Controllers\Admin\ModerationController;
use App\Http\Controllers\Admin\PlacementController as AdminPlacementController;
use App\Http\Controllers\Admin\PortfolioFileController;
use App\Http\Controllers\Admin\ProfileController as AdminProfileController;
use App\Http\Controllers\Admin\SkillTagController;
use App\Http\Controllers\Admin\StudentImportController;
use App\Http\Controllers\Admin\SystemController as AdminSystemController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\AppraisalController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\ForcePasswordChangeController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\CompanyInvitationController;
use App\Http\Controllers\ContentReportController;
use App\Http\Controllers\Coordinator\ApplicationMonitorController;
use App\Http\Controllers\Coordinator\CompanyController;
use App\Http\Controllers\Coordinator\DashboardController as CoordinatorDashboardController;
use App\Http\Controllers\Coordinator\EligibilityCriteriaController;
use App\Http\Controllers\Coordinator\EligibilityDecisionController;
use App\Http\Controllers\Coordinator\PlacementController as CoordinatorPlacementController;
use App\Http\Controllers\Coordinator\ReviewController;
use App\Http\Controllers\Coordinator\StatisticsController;
use App\Http\Controllers\Coordinator\StudentProfileReviewController;
use App\Http\Controllers\Coordinator\VerificationController;
use App\Http\Controllers\Employer\ApplicantController;
use App\Http\Controllers\Employer\DashboardController as EmployerDashboardController;
use App\Http\Controllers\Employer\InternController as EmployerInternController;
use App\Http\Controllers\Employer\InternshipController as EmployerInternshipController;
use App\Http\Controllers\Employer\ProfileController as EmployerProfileController;
use App\Http\Controllers\Employer\ShortlistController;
use App\Http\Controllers\Employer\StudentBrowseController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OjtCertificateController;
use App\Http\Controllers\OjtFormPrintController;
use App\Http\Controllers\ProfileCompletionController;
use App\Http\Controllers\PublicInternshipController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\Student\CharacterReferenceController;
use App\Http\Controllers\Student\CompetencyController;
use App\Http\Controllers\Student\DashboardController as StudentDashboardController;
use App\Http\Controllers\Student\ExperienceController;
use App\Http\Controllers\Student\IdPhotoController;
use App\Http\Controllers\Student\InternshipController as StudentInternshipController;
use App\Http\Controllers\Student\InvitationController;
use App\Http\Controllers\Student\OjtController as StudentOjtController;
use App\Http\Controllers\Student\PortfolioController;
use App\Http\Controllers\Student\ProfileController;
use App\Http\Controllers\Student\ProfileController as StudentProfileController;
use App\Http\Controllers\Student\ResumeController;
use App\Http\Controllers\Student\StudentAwardController;
use App\Http\Controllers\Student\StudentChangeRequestController;
use App\Http\Controllers\Student\StudentSkillController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PublicInternshipController::class, 'home'])->name('home');

// Public, read-only internship listings. "Apply" is only an entry point: guests are sent to login
// (and back afterwards); the real apply flow stays behind auth + role:student under /student.
Route::get('/internships', [PublicInternshipController::class, 'index'])->name('internships.index');
Route::get('/internships/{internship}', [PublicInternshipController::class, 'show'])->name('internships.show');

// Public information pages (static copy, no data access).
Route::view('/how-it-works', 'public.pages.how')->name('pages.how');
Route::view('/students', 'public.pages.role', ['role' => 'students'])->name('pages.students');
Route::view('/coordinators', 'public.pages.role', ['role' => 'coordinators'])->name('pages.coordinators');
Route::view('/faqs', 'public.pages.faqs')->name('pages.faqs');
Route::view('/privacy', 'public.pages.privacy')->name('pages.privacy'); // privacy notice (RA 10173), linked from the footer and sign-in
Route::get('/internships/{internship}/apply', [PublicInternshipController::class, 'apply'])->name('internships.apply');

Route::middleware('guest')->group(function () {
    // LOGIN
    Route::get('/login', [LoginController::class, 'showLoginForm'])
        ->name('login');

    Route::post('/login', [LoginController::class, 'login'])
        ->middleware('throttle:login');

    // Second step of sign-in (emailed code or recovery code); the password was already accepted.
    Route::get('/two-factor', [App\Http\Controllers\Auth\TwoFactorChallengeController::class, 'show'])->name('two-factor.challenge');
    Route::post('/two-factor', [App\Http\Controllers\Auth\TwoFactorChallengeController::class, 'verify'])->middleware('throttle:two-factor-verify')->name('two-factor.verify');
    Route::post('/two-factor/resend', [App\Http\Controllers\Auth\TwoFactorChallengeController::class, 'resend'])->middleware('throttle:two-factor-resend')->name('two-factor.resend');

    Route::get('/forgot-password', [ForgotPasswordController::class, 'showResetForm'])->name('password.request');
    Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetCode'])->name('password.email')->middleware('throttle:password-reset-request');
    Route::post('/reset-password', [ResetPasswordController::class, 'reset'])->name('password.update')->middleware('throttle:password-reset-submit');

    // A company completes its account from a coordinator's invitation link.
    Route::middleware('throttle:company-invitation')->group(function () {
        Route::get('/company-invitation/{token}', [CompanyInvitationController::class, 'show'])
            ->where('token', '[A-Za-z0-9]+')->name('company-invitations.show');
        Route::post('/company-invitation/{token}', [CompanyInvitationController::class, 'store'])
            ->where('token', '[A-Za-z0-9]+')->name('company-invitations.accept');
        // A supervisor sets their password from the emailed link.
        Route::get('/supervisor-setup/{token}', [App\Http\Controllers\SupervisorSetupController::class, 'show'])
            ->where('token', '[A-Za-z0-9]+')->name('supervisor.setup');
        Route::post('/supervisor-setup/{token}', [App\Http\Controllers\SupervisorSetupController::class, 'store'])
            ->where('token', '[A-Za-z0-9]+')->name('supervisor.setup.store');
    });
});

Route::post('/logout', [LoginController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

Route::middleware(['auth', 'active'])->group(function () {
    // Front-end-only preview of the Gmail -> code -> new password setup flow (mock functions, no backend).
    if (! app()->isProduction()) {
        Route::view('/account-setup', 'auth.account-setup')->name('account-setup');
    }

    // Step 1-2 of first login: add and verify the email. This must come before the new password.
    Route::get('/email/verify', [EmailVerificationController::class, 'notice'])
        ->name('verification.notice');
    Route::post('/email/verify', [EmailVerificationController::class, 'verify'])
        ->middleware('throttle:6,1')
        ->name('verification.verify');
    Route::post('/email/verify/address', [EmailVerificationController::class, 'storeEmail'])
        ->middleware('throttle:5,1')
        ->name('verification.email');
    Route::post('/email/verify/resend', [EmailVerificationController::class, 'resend'])
        ->middleware('throttle:1,1')
        ->name('verification.resend');

    // Idle sign-out (App\Http\Middleware\EndIdleSession): the page asks how long is left, and "Stay signed in".
    Route::get('/session/idle-status', fn (\Illuminate\Http\Request $request) => response()->json(['seconds_left' => \App\Http\Middleware\EndIdleSession::secondsLeft($request)]))
        ->name('session.idle-status');
    Route::post('/session/keep-alive', fn (\Illuminate\Http\Request $request) => response()->json(['seconds_left' => \App\Http\Middleware\EndIdleSession::secondsLeft($request)]))
        ->middleware('throttle:session-keep-alive')
        ->name('session.keep-alive');

    Route::get('/password/force-change', [ForcePasswordChangeController::class, 'edit'])
        ->name('password.force-change');
    Route::put('/password/force-change', [ForcePasswordChangeController::class, 'update'])
        ->middleware('throttle:password-force-change')
        ->name('password.force-change.update');
});

Route::middleware(['auth', 'active', 'password.current'])->group(function () {
    Route::get('/profile/complete', ProfileCompletionController::class)
        ->name('profile.complete');

    Route::middleware('verified')->group(function () {
        Route::get('/search', [SearchController::class, 'index'])->name('search');
        // Where this account is signed in; sign out everywhere else (asks for the password).
        Route::get('/account/devices', [App\Http\Controllers\AccountDevicesController::class, 'index'])->name('account.devices');
        Route::delete('/account/devices', [App\Http\Controllers\AccountDevicesController::class, 'destroyOthers'])->middleware('throttle:account-security')->name('account.devices.destroy-others');
        // Two-factor sign-in: status, recovery codes (shown once), on/off for non-admins, trusted browsers.
        Route::get('/account/security', [App\Http\Controllers\AccountSecurityController::class, 'index'])->name('account.security');
        Route::post('/account/security/enable', [App\Http\Controllers\AccountSecurityController::class, 'enable'])->middleware('throttle:account-security')->name('account.security.enable');
        Route::post('/account/security/disable', [App\Http\Controllers\AccountSecurityController::class, 'disable'])->middleware('throttle:account-security')->name('account.security.disable');
        Route::post('/account/security/recovery-codes', [App\Http\Controllers\AccountSecurityController::class, 'recoveryCodes'])->middleware('throttle:account-security')->name('account.security.recovery-codes');
        Route::post('/account/security/forget-browsers', [App\Http\Controllers\AccountSecurityController::class, 'forgetBrowsers'])->middleware('throttle:account-security')->name('account.security.forget-browsers');
        Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
        Route::get('/notifications/{notification}', [NotificationController::class, 'show'])->name('notifications.show');
        Route::post('/notifications/{notification}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
        Route::post('/notifications/{notification}/archive', [NotificationController::class, 'archive'])->name('notifications.archive');
        Route::post('/notifications/{notification}/restore', [NotificationController::class, 'restore'])->name('notifications.restore');
        Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
        Route::post('/notifications/archive-all-read', [NotificationController::class, 'archiveAllRead'])->name('notifications.archive-all-read');
        // Selected notifications: read, archive, unarchive, delete, restore, force-delete (own notifications only).
        Route::post('/notifications/bulk', [NotificationController::class, 'bulk'])->name('notifications.bulk');
        // Soft delete to Trash; withTrashed lets a repeated click succeed instead of 404.
        Route::delete('/notifications/{notification}', [NotificationController::class, 'destroy'])->whereNumber('notification')->withTrashed()->name('notifications.destroy');
        Route::post('/notifications/{notification}/restore-deleted', [NotificationController::class, 'restoreDeleted'])->whereNumber('notification')->withTrashed()->name('notifications.restore-deleted');
        Route::delete('/notifications/{notification}/force', [NotificationController::class, 'forceDestroy'])->whereNumber('notification')->withTrashed()->name('notifications.force-destroy');
        Route::get('/api/notifications/unread', [NotificationController::class, 'unreadJson'])->name('notifications.unread');
        // 2x2 picture: private file, served only through StudentPolicy::viewIdPhoto.
        Route::get('/students/{student}/id-photo', [IdPhotoController::class, 'show'])->whereNumber('student')->name('students.id-photo');
        // Report a posting, company, message, or user to the administrators.
        Route::post('/reports', [ContentReportController::class, 'store'])->middleware('throttle:content-reports')->name('content-reports.store');
        Route::get('/messages', [MessageController::class, 'index'])->name('messages.index');
        Route::get('/messages/{user}', [MessageController::class, 'show'])->name('messages.show');
        Route::post('/messages/{user}', [MessageController::class, 'store'])->name('messages.store');
        Route::put('/messages/{message}', [MessageController::class, 'update'])->whereNumber('message')->middleware('throttle:30,1')->name('messages.update');
        Route::delete('/messages/{message}/me', [MessageController::class, 'removeForMe'])->whereNumber('message')->middleware('throttle:30,1')->name('messages.remove');
        Route::delete('/messages/{message}', [MessageController::class, 'unsend'])->whereNumber('message')->middleware('throttle:30,1')->name('messages.unsend');
        // Messenger actions: re-render one message (live updates), react, pin, forward.
        Route::get('/messages/{message}/row', [MessageController::class, 'row'])->whereNumber('message')->name('messages.row');
        Route::get('/messages/{message}/attachment', [MessageController::class, 'attachment'])->whereNumber('message')->name('messages.attachment');
        Route::post('/messages/{message}/react', [MessageController::class, 'react'])->whereNumber('message')->middleware('throttle:60,1')->name('messages.react');
        Route::post('/messages/{message}/pin', [MessageController::class, 'pin'])->whereNumber('message')->middleware('throttle:30,1')->name('messages.pin');
        Route::post('/messages/{message}/forward', [MessageController::class, 'forward'])->whereNumber('message')->middleware('throttle:30,1')->name('messages.forward');
        Route::get('/api/analytics/charts', [AnalyticsController::class, 'chartData'])
            ->middleware('role:admin,coordinator')
            ->name('analytics.charts');
        Route::get('/ojt/placements/{placement}/certificate', OjtCertificateController::class)->name('ojt.certificate');
        // Monthly Evaluation PDF: student (own), supervisor (own company), managing coordinator, admin (AppraisalPolicy::view).
        Route::get('/ojt/appraisals/{appraisal}/print', [AppraisalController::class, 'print'])->name('ojt.appraisals.print');
        // Printable school forms (PlacementPolicy::view): weekly Activity Narrative Report and Final Performance Evaluation.
        Route::get('/ojt/journals/{journalEntry}/print', [OjtFormPrintController::class, 'journal'])->name('ojt.journals.print');
        Route::get('/ojt/placements/{placement}/evaluation/print', [OjtFormPrintController::class, 'evaluation'])->name('ojt.evaluations.print');

        Route::prefix('student')->name('student.')->middleware('role:student')->group(function () {
            Route::get('/dashboard', [StudentDashboardController::class, 'index'])->name('dashboard');
            Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
            Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
            Route::post('/profile/consent', [ProfileController::class, 'consent'])->name('profile.consent');
            // Privacy and my data: what is held, and a request to delete the account (an admin approves it).
            Route::get('/privacy', [App\Http\Controllers\Student\PrivacyController::class, 'index'])->name('privacy.index');
            Route::post('/privacy/deletion-request', [App\Http\Controllers\Student\PrivacyController::class, 'store'])->middleware('throttle:privacy-requests')->name('privacy.deletion-request');
            Route::delete('/privacy/deletion-request', [App\Http\Controllers\Student\PrivacyController::class, 'cancel'])->middleware('throttle:privacy-requests')->name('privacy.deletion-cancel');
            Route::post('/profile/change-requests', [StudentChangeRequestController::class, 'store'])->name('profile.change-requests.store');
            Route::get('/profile/skills/suggest', [StudentSkillController::class, 'suggest'])->name('skills.suggest');
            Route::post('/profile/skills', [StudentSkillController::class, 'store'])->name('skills.store');
            Route::patch('/profile/skills/{studentSkill}', [StudentSkillController::class, 'update'])->name('skills.update');
            Route::delete('/profile/skills/{studentSkill}', [StudentSkillController::class, 'destroy'])->name('skills.destroy');
            Route::post('/profile/experiences', [ExperienceController::class, 'store'])->name('experiences.store');
            Route::put('/profile/experiences/{experience}', [ExperienceController::class, 'update'])->name('experiences.update');
            Route::delete('/profile/experiences/{experience}', [ExperienceController::class, 'destroy'])->name('experiences.destroy');
            Route::post('/profile/references', [CharacterReferenceController::class, 'store'])->name('references.store');
            Route::put('/profile/references/{reference}', [CharacterReferenceController::class, 'update'])->name('references.update');
            Route::delete('/profile/references/{reference}', [CharacterReferenceController::class, 'destroy'])->name('references.destroy');
            Route::post('/profile/awards', [StudentAwardController::class, 'store'])->name('awards.store');
            Route::put('/profile/awards/{award}', [StudentAwardController::class, 'update'])->name('awards.update');
            Route::delete('/profile/awards/{award}', [StudentAwardController::class, 'destroy'])->name('awards.destroy');
            Route::middleware('throttle:photo-uploads')->group(function () {
                Route::delete('/profile-picture', [ProfileController::class, 'deleteProfilePicture'])->name('profile.delete-picture');
                Route::post('/profile-picture', [ProfileController::class, 'updatePicture'])->name('profile.update-picture');
                Route::post('/profile/id-photo', [IdPhotoController::class, 'store'])->name('profile.id-photo.store');
                Route::delete('/profile/id-photo', [IdPhotoController::class, 'destroy'])->name('profile.id-photo.destroy');
            });
            // Competencies
            Route::get('/competencies', [CompetencyController::class, 'index'])->name('competencies.index');
            Route::get('/competencies/create', [CompetencyController::class, 'create'])->name('competencies.create');
            Route::post('/competencies', [CompetencyController::class, 'store'])->name('competencies.store');
            Route::get('/competencies/skills/suggest', [CompetencyController::class, 'suggestSkills'])->name('competencies.skills.suggest');
            Route::post('/competencies/from-certificate/{certificate}', [CompetencyController::class, 'completeLegacy'])->name('competencies.complete-legacy');
            Route::get('/competencies/{competency}/file', [CompetencyController::class, 'file'])->name('competencies.file');
            Route::get('/competencies/{competency}', [CompetencyController::class, 'show'])->name('competencies.show');
            Route::get('/competencies/{competency}/edit', [CompetencyController::class, 'edit'])->name('competencies.edit');
            Route::put('/competencies/{competency}', [CompetencyController::class, 'update'])->name('competencies.update');
            Route::delete('/competencies/{competency}', [CompetencyController::class, 'destroy'])->name('competencies.destroy');

            // Portfolio
            Route::get('/portfolio', [PortfolioController::class, 'index'])->name('portfolio.index');
            Route::get('/portfolio/create', [PortfolioController::class, 'create'])->name('portfolio.create');
            Route::post('/portfolio', [PortfolioController::class, 'storePortfolio'])->name('portfolio.store');
            Route::post('/portfolio/bulk', [PortfolioController::class, 'bulkStore'])->name('portfolio.bulk-store');
            Route::post('/portfolio/deployment-requirements/{deploymentRequirement}', [PortfolioController::class, 'storeDeploymentRequirement'])
                ->name('portfolio.deployment-requirements.store');
            Route::get('/portfolio/{portfolio}', [PortfolioController::class, 'show'])->name('portfolio.show');
            Route::get('/portfolio/{portfolio}/edit', [PortfolioController::class, 'edit'])->name('portfolio.edit');
            Route::put('/portfolio/{portfolio}', [PortfolioController::class, 'update'])->name('portfolio.update');
            Route::delete('/portfolio/{portfolio}', [PortfolioController::class, 'destroyPortfolio'])->name('portfolio.destroyPortfolio');

            // Certificates
            Route::post('/certificates', [PortfolioController::class, 'storeCertificate'])->name('certificates.store');
            Route::get('/certificates/{certificate}/edit', [PortfolioController::class, 'editCertificate'])->name('certificates.edit');
            Route::put('/certificates/{certificate}', [PortfolioController::class, 'updateCertificate'])->name('certificates.update');
            Route::delete('/certificates/{certificate}', [PortfolioController::class, 'destroyCertificate'])->name('certificates.destroy');
            Route::get('/resume', [ResumeController::class, 'index'])->name('resume.index');
            Route::post('/resume/generate', [ResumeController::class, 'generate'])->name('resume.generate');
            Route::delete('/resume/{resume}', [ResumeController::class, 'destroy'])->whereNumber('resume')->name('resume.destroy');
            // Resume Editor: edits are stored per resume (its content JSON), never on the profile.
            Route::get('/resume/{resume}/edit', [ResumeController::class, 'edit'])->whereNumber('resume')->name('resume.edit');
            Route::post('/resume/{resume}/preview', [ResumeController::class, 'preview'])->whereNumber('resume')->middleware('throttle:120,1')->name('resume.preview');
            // Page counter: answering means rendering the PDF, so it is rate limited (the editor asks 3 seconds after the last change).
            Route::post('/resume/{resume}/pages', [ResumeController::class, 'pages'])->whereNumber('resume')->middleware('throttle:20,1')->name('resume.pages');
            Route::put('/resume/{resume}', [ResumeController::class, 'update'])->whereNumber('resume')->name('resume.update');
            Route::post('/resume/{resume}/copy', [ResumeController::class, 'storeCopy'])->whereNumber('resume')->name('resume.copy');
            Route::post('/resume/{resume}/pdf', [ResumeController::class, 'pdf'])->whereNumber('resume')->middleware('throttle:30,1')->name('resume.pdf');
            Route::get('/internships', [StudentInternshipController::class, 'index'])->name('internships.index');
            Route::get('/internships/{internship}', [StudentInternshipController::class, 'show'])->name('internships.show');
            Route::post('/internships/{internship}/recommendation-feedback', [StudentInternshipController::class, 'storeRecommendationFeedback'])->name('internships.recommendation-feedback');
            Route::post('/internships/{internship}/apply', [StudentInternshipController::class, 'apply'])->name('internships.apply');
            Route::get('/invitations', [InvitationController::class, 'index'])->name('invitations.index');
            Route::post('/invitations/{invitation}/accept', [InvitationController::class, 'accept'])->whereNumber('invitation')->name('invitations.accept');
            Route::post('/invitations/{invitation}/decline', [InvitationController::class, 'decline'])->whereNumber('invitation')->name('invitations.decline');
            Route::put('/profile/discoverable', [StudentProfileController::class, 'discoverable'])->name('profile.discoverable');
            Route::get('/applications', [StudentInternshipController::class, 'applications'])->name('applications.index');
            Route::get('/applications/{application}', [StudentInternshipController::class, 'showApplication'])->name('applications.show');
            Route::get('/applications/{application}/edit', [StudentInternshipController::class, 'editApplication'])->name('applications.edit');
            Route::put('/applications/{application}', [StudentInternshipController::class, 'updateApplication'])->name('applications.update');
            Route::delete('/applications/{application}', [StudentInternshipController::class, 'destroyApplication'])->name('applications.destroy');

            // OJT tracking
            Route::get('/ojt', [StudentOjtController::class, 'index'])->name('ojt.index');
            Route::post('/ojt/{placement}/attendance', [StudentOjtController::class, 'storeAttendance'])->name('ojt.attendance.store');
            Route::delete('/ojt/{placement}/attendance/{attendanceLog}', [StudentOjtController::class, 'destroyAttendance'])->name('ojt.attendance.destroy');
            Route::post('/ojt/{placement}/journals', [StudentOjtController::class, 'storeJournal'])->name('ojt.journals.store');
            Route::patch('/ojt/{placement}/details', [StudentOjtController::class, 'updateDetails'])->name('ojt.details.update');
            Route::delete('/ojt/{placement}/journals/{journalEntry}', [StudentOjtController::class, 'destroyJournal'])->name('ojt.journals.destroy');
            Route::post('/ojt/appraisals/{appraisal}/rating', [AppraisalController::class, 'submitSelf'])->name('ojt.appraisals.self');
            Route::post('/ojt/appraisals/{appraisal}/comment', [AppraisalController::class, 'submitStudentComment'])->name('ojt.appraisals.comment');
        });

        Route::prefix('employer')->name('employer.')->middleware('role:employer')->group(function () {
            Route::get('/dashboard', [EmployerDashboardController::class, 'index'])->name('dashboard');
            Route::get('/profile', [EmployerProfileController::class, 'edit'])->name('profile.edit');
            Route::put('/profile', [EmployerProfileController::class, 'update'])->name('profile.update');
            Route::post('/company-logo', [EmployerProfileController::class, 'updateLogo'])->name('profile.update-logo');
            Route::delete('/company-logo', [EmployerProfileController::class, 'deleteCompanyLogo'])->name('profile.delete-logo');
            // Posting, browsing students, and inviting need an admin-verified company.
            Route::resource('internships', EmployerInternshipController::class)->except(['show'])
                ->middlewareFor(['create', 'store', 'edit', 'update', 'destroy'], 'company.verified');
            Route::middleware('company.verified')->group(function () {
                Route::post('/internships/{internship}/close', [EmployerInternshipController::class, 'close'])->name('internships.close');
                Route::get('/students', [StudentBrowseController::class, 'index'])->name('students.index');
                Route::get('/students/{student}', [StudentBrowseController::class, 'show'])->whereNumber('student')->name('students.show');
                Route::post('/students/{student}/shortlist', [ShortlistController::class, 'store'])->whereNumber('student')->name('students.shortlist.store');
                Route::delete('/students/{student}/shortlist', [ShortlistController::class, 'destroy'])->whereNumber('student')->name('students.shortlist.destroy');
                Route::post('/invitations', [App\Http\Controllers\Employer\InvitationController::class, 'store'])->middleware('throttle:invitations')->name('invitations.store');
            });
            Route::get('/invitations', [App\Http\Controllers\Employer\InvitationController::class, 'index'])->name('invitations.index');
            Route::get('/applicants', [ApplicantController::class, 'index'])->name('applicants.index');
            Route::get('/applicants/{application}', [ApplicantController::class, 'show'])->name('applicants.show');
            Route::put('/applicants/{application}/status', [ApplicantController::class, 'updateStatus'])->name('applicants.status');

            // The company's supervisors: create, edit, deactivate, and assign interns to them.
            Route::get('/supervisors', [App\Http\Controllers\Employer\SupervisorController::class, 'index'])->name('supervisors.index');
            Route::post('/supervisors', [App\Http\Controllers\Employer\SupervisorController::class, 'store'])->middleware('throttle:company-invitation-send')->name('supervisors.store');
            Route::put('/supervisors/{supervisor}', [App\Http\Controllers\Employer\SupervisorController::class, 'update'])->name('supervisors.update');
            Route::patch('/supervisors/{supervisor}/status', [App\Http\Controllers\Employer\SupervisorController::class, 'status'])->name('supervisors.status');
            Route::post('/supervisors/{supervisor}/resend', [App\Http\Controllers\Employer\SupervisorController::class, 'resend'])->middleware('throttle:company-invitation-send')->name('supervisors.resend');
            Route::put('/interns/{placement}/supervisor', [App\Http\Controllers\Employer\SupervisorController::class, 'assign'])->name('interns.supervisor');
            Route::post('/interns/supervisor/bulk', [App\Http\Controllers\Employer\SupervisorController::class, 'assignMany'])->name('interns.supervisor.bulk');

            // OJT supervision
            Route::get('/interns', [EmployerInternController::class, 'index'])->name('interns.index');
            Route::get('/ojt/{section}', [App\Http\Controllers\Employer\OjtOverviewController::class, 'show'])
                ->where('section', 'progress|time-records|weekly-reports|monthly-evaluations|final-evaluations')->name('ojt.overview');
            Route::get('/interns/{placement}', [EmployerInternController::class, 'show'])->name('interns.show');
            Route::put('/interns/{placement}/schedule', [EmployerInternController::class, 'updateSchedule'])->name('interns.schedule');
            Route::patch('/interns/{placement}/attendance/{attendanceLog}', [EmployerInternController::class, 'reviewAttendance'])->name('interns.attendance.review');
            Route::post('/interns/{placement}/attendance/approve-all', [EmployerInternController::class, 'approveAllAttendance'])->name('interns.attendance.approve-all');
            Route::patch('/interns/{placement}/journals/{journalEntry}', [EmployerInternController::class, 'reviewJournal'])->name('interns.journals.review');
            Route::patch('/interns/{placement}/journals/{journalEntry}/reopen', [EmployerInternController::class, 'reopenJournal'])->name('interns.journals.reopen');
            Route::post('/interns/{placement}/evaluation', [EmployerInternController::class, 'storeEvaluation'])->name('interns.evaluation.store');
            Route::post('/interns/appraisals/{appraisal}', [AppraisalController::class, 'submitSupervisor'])->name('interns.appraisals.rate');
        });

        // The company's supervisors: the same OJT supervision pages and actions as the HR account has for
        // unassigned interns, limited by PlacementPolicy::supervise to the interns assigned to them.
        Route::prefix('supervisor')->name('supervisor.')->middleware('role:supervisor')->group(function () {
            Route::get('/dashboard', [App\Http\Controllers\Supervisor\DashboardController::class, 'index'])->name('dashboard');
            Route::get('/profile', [App\Http\Controllers\Supervisor\ProfileController::class, 'edit'])->name('profile.edit');
            Route::post('/profile-picture', [App\Http\Controllers\Supervisor\ProfileController::class, 'updatePicture'])->name('profile.update-picture');
            Route::delete('/profile-picture', [App\Http\Controllers\Supervisor\ProfileController::class, 'deletePicture'])->name('profile.delete-picture');
            Route::get('/interns', [EmployerInternController::class, 'index'])->name('interns.index');
            Route::get('/ojt/{section}', [App\Http\Controllers\Employer\OjtOverviewController::class, 'show'])
                ->where('section', 'progress|time-records|weekly-reports|monthly-evaluations|final-evaluations')->name('ojt.overview');
            Route::get('/interns/{placement}', [EmployerInternController::class, 'show'])->name('interns.show');
            Route::put('/interns/{placement}/schedule', [EmployerInternController::class, 'updateSchedule'])->name('interns.schedule');
            Route::patch('/interns/{placement}/attendance/{attendanceLog}', [EmployerInternController::class, 'reviewAttendance'])->name('interns.attendance.review');
            Route::post('/interns/{placement}/attendance/approve-all', [EmployerInternController::class, 'approveAllAttendance'])->name('interns.attendance.approve-all');
            Route::patch('/interns/{placement}/journals/{journalEntry}', [EmployerInternController::class, 'reviewJournal'])->name('interns.journals.review');
            Route::patch('/interns/{placement}/journals/{journalEntry}/reopen', [EmployerInternController::class, 'reopenJournal'])->name('interns.journals.reopen');
            Route::post('/interns/{placement}/evaluation', [EmployerInternController::class, 'storeEvaluation'])->name('interns.evaluation.store');
            Route::post('/interns/appraisals/{appraisal}', [AppraisalController::class, 'submitSupervisor'])->name('interns.appraisals.rate');
        });

        Route::prefix('coordinator')->name('coordinator.')->middleware('role:coordinator')->group(function () {
            Route::get('/dashboard', [CoordinatorDashboardController::class, 'index'])->name('dashboard');
            Route::get('/profile', [App\Http\Controllers\Coordinator\ProfileController::class, 'edit'])->name('profile.edit');
            Route::put('/profile', [App\Http\Controllers\Coordinator\ProfileController::class, 'update'])->name('profile.update');
            Route::post('/profile-picture', [App\Http\Controllers\Coordinator\ProfileController::class, 'updatePicture'])->name('profile.update-picture');
            Route::delete('/profile-picture', [App\Http\Controllers\Coordinator\ProfileController::class, 'deleteProfilePicture'])->name('profile.delete-picture');
            Route::get('/students', [CoordinatorDashboardController::class, 'students'])->name('students.index');
            Route::get('/students/{student}', [CoordinatorDashboardController::class, 'showStudent'])->name('students.show');
            Route::post('/students/{student}/verify-profile', [StudentProfileReviewController::class, 'verify'])->name('students.verify-profile');
            Route::put('/students/{student}/academic-readiness', [StudentProfileReviewController::class, 'updateAcademicReadiness'])->name('students.academic-readiness.update');
            Route::patch('/students/{student}/change-requests/{studentChangeRequest}', [StudentProfileReviewController::class, 'reviewChangeRequest'])
                ->name('students.change-requests.review');
            Route::post('/students/{student}/deployment-documents', [VerificationController::class, 'uploadDeploymentDocument'])
                ->name('students.deployment-documents.store');
            Route::get('/verification', [VerificationController::class, 'index'])->name('verification.index');
            Route::get('/verification/students/{student}', [VerificationController::class, 'studentDetails'])->name('verification.students.show');
            Route::post('/verification/students/{student}/verify-all', [VerificationController::class, 'verifyAll'])->name('verification.students.verify-all');
            Route::get('/verification/{type}/{id}/evidence', [VerificationController::class, 'showEvidence'])
                ->whereIn('type', ['competencies', 'certificates', 'portfolio', 'resumes'])
                ->name('verification.evidence');
            Route::get('/verification/{type}/{id}/download', [VerificationController::class, 'downloadEvidence'])
                ->whereIn('type', ['competencies', 'certificates', 'portfolio', 'resumes'])
                ->name('verification.download');
            // 'certificates' and 'competencies' are intentionally excluded here: coordinators
            // can view/download but not review/change status.
            Route::patch('/verification/{type}/{id}', [VerificationController::class, 'updateStatus'])
                ->whereIn('type', ['portfolio'])
                ->name('verification.update');
            Route::post('/verification/batch', [CoordinatorDashboardController::class, 'batchVerify'])->name('verification.batch');
            Route::post('/verification/portfolios/{portfolio}', [VerificationController::class, 'reviewPortfolio'])->name('verification.reviewPortfolio');
            // Reports now live on the Statistics & Reports page; old bookmarks land there.
            Route::permanentRedirect('/reports', '/coordinator/statistics');
            Route::get('/analytics', fn () => view('coordinator.analytics'))->name('analytics');
            Route::get('/placements', [CoordinatorPlacementController::class, 'index'])->name('placements.index');
            Route::get('/placements/{placement}', [CoordinatorPlacementController::class, 'show'])->name('placements.show');
            Route::post('/placements/{placement}/clear', [CoordinatorPlacementController::class, 'clear'])->name('placements.clear');
            Route::post('/placements/{placement}/request-rating', [CoordinatorPlacementController::class, 'requestRating'])->name('placements.request-rating');
            Route::post('/placements/{placement}/validate', [CoordinatorPlacementController::class, 'validatePlacement'])->name('placements.validate');
            Route::post('/placements/{placement}/hold', [CoordinatorPlacementController::class, 'hold'])->name('placements.hold');
            // Monthly Evaluations: request (one placement or all active ones), adviser rating, reopen the latest step.
            Route::post('/placements/{placement}/appraisals', [AppraisalController::class, 'store'])->name('placements.appraisals.store');
            Route::post('/appraisals/bulk', [AppraisalController::class, 'bulkStore'])->name('appraisals.bulk');
            // OJT Monitoring > Requests and reminders: request once per period, remind with no limit (flood guard only).
            Route::post('/ojt/requests', [App\Http\Controllers\Coordinator\OjtRequestController::class, 'request'])->name('ojt.requests');
            Route::post('/ojt/reminders', [App\Http\Controllers\Coordinator\OjtRequestController::class, 'remindAll'])->middleware('throttle:20,1')->name('ojt.reminders');
            Route::post('/placements/{placement}/remind-daily-log', [App\Http\Controllers\Coordinator\OjtRequestController::class, 'remindDailyLog'])->middleware('throttle:30,1')->name('placements.remind-daily-log');
            Route::post('/appraisals/{appraisal}/adviser', [AppraisalController::class, 'submitAdviser'])->name('appraisals.adviser');
            Route::post('/appraisals/{appraisal}/reopen', [AppraisalController::class, 'reopen'])->name('appraisals.reopen');

            // Eligibility decisions (approve / mark not eligible) with history.
            Route::post('/students/{student}/eligibility', [EligibilityDecisionController::class, 'store'])->name('students.eligibility.store');
            // Applications and placements monitor, including unplaced students.
            Route::get('/applications', [ApplicationMonitorController::class, 'index'])->name('applications.index');
            // Partner companies: directory, MOA, and postings marked not suitable.
            Route::get('/companies', [CompanyController::class, 'index'])->name('companies.index');
            // Invite companies that have no account yet. Registered before /companies/{employer} so "invitations" is not read as an id.
            Route::get('/companies/invitations', [App\Http\Controllers\Coordinator\CompanyInvitationController::class, 'index'])->name('companies.invitations.index');
            Route::post('/companies/invitations', [App\Http\Controllers\Coordinator\CompanyInvitationController::class, 'store'])->middleware('throttle:company-invitation-send')->name('companies.invitations.store');
            Route::post('/companies/invitations/{companyInvitation}/resend', [App\Http\Controllers\Coordinator\CompanyInvitationController::class, 'resend'])->middleware('throttle:company-invitation-send')->name('companies.invitations.resend');
            Route::get('/companies/{employer}', [CompanyController::class, 'show'])->name('companies.show');
            Route::put('/companies/{employer}/moa', [CompanyController::class, 'updateMoa'])->name('companies.moa.update');
            Route::post('/internships/{internship}/flag', [CompanyController::class, 'flag'])->name('internships.flag');
            Route::delete('/internships/{internship}/flag', [CompanyController::class, 'unflag'])->name('internships.unflag');
            // Second review of weekly reports and evaluations after the supervisor.
            Route::get('/reviews', [ReviewController::class, 'index'])->name('reviews.index');
            Route::post('/reviews/evaluations/remind', [ReviewController::class, 'remind'])->middleware('throttle:30,1')->name('reviews.evaluations.remind');
            Route::post('/reviews/journals/{journalEntry}', [ReviewController::class, 'reviewJournal'])->name('reviews.journals');
            Route::post('/reviews/journals/{journalEntry}/reopen', [ReviewController::class, 'reopenJournal'])->name('reviews.journals.reopen');
            Route::post('/reviews/evaluations/{evaluation}', [ReviewController::class, 'reviewEvaluation'])->name('reviews.evaluations');
            // Institutional statistics and exports.
            Route::get('/statistics', [StatisticsController::class, 'index'])->name('statistics.index');
            Route::get('/statistics/export/{format}', [StatisticsController::class, 'export'])->whereIn('format', ['pdf', 'xlsx'])->name('statistics.export');
            Route::post('/statistics/reports', [StatisticsController::class, 'generateReport'])->name('statistics.reports.generate');
            // Academic Settings, the same pages the admin has (routes/academics.php).
            require __DIR__.'/academics.php';
        });

        Route::prefix('admin')->name('admin.')->middleware('role:admin')->group(function () {
            Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
            Route::get('/profile', [AdminProfileController::class, 'edit'])->name('profile.edit');
            Route::post('/profile-picture', [AdminProfileController::class, 'updatePicture'])->name('profile.update-picture');
            Route::delete('/profile-picture', [AdminProfileController::class, 'deletePicture'])->name('profile.delete-picture');
            // Academic Settings, shared with coordinators (routes/academics.php).
            require __DIR__.'/academics.php';
            Route::get('/criteria', [EligibilityCriteriaController::class, 'index'])->name('criteria.index');
            Route::get('/criteria/{program}', [EligibilityCriteriaController::class, 'show'])->name('criteria.show');
            Route::post('/criteria/{program}', [EligibilityCriteriaController::class, 'store'])->name('criteria.store');
            Route::get('/students/{student}/files', [PortfolioFileController::class, 'studentFiles'])
                ->name('students.files');
            Route::put('/students/{student}/student-id', [PortfolioFileController::class, 'updateStudentId'])
                ->name('students.student-id.update');
            Route::post('/portfolio-files/{type}/{id}/review', [PortfolioFileController::class, 'review'])
                ->whereIn('type', ['portfolio', 'certificate', 'resume'])
                ->name('portfolio-files.review');
            Route::delete('/portfolio-files/{type}/{id}', [PortfolioFileController::class, 'destroy'])
                ->whereIn('type', ['portfolio', 'certificate', 'resume'])
                ->name('portfolio-files.destroy');
            Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
            Route::get('/users/create', [AdminUserController::class, 'create'])->name('users.create');
            Route::get('/students/import', [StudentImportController::class, 'create'])->name('students.import.create');
            Route::post('/students/import', [StudentImportController::class, 'store'])->name('students.import.store');
            Route::post('/users', [AdminUserController::class, 'store'])->name('users.store');
            Route::put('/users/{user}', [AdminUserController::class, 'update'])->name('users.update');
            Route::post('/users/{user}/verify-email', [AdminUserController::class, 'verifyEmail'])->name('users.verify-email');
            Route::post('/users/{user}/verify-company', [AdminUserController::class, 'verifyCompany'])->name('users.verify-company');
            Route::post('/users/{user}/unverify-company', [AdminUserController::class, 'unverifyCompany'])->name('users.unverify-company');
            Route::delete('/users/{user}/delete-now', [AdminUserController::class, 'deleteNow'])->name('users.delete-now');
            Route::delete('/users/{user}', [AdminUserController::class, 'destroy'])->name('users.destroy');
            Route::get('/announcements', [AdminAnnouncementController::class, 'index'])->name('announcements.index');
            Route::post('/announcements', [AdminAnnouncementController::class, 'store'])->name('announcements.store');
            Route::put('/announcements/{announcement}', [AdminAnnouncementController::class, 'update'])->name('announcements.update');
            Route::delete('/announcements/{announcement}', [AdminAnnouncementController::class, 'destroy'])->name('announcements.destroy');
            Route::get('/company-invitations', [App\Http\Controllers\Admin\CompanyInvitationController::class, 'index'])->name('company-invitations.index');
            Route::put('/company-invitations/{companyInvitation}/expiry', [App\Http\Controllers\Admin\CompanyInvitationController::class, 'updateExpiry'])->name('company-invitations.expiry');
            Route::get('/placements', [AdminPlacementController::class, 'index'])->name('placements.index');
            Route::get('/placements/{placement}', [AdminPlacementController::class, 'show'])->name('placements.show');
            // Audit and security log (kept 1 year).
            Route::get('/logs', [AuditLogController::class, 'index'])->name('logs');
            Route::get('/logs/export', [AuditLogController::class, 'export'])->name('logs.export');
            // Account deletion: the approval queue (approving anonymises the account, after an encrypted backup), and the
            // student accounts past the retention period (listing only: nothing is deleted without approval).
            Route::get('/deletion-requests', [App\Http\Controllers\Admin\DeletionRequestController::class, 'index'])->name('deletion-requests.index');
            Route::post('/deletion-requests/{deletionRequest}/approve', [App\Http\Controllers\Admin\DeletionRequestController::class, 'approve'])->middleware('throttle:privacy-requests')->name('deletion-requests.approve');
            Route::post('/deletion-requests/{deletionRequest}/reject', [App\Http\Controllers\Admin\DeletionRequestController::class, 'reject'])->name('deletion-requests.reject');
            Route::get('/retention', [App\Http\Controllers\Admin\RetentionController::class, 'index'])->name('retention');
            Route::post('/retention/{student}/request', [App\Http\Controllers\Admin\RetentionController::class, 'store'])->whereNumber('student')->name('retention.request');
            // Who opened which student's data (kept 1 year).
            Route::get('/data-access', [App\Http\Controllers\Admin\DataAccessController::class, 'index'])->name('data-access');
            // End a lock after too many wrong passwords (also: php artisan user:unlock {email}).
            Route::post('/users/{user}/unlock', [AdminUserController::class, 'unlock'])->name('users.unlock');
            // Account suspension.
            Route::post('/users/{user}/suspend', [AdminUserController::class, 'suspend'])->name('users.suspend');
            Route::post('/users/{user}/lift-suspension', [AdminUserController::class, 'liftSuspension'])->name('users.lift-suspension');
            // Reported content and moderation.
            Route::get('/moderation', [ModerationController::class, 'index'])->name('moderation.index');
            Route::get('/moderation/{contentReport}', [ModerationController::class, 'show'])->name('moderation.show');
            Route::post('/moderation/{contentReport}/resolve', [ModerationController::class, 'resolve'])->name('moderation.resolve');
            // Usage and health.
            Route::get('/health', [HealthController::class, 'index'])->name('health');
            // Student skill tags.
            Route::get('/skill-tags', [SkillTagController::class, 'index'])->name('skill-tags.index');
            Route::put('/skill-tags/{skillTag}', [SkillTagController::class, 'update'])->name('skill-tags.update');
            Route::post('/skill-tags/{skillTag}/merge', [SkillTagController::class, 'merge'])->name('skill-tags.merge');
            Route::delete('/skill-tags/{skillTag}', [SkillTagController::class, 'destroy'])->name('skill-tags.destroy');
            Route::match(['get', 'post'], '/reports', [AdminSystemController::class, 'reports'])->name('reports');
            Route::post('/backup', [AdminSystemController::class, 'backup'])->name('backup');
            Route::get('/backups/{name}/download', [AdminSystemController::class, 'downloadBackup'])->where('name', 'backup_[0-9_-]+\.zip')->name('backups.download');
            Route::post('/backups/{name}/verify', [AdminSystemController::class, 'verifyBackup'])->where('name', 'backup_[0-9_-]+\.zip')->middleware('throttle:account-security')->name('backups.verify');
        });
    });
});
