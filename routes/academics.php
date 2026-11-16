<?php

/*
 * Academic Settings (programs, deployment requirements, calendar). Required inside both the
 * admin group (admin.academics.*, /admin/academics) and the coordinator group
 * (coordinator.academics.*, /coordinator/academics); each group's role middleware guards it.
 */

use App\Http\Controllers\Admin\AcademicCatalogController;
use App\Http\Controllers\Admin\DeploymentRequirementController;
use Illuminate\Support\Facades\Route;

Route::get('/academics', [AcademicCatalogController::class, 'index'])->name('academics.index');
Route::get('/academics/settings', fn () => redirect()->route(request()->routeIs('coordinator.*') ? 'coordinator.academics.index' : 'admin.academics.index'))
    ->name('academics.settings');
Route::post('/academics/settings', [AcademicCatalogController::class, 'updateSettings'])->name('academics.settings.update');
Route::post('/academics/deployment-requirements', [DeploymentRequirementController::class, 'store'])
    ->name('academics.deployment-requirements.store');
Route::patch('/academics/deployment-requirements/{deploymentRequirement}', [DeploymentRequirementController::class, 'update'])
    ->name('academics.deployment-requirements.update');
Route::delete('/academics/deployment-requirements/{deploymentRequirement}', [DeploymentRequirementController::class, 'destroy'])
    ->name('academics.deployment-requirements.destroy');
Route::get('/academics/programs/create', [AcademicCatalogController::class, 'create'])->name('academics.programs.create');
Route::post('/academics/programs', [AcademicCatalogController::class, 'store'])->name('academics.programs.store');
Route::get('/academics/programs/{program}/edit', [AcademicCatalogController::class, 'edit'])->name('academics.programs.edit');
Route::put('/academics/programs/{program}', [AcademicCatalogController::class, 'update'])->name('academics.programs.update');
