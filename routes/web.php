<?php

use App\Http\Controllers\ActionCenterController;
use App\Http\Controllers\AdminExportController;
use App\Http\Controllers\AssetController;
use App\Http\Controllers\AssetStructureController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\CmsPageController;
use App\Http\Controllers\CmsPageSectionController;
use App\Http\Controllers\CmsSectionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentationController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\ExpenseEntryController;
use App\Http\Controllers\GlobalSearchController;
use App\Http\Controllers\LeaseController;
use App\Http\Controllers\LeaseMoveOutController;
use App\Http\Controllers\LeaseRenewalController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\MaintenanceAttachmentController;
use App\Http\Controllers\MaintenanceRequestController;
use App\Http\Controllers\MaintenanceVendorController;
use App\Http\Controllers\MaintenanceWorkOrderController;
use App\Http\Controllers\ManagerPropertyAssignmentController;
use App\Http\Controllers\MediaFileController;
use App\Http\Controllers\NavigationItemController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PortfolioControlController;
use App\Http\Controllers\PortfolioController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PropertyExplorerController;
use App\Http\Controllers\PropertyMapController;
use App\Http\Controllers\PublicSiteController;
use App\Http\Controllers\RentCollectionController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ReportStatementController;
use App\Http\Controllers\ShowcaseDataController;
use App\Http\Controllers\SystemReadinessController;
use App\Http\Controllers\TenantController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WordingController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PublicSiteController::class, 'home'])->name('home');
Route::get('/pages/{slug}', [PublicSiteController::class, 'show'])->name('pages.show');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
    Route::get('/forgot-password', [ForgotPasswordController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [ForgotPasswordController::class, 'store'])->name('password.email');
    Route::get('/reset-password/{token}', [ResetPasswordController::class, 'create'])->name('password.reset');
    Route::post('/reset-password', [ResetPasswordController::class, 'store'])->name('password.update');
});

Route::post('/locale/{locale}', [LocaleController::class, 'update'])->name('locale.update');

Route::middleware(['auth', 'account.active', 'password.changed', 'property.context'])->group(function () {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/portfolio-control', PortfolioControlController::class)
        ->name('portfolio-control.index')
        ->middleware('portfolio.module:assets');
    Route::get('/action-center', [ActionCenterController::class, 'index'])
        ->name('action-center.index');
    Route::get('/action-center/export', [ActionCenterController::class, 'export'])
        ->name('action-center.export');
    Route::get('/profile', [ProfileController::class, 'index'])->name('profile.index');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])
        ->middleware('throttle:6,1')
        ->name('profile.password');
    Route::get('/documentation', [DocumentationController::class, 'index'])->name('documentation.index');
    Route::get('/documentation/{guide}', [DocumentationController::class, 'show'])->name('documentation.show');
    Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
    Route::get('/audit-logs/export', [AuditLogController::class, 'export'])->name('audit-logs.export');
    Route::get('/global-search', GlobalSearchController::class)->name('global-search');
    Route::get('/exports/{resource}', AdminExportController::class)->name('exports.resource');
    Route::get('/property-map', PropertyMapController::class)->name('property-map.index')->middleware('portfolio.module:assets');
    Route::get('/property-explorer', PropertyExplorerController::class)->name('property-explorer.index')->middleware('portfolio.module:assets');

    Route::resource('portfolios', PortfolioController::class)->only(['index', 'create', 'store', 'show', 'edit', 'update', 'destroy']);
    Route::resource('users', UserController::class)->only(['index', 'create', 'store', 'show', 'edit', 'update', 'destroy'])->middleware('portfolio.module:users')->middlewareFor(['create', 'store'], 'property.assigned');
    Route::get('/users/{user}/property-assignments', [ManagerPropertyAssignmentController::class, 'edit'])
        ->name('users.property-assignments.edit')
        ->middleware('portfolio.module:users');
    Route::put('/users/{user}/property-assignments', [ManagerPropertyAssignmentController::class, 'update'])
        ->name('users.property-assignments.update')
        ->middleware('portfolio.module:users');
    Route::get('/assets/building-setup', [AssetStructureController::class, 'create'])
        ->name('assets.structure.create')
        ->middleware('portfolio.module:assets');
    Route::post('/assets/building-setup', [AssetStructureController::class, 'store'])
        ->name('assets.structure.store')
        ->middleware('portfolio.module:assets');
    Route::resource('assets', AssetController::class)->only(['index', 'create', 'store', 'show', 'edit', 'update', 'destroy'])->middleware('portfolio.module:assets')->middlewareFor(['create', 'store'], 'property.assigned');
    Route::resource('tenants', TenantController::class)->only(['index', 'create', 'store', 'show', 'edit', 'update', 'destroy'])->middleware('portfolio.module:tenants')->middlewareFor(['create', 'store'], 'property.assigned');
    Route::resource('leases', LeaseController::class)->only(['index', 'create', 'store', 'show', 'edit', 'update', 'destroy'])->middleware('portfolio.module:leases')->middlewareFor(['create', 'store'], 'property.assigned');
    Route::get('/leases/{lease}/renew', [LeaseController::class, 'renew'])->name('leases.renew')->middleware('portfolio.module:leases');
    Route::post('/leases/{lease}/signed-contract', [LeaseController::class, 'uploadSignedContract'])->name('leases.signed-contract')->middleware('portfolio.module:leases');
    Route::get('/leases/{lease}/contract', [LeaseController::class, 'contract'])->name('leases.contract')->middleware('portfolio.module:leases');
    Route::get('/leases/{lease}/statement', [LeaseController::class, 'statement'])->name('leases.statement')->middleware('portfolio.module:leases');
    Route::get('/lease-move-outs', [LeaseMoveOutController::class, 'index'])
        ->name('lease-move-outs.index')
        ->middleware('portfolio.module:leases');
    Route::get('/leases/{lease}/move-out', [LeaseMoveOutController::class, 'edit'])
        ->name('leases.move-out.edit')
        ->middleware('portfolio.module:leases');
    Route::put('/leases/{lease}/move-out', [LeaseMoveOutController::class, 'update'])
        ->name('leases.move-out.update')
        ->middleware('portfolio.module:leases');
    Route::post('/leases/{lease}/move-out/complete', [LeaseMoveOutController::class, 'complete'])
        ->name('leases.move-out.complete')
        ->middleware('portfolio.module:leases');
    Route::delete('/leases/{lease}/move-out', [LeaseMoveOutController::class, 'destroy'])
        ->name('leases.move-out.destroy')
        ->middleware('portfolio.module:leases');

    Route::get('/lease-renewals', [LeaseRenewalController::class, 'index'])
        ->name('lease-renewals.index')
        ->middleware('portfolio.module:leases');

    Route::get('/rent-collection', [RentCollectionController::class, 'index'])
        ->name('rent-collection.index')
        ->middleware('portfolio.module:payments');
    Route::get('/rent-collection/{leaseInstallment}/follow-up', [RentCollectionController::class, 'followUp'])
        ->name('rent-collection.follow-up')
        ->middleware('portfolio.module:payments');
    Route::post('/rent-collection/{leaseInstallment}/follow-ups', [RentCollectionController::class, 'storeFollowUp'])
        ->name('rent-collection.follow-ups.store')
        ->middleware('portfolio.module:payments');
    Route::resource('payments', PaymentController::class)->only(['index', 'create', 'store', 'show', 'edit', 'update', 'destroy'])->middleware('portfolio.module:payments')->middlewareFor(['create', 'store'], 'property.assigned');
    Route::get('/payments/{payment}/receipt', [PaymentController::class, 'receipt'])->name('payments.receipt')->middleware('portfolio.module:payments');

    Route::get(
        '/maintenance-requests/{maintenanceRequest}/attachments/create',
        [MaintenanceAttachmentController::class, 'create'],
    )->name('maintenance-requests.attachments.create')
        ->middleware('portfolio.module:maintenance');
    Route::post(
        '/maintenance-requests/{maintenanceRequest}/attachments',
        [MaintenanceAttachmentController::class, 'store'],
    )->name('maintenance-requests.attachments.store')
        ->middleware('portfolio.module:maintenance');
    Route::get(
        '/maintenance-requests/{maintenanceRequest}/attachments/{maintenanceAttachment}',
        [MaintenanceAttachmentController::class, 'show'],
    )->name('maintenance-requests.attachments.show')
        ->middleware('portfolio.module:maintenance');
    Route::resource('maintenance-requests', MaintenanceRequestController::class)
        ->parameters(['maintenance-requests' => 'maintenanceRequest'])
        ->only(['index', 'create', 'store', 'show', 'edit', 'update', 'destroy'])
        ->middleware('portfolio.module:maintenance')
        ->middlewareFor(['create', 'store'], 'property.assigned');
    Route::get(
        '/maintenance-requests/{maintenanceRequest}/work-orders/create',
        [MaintenanceWorkOrderController::class, 'create'],
    )->name('maintenance-requests.work-orders.create')
        ->middleware('portfolio.module:maintenance');
    Route::post(
        '/maintenance-requests/{maintenanceRequest}/work-orders',
        [MaintenanceWorkOrderController::class, 'store'],
    )->name('maintenance-requests.work-orders.store')
        ->middleware('portfolio.module:maintenance');
    Route::get(
        '/maintenance-work-orders/{maintenanceWorkOrder}',
        [MaintenanceWorkOrderController::class, 'show'],
    )->name('maintenance-work-orders.show')
        ->middleware('portfolio.module:maintenance');
    Route::get(
        '/maintenance-work-orders/{maintenanceWorkOrder}/edit',
        [MaintenanceWorkOrderController::class, 'edit'],
    )->name('maintenance-work-orders.edit')
        ->middleware('portfolio.module:maintenance');
    Route::put(
        '/maintenance-work-orders/{maintenanceWorkOrder}',
        [MaintenanceWorkOrderController::class, 'update'],
    )->name('maintenance-work-orders.update')
        ->middleware('portfolio.module:maintenance');
    Route::resource('maintenance-vendors', MaintenanceVendorController::class)
        ->parameters(['maintenance-vendors' => 'maintenanceVendor'])
        ->only(['index', 'create', 'store', 'show', 'edit', 'update', 'destroy'])
        ->middleware('portfolio.module:maintenance');

    Route::resource('expenses', ExpenseEntryController::class)->only(['index', 'create', 'store', 'show', 'edit', 'update', 'destroy'])->middleware('portfolio.module:expenses')->middlewareFor(['create', 'store'], 'property.assigned');
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index')->middleware('portfolio.module:reports');
    Route::get('/reports/export', [ReportController::class, 'export'])->name('reports.export')->middleware('portfolio.module:reports');
    Route::get('/reports/statement', [ReportStatementController::class, 'show'])->name('reports.statement')->middleware('portfolio.module:reports');
    Route::get('/reports/statement.pdf', [ReportStatementController::class, 'pdf'])->name('reports.statement.pdf')->middleware('portfolio.module:reports');
    Route::get('/reports/statement.docx', [ReportStatementController::class, 'word'])->name('reports.statement.word')->middleware('portfolio.module:reports');
    Route::post('/reports/presets', [ReportController::class, 'storePreset'])->name('reports.presets.store')->middleware('portfolio.module:reports');
    Route::delete('/reports/presets/{reportPreset}', [ReportController::class, 'destroyPreset'])->name('reports.presets.destroy')->middleware('portfolio.module:reports');
    Route::resource('documents', DocumentController::class)->only(['index', 'create', 'store', 'show', 'edit', 'update', 'destroy'])->middleware('portfolio.module:documents')->middlewareFor(['create', 'store'], 'property.assigned');
    Route::get('/documents/{document}/download', [DocumentController::class, 'download'])->name('documents.download')->middleware('portfolio.module:documents');

    Route::get('/cms', [CmsPageController::class, 'index'])->name('cms.index');
    Route::get('/wording', [WordingController::class, 'index'])->name('wording.index');
    Route::put('/wording', [WordingController::class, 'update'])->name('wording.update');
    Route::delete('/wording', [WordingController::class, 'destroy'])->name('wording.destroy');
    Route::get('/system/showcase-data', [ShowcaseDataController::class, 'index'])->name('showcase-data.index');
    Route::post('/system/showcase-data', [ShowcaseDataController::class, 'store'])->name('showcase-data.store');
    Route::post('/system/showcase-data/{showcaseDataset}/retry', [ShowcaseDataController::class, 'retry'])->name('showcase-data.retry');
    Route::delete('/system/showcase-data/{showcaseDataset}', [ShowcaseDataController::class, 'destroy'])->name('showcase-data.destroy');
    Route::get('/system/readiness', [SystemReadinessController::class, 'index'])->name('system-readiness.index');
    Route::put('/system/readiness/checks', [SystemReadinessController::class, 'update'])->name('system-readiness.update');
    Route::post('/system/readiness/test-email', [SystemReadinessController::class, 'testEmail'])
        ->middleware('throttle:3,10')
        ->name('system-readiness.test-email');
    Route::get('/cms/pages/create', [CmsPageController::class, 'create'])->name('cms.pages.create');
    Route::get('/cms/sections/create', [CmsSectionController::class, 'create'])->name('cms.sections.create');
    Route::get('/cms/sections/{cmsSection}/edit', [CmsSectionController::class, 'edit'])->name('cms.sections.edit');
    Route::get('/cms/navigation/create', [NavigationItemController::class, 'create'])->name('cms.navigation.create');
    Route::get('/cms/navigation/{navigationItem}/edit', [NavigationItemController::class, 'edit'])->name('cms.navigation.edit');
    Route::get('/cms/pages/{cmsPage}', [CmsPageController::class, 'builder'])->name('cms.pages.show');
    Route::get('/cms/pages/{cmsPage}/edit', [CmsPageController::class, 'edit'])->name('cms.pages.edit');
    Route::post('/cms/pages', [CmsPageController::class, 'store'])->name('cms.pages.store');
    Route::put('/cms/pages/{cmsPage}', [CmsPageController::class, 'update'])->name('cms.pages.update');
    Route::delete('/cms/pages/{cmsPage}', [CmsPageController::class, 'destroy'])->name('cms.pages.destroy');
    Route::post('/cms/sections', [CmsSectionController::class, 'store'])->name('cms.sections.store');
    Route::put('/cms/sections/{cmsSection}', [CmsSectionController::class, 'update'])->name('cms.sections.update');
    Route::delete('/cms/sections/{cmsSection}', [CmsSectionController::class, 'destroy'])->name('cms.sections.destroy');
    Route::post('/cms/pages/{cmsPage}/sections', [CmsPageSectionController::class, 'store'])->name('cms.pages.sections.store');
    Route::put('/cms/pages/{cmsPage}/sections/reorder', [CmsPageSectionController::class, 'reorder'])->name('cms.pages.sections.reorder');
    Route::put('/cms/page-sections/{cmsPageSection}', [CmsPageSectionController::class, 'update'])->name('cms.page-sections.update');
    Route::delete('/cms/page-sections/{cmsPageSection}', [CmsPageSectionController::class, 'destroy'])->name('cms.page-sections.destroy');

    Route::resource('navigation-items', NavigationItemController::class)->only(['store', 'update', 'destroy']);
    Route::get('/media-files/{mediaFile}/file', [MediaFileController::class, 'file'])->name('media-files.file')->middleware('portfolio.module:media');
    Route::resource('media-files', MediaFileController::class)
        ->parameters(['media-files' => 'mediaFile'])
        ->only(['index', 'create', 'store', 'show', 'edit', 'update', 'destroy'])
        ->middleware('portfolio.module:media');
});
