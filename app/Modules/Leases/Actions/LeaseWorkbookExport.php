<?php

namespace App\Modules\Leases\Actions;

use App\Models\Lease;
use App\Models\User;
use App\Modules\Exports\Contracts\ResourceExporter;
use App\Modules\Exports\Support\ResourceWorkbook;
use App\Modules\Leases\Queries\LeaseIndexQuery;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class LeaseWorkbookExport implements ResourceExporter
{
    public function __construct(
        private readonly LeaseIndexQuery $leases,
        private readonly ResourceWorkbook $workbook,
    ) {}

    public function download(Request $request, User $actor): BinaryFileResponse
    {
        return $this->workbook->download('leases', [
            $this->label('code'),
            $this->label('tenant'),
            $this->label('asset'),
            $this->label('status'),
            $this->label('frequency'),
            $this->label('start'),
            $this->label('end'),
            $this->label('rent'),
            $this->label('paid'),
            $this->label('balance'),
            $this->label('currency'),
        ], $this->leases->forExport($request, $actor), fn (Lease $lease): array => [
            $lease->code,
            $lease->tenantProfile?->user?->name,
            $this->workbook->localized($lease->leaseable, 'title_en', 'title_ar'),
            $this->workbook->option($lease->status),
            $this->label("frequency_{$lease->payment_frequency}"),
            $this->workbook->date($lease->started_at),
            $this->workbook->date($lease->ends_at),
            $lease->rent_amount,
            $lease->total_paid,
            $lease->balance_remaining,
            $lease->currency,
        ]);
    }

    private function label(string $key): string
    {
        $label = trans("app.leases.{$key}");

        return is_string($label) ? $label : $key;
    }
}
