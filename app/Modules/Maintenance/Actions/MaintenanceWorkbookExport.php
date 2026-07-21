<?php

namespace App\Modules\Maintenance\Actions;

use App\Models\MaintenanceRequest;
use App\Models\User;
use App\Modules\Exports\Contracts\ResourceExporter;
use App\Modules\Exports\Support\ResourceWorkbook;
use App\Modules\Maintenance\Queries\MaintenanceIndexQuery;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class MaintenanceWorkbookExport implements ResourceExporter
{
    public function __construct(
        private readonly MaintenanceIndexQuery $maintenance,
        private readonly ResourceWorkbook $workbook,
    ) {}

    public function download(Request $request, User $actor): BinaryFileResponse
    {
        return $this->workbook->download('maintenance-requests', [
            'ID',
            'Title',
            'Tenant',
            'Asset',
            'Category',
            'Priority',
            'Status',
            'Requested',
            'Assigned To',
        ], $this->maintenance->forExport($request, $actor), fn (MaintenanceRequest $maintenance): array => [
            $maintenance->id,
            $maintenance->title,
            $maintenance->tenantProfile?->user?->name,
            $this->workbook->localized($maintenance->asset, 'title_en', 'title_ar'),
            $this->workbook->option($maintenance->category),
            $this->workbook->option($maintenance->priority),
            $this->workbook->option($maintenance->status),
            $this->workbook->date($maintenance->requested_at, true),
            $maintenance->assignedTo?->name,
        ]);
    }
}
