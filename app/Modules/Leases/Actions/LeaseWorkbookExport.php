<?php

namespace App\Modules\Leases\Actions;

use App\Models\Lease;
use App\Models\User;
use App\Modules\Exports\Contracts\ResourceExporter;
use App\Modules\Exports\Support\ResourceWorkbook;
use App\Modules\Leases\Queries\LeaseIndexQuery;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class LeaseWorkbookExport implements ResourceExporter
{
    public function __construct(
        private readonly LeaseIndexQuery $leases,
        private readonly ResourceWorkbook $workbook,
    ) {}

    public function download(Request $request, User $actor): BinaryFileResponse
    {
        return $this->workbook->download('leases', [
            'Code',
            'Tenant',
            'Asset',
            'Status',
            'Frequency',
            'Start',
            'End',
            'Rent',
            'Paid',
            'Balance',
            'Currency',
        ], $this->leases->forExport($request, $actor), fn (Lease $lease): array => [
            $lease->code,
            $lease->tenantProfile?->user?->name,
            $this->workbook->localized($lease->leaseable, 'title_en', 'title_ar'),
            $this->workbook->option($lease->status),
            $this->workbook->option($lease->payment_frequency),
            $this->workbook->date($lease->started_at),
            $this->workbook->date($lease->ends_at),
            $lease->rent_amount,
            $lease->total_paid,
            $lease->balance_remaining,
            $lease->currency,
        ]);
    }
}
