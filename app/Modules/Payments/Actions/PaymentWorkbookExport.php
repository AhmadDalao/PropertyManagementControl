<?php

namespace App\Modules\Payments\Actions;

use App\Models\Payment;
use App\Models\User;
use App\Modules\Exports\Contracts\ResourceExporter;
use App\Modules\Exports\Support\ResourceWorkbook;
use App\Modules\Payments\Queries\PaymentIndexQuery;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PaymentWorkbookExport implements ResourceExporter
{
    public function __construct(
        private readonly PaymentIndexQuery $payments,
        private readonly ResourceWorkbook $workbook,
    ) {}

    public function download(Request $request, User $actor): BinaryFileResponse
    {
        return $this->workbook->download('payments', [
            'Reference',
            'Tenant',
            'Lease',
            'Date',
            'Type',
            'Method',
            'Status',
            'Amount',
            'Currency',
        ], $this->payments->forExport($request, $actor), fn (Payment $payment): array => [
            $payment->reference ?: '#'.$payment->id,
            $payment->tenantProfile?->user?->name,
            $payment->lease?->code,
            $this->workbook->date($payment->received_on),
            $this->workbook->option($payment->type),
            $this->workbook->option($payment->method),
            $this->workbook->option($payment->status),
            $payment->amount,
            $payment->currency,
        ]);
    }
}
