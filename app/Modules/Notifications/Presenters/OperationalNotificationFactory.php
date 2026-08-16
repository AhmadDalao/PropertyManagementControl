<?php

namespace App\Modules\Notifications\Presenters;

use App\Models\Asset;
use App\Models\Document;
use App\Models\Lease;
use App\Models\Payment;
use App\Models\User;
use App\Modules\Notifications\Data\OperationalNotificationData;
use Illuminate\Support\Number;

final class OperationalNotificationFactory
{
    public function payment(Payment $payment, User $actor, string $event): OperationalNotificationData
    {
        $payment->loadMissing('lease');
        $common = [
            'actor' => $actor->name,
            'reference' => $payment->reference ?: '#'.$payment->id,
            'lease' => $payment->lease?->code ?: '#'.$payment->lease_id,
        ];

        return $this->data(
            event: $event,
            en: [...$common, 'amount' => $this->money($payment, 'en')],
            ar: [...$common, 'amount' => $this->money($payment, 'ar-SA')],
            url: route('payments.show', $payment, false),
            icon: match ($event) {
                'payment_posted' => 'bi-receipt',
                'payment_proof_submitted' => 'bi-file-earmark-arrow-up',
                'payment_proof_accepted' => 'bi-file-earmark-check',
                'payment_proof_rejected' => 'bi-file-earmark-x',
                default => 'bi-arrow-counterclockwise',
            },
            tone: match ($event) {
                'payment_posted', 'payment_proof_accepted' => 'success',
                'payment_proof_submitted' => 'blue',
                default => 'danger',
            },
            type: 'payment',
            id: $payment->id,
            portfolioId: $payment->portfolio_id,
            actorId: $actor->id,
        );
    }

    public function lease(Lease $lease, User $actor, string $event): OperationalNotificationData
    {
        $lease->loadMissing('leaseable');
        $common = [
            'actor' => $actor->name,
            'lease' => $lease->code,
        ];

        return $this->data(
            event: $event,
            en: [...$common, 'asset' => $this->assetTitle($lease, 'en')],
            ar: [...$common, 'asset' => $this->assetTitle($lease, 'ar')],
            url: route('leases.show', $lease, false),
            icon: $event === 'lease_terminated' ? 'bi-file-earmark-x' : 'bi-file-earmark-check',
            tone: match ($event) {
                'lease_activated' => 'success',
                'lease_terminated' => 'danger',
                'lease_renewal_created' => 'blue',
                default => 'warning',
            },
            type: 'lease',
            id: $lease->id,
            portfolioId: $lease->portfolio_id,
            actorId: $actor->id,
        );
    }

    public function document(Document $document, User $actor): OperationalNotificationData
    {
        $typeKey = "app.documents.options.{$document->type}";
        $common = [
            'actor' => $actor->name,
            'document' => $document->title_en ?: $document->original_name,
        ];

        return $this->data(
            event: 'document_available',
            en: [...$common, 'document_type' => trans($typeKey, locale: 'en')],
            ar: [
                ...$common,
                'document' => $document->title_ar ?: $document->original_name,
                'document_type' => trans($typeKey, locale: 'ar'),
            ],
            url: route('documents.show', $document, false),
            icon: 'bi-file-earmark-pdf',
            tone: 'blue',
            type: 'document',
            id: $document->id,
            portfolioId: $document->portfolio_id,
            actorId: $actor->id,
        );
    }

    /**
     * @param  array<string, scalar|null>  $en
     * @param  array<string, scalar|null>  $ar
     */
    private function data(
        string $event,
        array $en,
        array $ar,
        string $url,
        string $icon,
        string $tone,
        string $type,
        int $id,
        int $portfolioId,
        int $actorId,
    ): OperationalNotificationData {
        return new OperationalNotificationData(
            $event,
            $en,
            $ar,
            $url,
            $icon,
            $tone,
            $type,
            $id,
            $portfolioId,
            $actorId,
        );
    }

    private function money(Payment $payment, string $locale): string
    {
        $formatted = Number::currency(
            (float) $payment->amount,
            in: (string) $payment->currency,
            locale: $locale,
        );

        return $formatted !== false
            ? $formatted
            : number_format((float) $payment->amount, 2).' '.$payment->currency;
    }

    private function assetTitle(Lease $lease, string $locale): string
    {
        $asset = $lease->leaseable;

        if (! $asset instanceof Asset) {
            return '#'.$lease->leaseable_id;
        }

        return trim((string) ($locale === 'ar' ? $asset->title_ar : $asset->title_en))
            ?: $asset->code;
    }
}
