<?php

namespace App\Modules\Payments\Support;

use App\Models\Document;
use App\Models\Payment;
use App\Models\User;
use App\Modules\Shared\Authorization\AssignedPropertyScope;

final class PaymentAccess
{
    public function __construct(private readonly AssignedPropertyScope $assignments) {}

    public function canManageSection(User $actor): bool
    {
        return $actor->hasAnyRole(['superadmin', 'owner', 'property_manager']);
    }

    public function canAccess(User $actor, Payment $payment): bool
    {
        if ($this->canManageSection($actor)) {
            return ($actor->hasRole('superadmin') || $actor->portfolio_id === $payment->portfolio_id)
                && $this->assignments->allowsPayment($actor, $payment);
        }

        return $actor->hasRole('tenant')
            && $payment->tenantProfile()->where('user_id', $actor->id)->exists();
    }

    public function canManage(User $actor, Payment $payment): bool
    {
        return $this->canManageSection($actor)
            && ($actor->hasRole('superadmin') || $actor->portfolio_id === $payment->portfolio_id)
            && $this->assignments->allowsPayment($actor, $payment);
    }

    public function canSubmitProof(User $actor, Payment $payment): bool
    {
        if (! $actor->hasRole('tenant')
            || $payment->status === 'void'
            || ! $this->canAccess($actor, $payment)) {
            return false;
        }

        return ! $payment->documents()
            ->where('type', 'payment_proof')
            ->where('meta_json->review_status', 'accepted')
            ->exists();
    }

    public function canReviewProof(User $actor, Payment $payment, Document $document): bool
    {
        return $this->canManage($actor, $payment)
            && $document->documentable_type === $payment->getMorphClass()
            && (int) $document->documentable_id === (int) $payment->id
            && $document->type === 'payment_proof'
            && data_get($document->meta_json, 'review_status', 'pending') === 'pending';
    }

    public function ensureCanSubmitProof(User $actor, Payment $payment): void
    {
        abort_unless(
            $actor->hasRole('tenant'),
            403,
            trans('app.errors.section_access_denied'),
        );
        $this->ensureCanAccess($actor, $payment);
        abort_unless(
            $this->canSubmitProof($actor, $payment),
            422,
            trans('app.errors.payment_proof_submission_unavailable'),
        );
    }

    public function ensureCanReviewProof(User $actor, Payment $payment, Document $document): void
    {
        abort_unless(
            $this->canReviewProof($actor, $payment, $document),
            403,
            trans('app.errors.payment_proof_review_denied'),
        );
    }

    public function ensureCanAccess(User $actor, Payment $payment): void
    {
        abort_unless($this->canAccess($actor, $payment), 403, trans('app.errors.receipt_access_denied'));
    }

    public function ensureCanManage(User $actor, Payment $payment): void
    {
        abort_unless($this->canManage($actor, $payment), 403, trans('app.errors.section_access_denied'));
    }

    public function ensureManager(User $actor): void
    {
        abort_unless($this->canManageSection($actor), 403, trans('app.errors.section_access_denied'));
    }
}
