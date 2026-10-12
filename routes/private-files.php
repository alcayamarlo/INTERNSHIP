<?php

use App\Http\Controllers\PrivateFileController;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->group(function () {
    Route::middleware(['auth', 'active', 'verified', 'role:student,coordinator,admin,employer'])->group(function () {
        Route::get('/student/portfolio/{portfolio}/download', [PrivateFileController::class, 'downloadPortfolio'])
            ->name('student.portfolio.download');
        Route::get('/student/portfolio/{portfolio}/preview', [PrivateFileController::class, 'previewPortfolio'])
            ->name('student.portfolio.preview');
        Route::get('/student/certificates/{certificate}/download', [PrivateFileController::class, 'downloadCertificate'])
            ->name('student.certificates.download');
        Route::get('/student/certificates/{certificate}/preview', [PrivateFileController::class, 'previewCertificate'])
            ->name('student.certificates.preview');
        Route::get('/student/resume/{resume}/download', [PrivateFileController::class, 'downloadResume'])
            ->name('student.resume.download');
        Route::get('/student/resume/{resume}/view', [PrivateFileController::class, 'viewResume'])
            ->name('student.resume.view');
    });

    Route::get('/employer/applicants/{application}/resume', [PrivateFileController::class, 'downloadApplicantResume'])
        ->middleware(['auth', 'active', 'verified', 'role:employer'])
        ->name('employer.applicants.resume');
    Route::get('/employer/applicants/{application}/resume/preview', [PrivateFileController::class, 'previewApplicantResume'])
        ->middleware(['auth', 'active', 'verified', 'role:employer'])
        ->name('employer.applicants.resume.preview');
    Route::get('/employer/applicants/{application}/portfolios/{portfolio}/download', [PrivateFileController::class, 'downloadApplicantPortfolio'])
        ->middleware(['auth', 'active', 'verified', 'role:employer'])
        ->name('employer.applicants.portfolios.download');
    Route::get('/employer/applicants/{application}/portfolios/{portfolio}/preview', [PrivateFileController::class, 'previewApplicantPortfolio'])
        ->middleware(['auth', 'active', 'verified', 'role:employer'])
        ->name('employer.applicants.portfolios.preview');
    Route::get('/employer/applicants/{application}/certificates/{certificate}/preview', [PrivateFileController::class, 'previewApplicantCertificate'])
        ->middleware(['auth', 'active', 'verified', 'role:employer'])
        ->name('employer.applicants.certificates.preview');
});
