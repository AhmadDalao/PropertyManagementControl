<?php

namespace App\Modules\Payments\Presenters;

use App\Models\Document;
use App\Models\PaymentAllocation;
use App\Modules\Payments\Data\PaymentDetailData;
use App\Modules\Payments\Support\PaymentAccess;
use App\Modules\Payments\Support\PaymentDisplayFormatter;

final class PaymentRelatedPresenter
{
    public function __construct(
        private readonly PaymentAccess $access,
        private readonly PaymentDisplayFormatter $format,
    ) {}

    /** @return array<int, array<string, mixed>> */
    public function present(PaymentDetailData $data): array
    {
        $payment = $data->payment;
        $installment = trans('app.payments.installment');
        $dueDate = trans('app.payments.due_date');
        $amount = trans('app.payments.amount');
        $count = (int) ($payment->getAttribute('allocations_count') ?? 0);

        return [[
            'title' => trans('app.payments.allocations'),
            'description' => $count > 50
                ? trans('app.payments.allocations_limited', ['shown' => 50, 'count' => $count])
                : trans('app.payments.allocations_help'),
            'columns' => [$installment, $dueDate, $amount],
            'rows' => $payment->allocations->map(fn (PaymentAllocation $allocation): array => [
                $installment => trans('app.payments.installment_number', [
                    'sequence' => data_get($allocation->leaseInstallment, 'sequence', '-'),
                ]),
                $dueDate => $this->format->date($allocation->leaseInstallment?->due_date),
                $amount => $this->format->money((float) $allocation->amount, $payment->currency),
            ])->all(),
            'emptyText' => trans('app.payments.no_allocations'),
        ]];
    }

    /** @return array<int, array<string, mixed>> */
    public function proofs(PaymentDetailData $data): array
    {
        return $data->payment->documents
            ->where('type', 'payment_proof')
            ->when(
                ! $data->adminMode,
                fn ($proofs) => $proofs->where('uploaded_by_user_id', $data->actor->id),
            )
            ->sortByDesc('id')
            ->map(function (Document $proof) use ($data): array {
                $status = (string) data_get($proof->meta_json, 'review_status', 'pending');

                return [
                    'id' => $proof->id,
                    'title' => app()->isLocale('ar')
                        ? ($proof->title_ar ?: $proof->title_en)
                        : ($proof->title_en ?: $proof->title_ar),
                    'original_name' => $proof->original_name,
                    'file_size' => (int) $proof->file_size,
                    'status' => $status,
                    'status_label' => trans("app.payments.proof_status_{$status}"),
                    'submission_note' => data_get($proof->meta_json, 'submission_note'),
                    'review_note' => data_get($proof->meta_json, 'review_note'),
                    'submitted_by' => $proof->uploadedBy?->name,
                    'submitted_at' => data_get($proof->meta_json, 'submitted_at') ?: $proof->created_at?->toIso8601String(),
                    'reviewed_at' => data_get($proof->meta_json, 'reviewed_at'),
                    'download_url' => route('documents.download', $proof),
                    'review_url' => $data->adminMode && $status === 'pending'
                        ? route('payments.proof.review', [$data->payment, $proof])
                        : null,
                ];
            })
            ->values()
            ->all();
    }

    /** @return array<string, mixed> */
    public function evidence(PaymentDetailData $data): array
    {
        $payment = $data->payment;

        return [
            'can_submit' => $this->access->canSubmitProof($data->actor, $payment),
            'upload_url' => route('payments.proof.store', $payment),
            'proofs' => $this->proofs($data),
            'receipt_url' => $payment->status === 'posted'
                ? route('payments.receipt', $payment)
                : null,
        ];
    }
}
