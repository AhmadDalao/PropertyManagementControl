<?php

namespace App\Modules\Documents\Actions;

use App\Models\Document;
use App\Models\Payment;
use App\Models\User;
use App\Modules\Documents\Support\DocumentAccess;
use App\Modules\Documents\Support\DocumentAttachments;
use App\Modules\Documents\Support\DocumentAttributes;
use App\Modules\Documents\Support\DocumentInputGuard;
use App\Modules\Notifications\Actions\SendOperationalActivityNotification;
use App\Modules\Payments\Support\PaymentAccess;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class UpdateDocument
{
    public function __construct(
        private readonly DocumentAccess $access,
        private readonly DocumentAttachments $attachments,
        private readonly DocumentAttributes $attributes,
        private readonly DocumentInputGuard $input,
        private readonly SendOperationalActivityNotification $notifications,
        private readonly PaymentAccess $payments,
    ) {}

    /** @param array<string, mixed> $data */
    public function handle(User $actor, Document $document, array $data): Document
    {
        $this->access->ensureCanManage($actor, $document);
        $data = $this->input->validateUpdate($data);

        $wasPublic = (bool) $document->is_public;
        $updated = DB::transaction(function () use ($actor, $document, $data): Document {
            $lockedDocument = Document::query()->lockForUpdate()->whereKey($document->id)->firstOrFail();
            $this->access->ensureCanManage($actor, $lockedDocument);
            $alias = $this->attachments->aliasForDocument($lockedDocument);

            if ($alias === null) {
                throw ValidationException::withMessages([
                    'type' => trans('app.errors.unsupported_document_attachment'),
                ]);
            }

            $lockedDocument->update($this->attributes->forUpdate($alias, $data));

            return $lockedDocument->fresh(['documentable']);
        }, 3);

        if (! $wasPublic && $updated->is_public) {
            $this->notifications->document($actor, $updated);
        }

        return $updated;
    }

    /** @param array<string, mixed> $data */
    public function reviewPaymentProof(
        User $actor,
        Payment $payment,
        Document $document,
        array $data,
    ): Document {
        $this->payments->ensureCanReviewProof($actor, $payment, $document);
        $data = $this->input->validatePaymentProofReview($data);

        $updated = DB::transaction(function () use ($actor, $payment, $document, $data): Document {
            $lockedPayment = Payment::query()->lockForUpdate()->findOrFail($payment->id);
            $lockedDocument = Document::query()->lockForUpdate()->findOrFail($document->id);
            $this->payments->ensureCanReviewProof($actor, $lockedPayment, $lockedDocument);
            $lockedDocument->update([
                'meta_json' => [
                    ...(array) $lockedDocument->meta_json,
                    'review_status' => $data['status'],
                    'review_note' => trim((string) ($data['review_note'] ?? '')) ?: null,
                    'reviewed_at' => now()->toIso8601String(),
                    'reviewed_by_user_id' => $actor->id,
                ],
            ]);

            return $lockedDocument->fresh(['documentable', 'uploadedBy']);
        }, 3);

        $this->notifications->payment(
            $actor,
            $payment,
            $data['status'] === 'accepted' ? 'payment_proof_accepted' : 'payment_proof_rejected',
        );

        return $updated;
    }
}
