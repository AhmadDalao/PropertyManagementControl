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
            'Name',
            'Email',
            'Phone',
            'Profile',
            'National ID',
            'Company',
            'Status',
            'Portfolio',
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
