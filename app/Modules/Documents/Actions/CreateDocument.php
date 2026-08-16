<?php

namespace App\Modules\Documents\Actions;

use App\Models\Document;
use App\Models\Payment;
use App\Models\User;
use App\Modules\Documents\Support\DocumentAccess;
use App\Modules\Documents\Support\DocumentAttachmentResolver;
use App\Modules\Documents\Support\DocumentAttributes;
use App\Modules\Documents\Support\DocumentFileStorage;
use App\Modules\Documents\Support\DocumentInputGuard;
use App\Modules\Notifications\Actions\SendOperationalActivityNotification;
use App\Modules\Payments\Support\PaymentAccess;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Throwable;

final class CreateDocument
{
    public function __construct(
        private readonly DocumentAccess $access,
        private readonly DocumentAttachmentResolver $attachments,
        private readonly DocumentAttributes $attributes,
        private readonly DocumentFileStorage $files,
        private readonly DocumentInputGuard $input,
        private readonly SendOperationalActivityNotification $notifications,
        private readonly PaymentAccess $payments,
    ) {}

    /** @param array<string, mixed> $data */
    public function handle(User $actor, array $data): Document
    {
        $this->access->ensureManager($actor);
        $data = $this->input->validateCreate($data);
        $alias = (string) $data['documentable_type'];
        $attachment = $this->attachments->resolve($actor, $alias, (int) $data['documentable_id']);
        $file = $data['file'];
        assert($file instanceof UploadedFile);
        $storedFile = $this->files->store($file, (int) $attachment->getAttribute('portfolio_id'));

        try {
            $document = DB::transaction(function () use ($actor, $alias, $data, $storedFile): Document {
                $attachment = $this->attachments->resolve(
                    $actor,
                    $alias,
                    (int) $data['documentable_id'],
                    lock: true,
                );

                return Document::query()->create(
                    $this->attributes->forCreate($actor, $attachment, $alias, $storedFile, $data),
                );
            }, 3);
            $this->notifications->document($actor, $document);

            return $document;
        } catch (Throwable $exception) {
            $this->files->delete($storedFile->disk, $storedFile->path);

            throw $exception;
        }
    }

    /** @param array<string, mixed> $data */
    public function paymentProof(User $actor, Payment $payment, array $data): Document
    {
        $this->payments->ensureCanSubmitProof($actor, $payment);
        $data = $this->input->validatePaymentProof($data);
        $file = $data['proof'];
        assert($file instanceof UploadedFile);
        $storedFile = $this->files->store($file, (int) $payment->portfolio_id);

        try {
            $document = DB::transaction(function () use ($actor, $payment, $data, $storedFile): Document {
                $lockedPayment = Payment::query()->lockForUpdate()->findOrFail($payment->id);
                $this->payments->ensureCanSubmitProof($actor, $lockedPayment);
                $submittedAt = now()->toIso8601String();

                $lockedPayment->documents()
                    ->where('type', 'payment_proof')
                    ->whereIn('meta_json->review_status', ['pending', 'rejected'])
                    ->get()
                    ->each(function (Document $proof) use ($actor, $submittedAt): void {
                        $proof->update([
                            'meta_json' => [
                                ...(array) $proof->meta_json,
                                'review_status' => 'superseded',
                                'superseded_at' => $submittedAt,
                                'superseded_by_user_id' => $actor->id,
                            ],
                        ]);
                    });

                $reference = $lockedPayment->reference ?: '#'.$lockedPayment->id;

                return Document::query()->create([
                    'portfolio_id' => $lockedPayment->portfolio_id,
                    'uploaded_by_user_id' => $actor->id,
                    'documentable_type' => $lockedPayment->getMorphClass(),
                    'documentable_id' => $lockedPayment->id,
                    'type' => 'payment_proof',
                    'title_en' => trans('app.payments.proof_title', ['reference' => $reference], locale: 'en'),
                    'title_ar' => trans('app.payments.proof_title', ['reference' => $reference], locale: 'ar'),
                    'issued_on' => now()->toDateString(),
                    'disk' => $storedFile->disk,
                    'file_path' => $storedFile->path,
                    'original_name' => $storedFile->originalName,
                    'mime_type' => $storedFile->mimeType,
                    'file_size' => $storedFile->size,
                    'is_public' => false,
                    'meta_json' => [
                        'review_status' => 'pending',
                        'submission_note' => trim((string) ($data['note'] ?? '')) ?: null,
                        'submitted_at' => $submittedAt,
                        'submitted_by_role' => $actor->getRoleNames()->first(),
                    ],
                ]);
            }, 3);

            $this->notifications->payment($actor, $payment, 'payment_proof_submitted');

            return $document;
        } catch (Throwable $exception) {
            $this->files->delete($storedFile->disk, $storedFile->path);

            throw $exception;
        }
    }
}
