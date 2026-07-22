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
            trans('app.payments.reference'),
            trans('app.payments.tenant'),
            trans('app.payments.lease'),
            trans('app.payments.received_on'),
            trans('app.payments.type'),
            trans('app.payments.method'),
            trans('app.payments.status'),
            trans('app.payments.amount'),
            trans('app.payments.currency'),
        ], $this->payments->forExport($request, $actor), fn (Payment $payment): array => [
            $payment->reference ?: '#'.$payment->id,
            $payment->tenantProfile?->user?->name,
            $payment->lease?->code,
            $this->workbook->date($payment->received_on),
            trans("app.payments.type_{$payment->type}"),
            trans("app.payments.method_{$payment->method}"),
            $this->workbook->option($payment->status),
            $payment->amount,
            $payment->currency,
        ]);
    }
}
