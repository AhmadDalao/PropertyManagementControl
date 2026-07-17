<?php

use App\Http\Controllers\AdminExportController;
use App\Http\Controllers\AssetController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\CmsPageController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentationController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\ExpenseEntryController;
use App\Http\Controllers\GlobalSearchController;
use App\Http\Controllers\LeaseController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\MaintenanceRequestController;
use App\Http\Controllers\MediaFileController;
use App\Http\Controllers\NavigationItemController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PortfolioController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PropertyMapController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\TenantController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', [CmsPageController::class, 'home'])->name('home');
Route::get('/pages/{slug}', [CmsPageController::class, 'show'])->name('pages.show');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
});

Route::post('/locale/{locale}', [LocaleController::class, 'update'])->name('locale.update');

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/profile', [ProfileController::class, 'index'])->name('profile.index');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
    Route::get('/documentation', [DocumentationController::class, 'index'])->name('documentation.index');
    Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
    Route::get('/audit-logs/export', [AuditLogController::class, 'export'])->name('audit-logs.export');
    Route::get('/global-search', GlobalSearchController::class)->name('global-search');
    Route::get('/exports/{resource}', AdminExportController::class)->name('exports.resource');
    Route::get('/property-map', PropertyMapController::class)->name('property-map.index')->middleware('portfolio.module:assets');

    Route::resource('portfolios', PortfolioController::class)->only(['index', 'create', 'store', 'show', 'edit', 'update', 'destroy']);
    Route::resource('users', UserController::class)->only(['index', 'create', 'store', 'show', 'edit', 'update', 'destroy'])->middleware('portfolio.module:users');
    Route::resource('assets', AssetController::class)->only(['index', 'create', 'store', 'show', 'edit', 'update', 'destroy'])->middleware('portfolio.module:assets');
    Route::resource('tenants', TenantController::class)->only(['index', 'create', 'store', 'show', 'edit', 'update', 'destroy'])->middleware('portfolio.module:tenants');
    Route::resource('leases', LeaseController::class)->only(['index', 'create', 'store', 'show', 'edit', 'update', 'destroy'])->middleware('portfolio.module:leases');
    Route::post('/leases/{lease}/signed-contract', [LeaseController::class, 'uploadSignedContract'])->name('leases.signed-contract')->middleware('portfolio.module:leases');
    Route::get('/leases/{lease}/contract', [LeaseController::class, 'contract'])->name('leases.contract')->middleware('portfolio.module:leases');
    Route::get('/leases/{lease}/statement', [LeaseController::class, 'statement'])->name('leases.statement')->middleware('portfolio.module:leases');

    Route::resource('payments', PaymentController::class)->only(['index', 'create', 'store', 'show', 'edit', 'update', 'destroy'])->middleware('portfolio.module:payments');
    Route::get('/payments/{payment}/receipt', [PaymentController::class, 'receipt'])->name('payments.receipt')->middleware('portfolio.module:payments');

    Route::resource('maintenance-requests', MaintenanceRequestController::class)
        ->parameters(['maintenance-requests' => 'maintenanceRequest'])
        ->only(['index', 'create', 'store', 'show', 'edit', 'update', 'destroy'])
        ->middleware('portfolio.module:maintenance');

    Route::resource('expenses', ExpenseEntryController::class)->only(['index', 'create', 'store', 'show', 'edit', 'update', 'destroy'])->middleware('portfolio.module:expenses');
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index')->middleware('portfolio.module:reports');
    Route::get('/reports/export', [ReportController::class, 'export'])->name('reports.export')->middleware('portfolio.module:reports');
    Route::post('/reports/presets', [ReportController::class, 'storePreset'])->name('reports.presets.store')->middleware('portfolio.module:reports');
    Route::delete('/reports/presets/{reportPreset}', [ReportController::class, 'destroyPreset'])->name('reports.presets.destroy')->middleware('portfolio.module:reports');
    Route::resource('documents', DocumentController::class)->only(['index', 'create', 'store', 'show', 'edit', 'update', 'destroy'])->middleware('portfolio.module:documents');
    Route::get('/documents/{document}/download', [DocumentController::class, 'download'])->name('documents.download')->middleware('portfolio.module:documents');

    Route::get('/cms', [CmsPageController::class, 'index'])->name('cms.index');
    Route::get('/cms/pages/create', [CmsPageController::class, 'create'])->name('cms.pages.create');
    Route::get('/cms/sections/create', [CmsPageController::class, 'createSection'])->name('cms.sections.create');
    Route::get('/cms/sections/{cmsSection}/edit', [CmsPageController::class, 'editSection'])->name('cms.sections.edit');
    Route::get('/cms/navigation/create', [NavigationItemController::class, 'create'])->name('cms.navigation.create');
    Route::get('/cms/navigation/{navigationItem}/edit', [NavigationItemController::class, 'edit'])->name('cms.navigation.edit');
    Route::get('/cms/pages/{cmsPage}', [CmsPageController::class, 'builder'])->name('cms.pages.show');
    Route::get('/cms/pages/{cmsPage}/edit', [CmsPageController::class, 'edit'])->name('cms.pages.edit');
    Route::post('/cms/pages', [CmsPageController::class, 'store'])->name('cms.pages.store');
    Route::put('/cms/pages/{cmsPage}', [CmsPageController::class, 'update'])->name('cms.pages.update');
    Route::delete('/cms/pages/{cmsPage}', [CmsPageController::class, 'destroy'])->name('cms.pages.destroy');
    Route::post('/cms/sections', [CmsPageController::class, 'storeSection'])->name('cms.sections.store');
    Route::put('/cms/sections/{cmsSection}', [CmsPageController::class, 'updateSection'])->name('cms.sections.update');
    Route::delete('/cms/sections/{cmsSection}', [CmsPageController::class, 'destroySection'])->name('cms.sections.destroy');
    Route::post('/cms/pages/{cmsPage}/sections', [CmsPageController::class, 'attachSection'])->name('cms.pages.sections.store');
    Route::put('/cms/pages/{cmsPage}/sections/reorder', [CmsPageController::class, 'reorderPageSections'])->name('cms.pages.sections.reorder');
    Route::put('/cms/page-sections/{cmsPageSection}', [CmsPageController::class, 'updatePageSection'])->name('cms.page-sections.update');
    Route::delete('/cms/page-sections/{cmsPageSection}', [CmsPageController::class, 'destroyPageSection'])->name('cms.page-sections.destroy');

    Route::resource('navigation-items', NavigationItemController::class)->only(['store', 'update', 'destroy']);
    Route::resource('media-files', MediaFileController::class)->only(['index', 'create', 'store', 'show', 'edit', 'update', 'destroy'])->middleware('portfolio.module:media');
});
