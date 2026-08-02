<?php

namespace App\Modules\Tenants\Actions;

use App\Models\TenantProfile;
use App\Models\User;
use App\Modules\Exports\Contracts\ResourceExporter;
use App\Modules\Exports\Support\ResourceWorkbook;
use App\Modules\Tenants\Queries\TenantIndexQuery;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class TenantWorkbookExport implements ResourceExporter
{
    public function __construct(
        private readonly TenantIndexQuery $tenants,
        private readonly ResourceWorkbook $workbook,
    ) {}

    public function download(Request $request, User $actor): BinaryFileResponse
    {
        return $this->workbook->download('tenants', [
            trans('app.tenants.name'),
            trans('app.tenants.email'),
            trans('app.tenants.phone'),
            trans('app.tenants.profile_type'),
            trans('app.tenants.national_id'),
            trans('app.tenants.company_name'),
            trans('app.tenants.status'),
            trans('app.tenants.portfolio'),
        ], $this->tenants->forExport($request, $actor), fn (TenantProfile $tenant): array => [
            $tenant->user?->name,
            $tenant->user?->email,
            $tenant->user?->phone,
            $this->workbook->option($tenant->profile_type),
            $tenant->national_id,
            $tenant->company_name,
            $this->workbook->option($tenant->status),
            $this->workbook->localized($tenant->portfolio, 'name_en', 'name_ar'),
        ]);
    }
}
