<?php

namespace App\Modules\Exports\Actions;

use App\Models\User;
use App\Modules\Assets\Actions\AssetWorkbookExport;
use App\Modules\Cms\Actions\CmsPageWorkbookExport;
use App\Modules\Documents\Actions\DocumentWorkbookExport;
use App\Modules\Expenses\Actions\ExpenseWorkbookExport;
use App\Modules\Exports\Contracts\ResourceExporter;
use App\Modules\Leases\Actions\LeaseWorkbookExport;
use App\Modules\Maintenance\Actions\MaintenanceWorkbookExport;
use App\Modules\Media\Actions\MediaFileWorkbookExport;
use App\Modules\Payments\Actions\PaymentWorkbookExport;
use App\Modules\Portfolios\Actions\PortfolioWorkbookExport;
use App\Modules\Tenants\Actions\TenantWorkbookExport;
use App\Modules\Users\Actions\UserWorkbookExport;
use App\Support\PortfolioModules;
use Illuminate\Contracts\Container\Container;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ResourceExportRegistry
{
    /** @var array<string, class-string<ResourceExporter>> */
    private const EXPORTERS = [
        'assets' => AssetWorkbookExport::class,
        'tenants' => TenantWorkbookExport::class,
        'leases' => LeaseWorkbookExport::class,
        'payments' => PaymentWorkbookExport::class,
        'maintenance-requests' => MaintenanceWorkbookExport::class,
        'expenses' => ExpenseWorkbookExport::class,
        'documents' => DocumentWorkbookExport::class,
        'users' => UserWorkbookExport::class,
        'portfolios' => PortfolioWorkbookExport::class,
        'cms-pages' => CmsPageWorkbookExport::class,
        'media-files' => MediaFileWorkbookExport::class,
    ];

    public function __construct(private readonly Container $container) {}

    public function download(
        string $resource,
        Request $request,
        User $actor,
    ): BinaryFileResponse {
        $exporterClass = self::EXPORTERS[$resource] ?? null;
        abort_unless($exporterClass !== null, 404);

        $module = PortfolioModules::exportResourceModule($resource);
        abort_unless(
            $module === null || PortfolioModules::enabledForUser($actor, $module),
            403,
            trans('app.errors.module_disabled'),
        );

        $exporter = $this->container->make($exporterClass);

        return $exporter->download($request, $actor);
    }
}
