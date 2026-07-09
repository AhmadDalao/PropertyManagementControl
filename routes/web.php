<?php

use App\Http\Controllers\AdminExportController;
use App\Http\Controllers\AssetController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\CmsPageController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\DocumentationController;
use App\Http\Controllers\ExpenseEntryController;
use App\Http\Controllers\GlobalSearchController;
use App\Http\Controllers\LeaseController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\MaintenanceRequestController;
use App\Http\Controllers\MediaFileController;
use App\Http\Controllers\NavigationItemController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PortfolioController;
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
    Route::get('/documentation', [DocumentationController::class, 'index'])->name('documentation.index');
    Route::get('/global-search', GlobalSearchController::class)->name('global-search');
    Route::get('/exports/{resource}', AdminExportController::class)->name('exports.resource');

    Route::resource('portfolios', PortfolioController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::resource('users', UserController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::resource('assets', AssetController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::resource('tenants', TenantController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::resource('leases', LeaseController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::post('/leases/{lease}/signed-contract', [LeaseController::class, 'uploadSignedContract'])->name('leases.signed-contract');
    Route::get('/leases/{lease}/contract', [LeaseController::class, 'contract'])->name('leases.contract');
    Route::get('/leases/{lease}/statement', [LeaseController::class, 'statement'])->name('leases.statement');

    Route::resource('payments', PaymentController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::get('/payments/{payment}/receipt', [PaymentController::class, 'receipt'])->name('payments.receipt');

    Route::resource('maintenance-requests', MaintenanceRequestController::class)
        ->parameters(['maintenance-requests' => 'maintenanceRequest'])
        ->only(['index', 'store', 'update', 'destroy']);

    Route::resource('expenses', ExpenseEntryController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::resource('documents', DocumentController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::get('/documents/{document}/download', [DocumentController::class, 'download'])->name('documents.download');

    Route::get('/cms', [CmsPageController::class, 'index'])->name('cms.index');
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
    Route::resource('media-files', MediaFileController::class)->only(['index', 'store', 'update', 'destroy']);
});
