<?php

namespace App\Modules\Payments\Actions;

use App\Models\Payment;
use App\Models\User;
use App\Modules\Payments\Support\PaymentAccess;
use App\Modules\Shared\PrivatePdfDocuments;
use App\Support\BilingualPdf;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PaymentReceipts
{
    public function __construct(
        private readonly PaymentAccess $access,
        private readonly BilingualPdf $pdf,
        private readonly PrivatePdfDocuments $documents,
    ) {}

    public function download(User $actor, Payment $payment): StreamedResponse
    {
        $this->access->ensureCanAccess($actor, $payment);
        abort_if(
            $payment->status !== 'posted',
            422,
            trans('app.errors.receipt_requires_posted_payment'),
        );
        $payment->loadMissing([
            'lease.portfolio.owner',
            'lease.leaseable',
            'tenantProfile.user',
            'recordedBy',
            'allocations.leaseInstallment',
        ]);

        $reference = $payment->reference ?: (string) $payment->id;
        $safeReference = Str::slug($reference) ?: (string) $payment->id;
        $fileName = "receipt-{$safeReference}.pdf";
        $content = $this->pdf->loadView('pdf.receipt', ['payment' => $payment])
            ->setPaper('a4')
            ->output();
        $documentOwner = $payment->recordedBy ?? $actor;

        $this->documents->replace(
            $payment,
            $documentOwner,
            $payment->portfolio_id,
            'receipt',
            "Payment receipt {$reference}",
            "إيصال الدفعة {$reference}",
            "generated/payments/{$payment->id}",
            $fileName,
            $content,
            true,
        );

        return response()->streamDownload(
            static fn () => print ($content),
            $fileName,
            ['Content-Type' => 'application/pdf'],
        );
    }
}
