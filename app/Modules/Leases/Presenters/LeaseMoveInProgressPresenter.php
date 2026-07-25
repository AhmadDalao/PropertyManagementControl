<?php

namespace App\Modules\Leases\Presenters;

use App\Modules\Leases\Data\LeaseDetailData;
use App\Modules\Portfolios\Support\PortfolioModules;
use LogicException;

final class LeaseMoveInProgressPresenter
{
    /** @return array<string, mixed>|null */
    public function present(LeaseDetailData $data): ?array
    {
        $lease = $data->lease;

        if (! $data->adminMode || ! in_array($lease->status, ['draft', 'active'], true)) {
            return null;
        }

        $hasSignedPdf = $lease->documents->contains('type', 'signed_contract');
        $isActive = $lease->status === 'active';
        $requiresOpeningPayment = (float) $lease->rent_amount > 0
            || (float) $lease->deposit_amount > 0
            || (float) $lease->tax_amount > 0;
        $hasOpeningPayment = ! $requiresOpeningPayment
            || (int) $lease->getAttribute('posted_payments_count') > 0;
        $canOpenTenants = PortfolioModules::enabledForUser($data->actor, 'tenants');
        $canOpenDocuments = PortfolioModules::enabledForUser($data->actor, 'documents');
        $canOpenPayments = PortfolioModules::enabledForUser($data->actor, 'payments');

        $steps = [
            $this->step(
                trans('app.leases.move_in_tenant_title'),
                trans('app.leases.move_in_tenant_description', [
                    'tenant' => $lease->tenantProfile->user->name,
                ]),
                'complete',
                'bi-person-check',
                $canOpenTenants ? route('tenants.show', $lease->tenant_profile_id) : null,
                $canOpenTenants ? $this->copy('app.leases.open_tenant') : null,
            ),
            $this->step(
                trans('app.leases.move_in_terms_title'),
                trans('app.leases.move_in_terms_description', ['code' => $lease->code]),
                'complete',
                'bi-file-earmark-text',
                route('leases.edit', $lease),
                trans('app.leases.review_terms'),
            ),
            $this->step(
                trans('app.leases.move_in_contract_title'),
                trans('app.leases.move_in_contract_description'),
                'complete',
                'bi-file-earmark-pdf',
                route('leases.contract', $lease),
                trans('app.leases.download_contract'),
                true,
            ),
            $this->step(
                trans('app.leases.move_in_signature_title'),
                $this->copy($hasSignedPdf
                    ? 'app.leases.move_in_signature_complete'
                    : 'app.leases.move_in_signature_pending'),
                $hasSignedPdf ? 'complete' : 'current',
                'bi-file-earmark-lock',
                $canOpenDocuments
                    ? ($hasSignedPdf
                        ? route('leases.show', $lease).'?tab=documents'
                        : route('documents.create', [
                            'documentable_type' => 'lease',
                            'documentable_id' => $lease->id,
                            'type' => 'signed_contract',
                            'title_en' => "Signed contract {$lease->code}",
                            'title_ar' => "العقد الموقع {$lease->code}",
                        ]))
                    : null,
                $canOpenDocuments
                    ? $this->copy($hasSignedPdf
                        ? 'app.leases.open_documents'
                        : 'app.leases.upload_signed_pdf')
                    : null,
            ),
            $this->step(
                trans('app.leases.move_in_activation_title'),
                $this->copy($isActive
                    ? 'app.leases.move_in_activation_complete'
                    : 'app.leases.move_in_activation_pending'),
                $isActive ? 'complete' : ($hasSignedPdf ? 'current' : 'pending'),
                'bi-house-check',
                $isActive ? null : route('leases.edit', $lease),
                $isActive ? null : $this->copy('app.leases.review_activate'),
            ),
            $this->step(
                trans('app.leases.move_in_payment_title'),
                $this->copy(! $requiresOpeningPayment
                    ? 'app.leases.move_in_payment_not_required'
                    : ($hasOpeningPayment
                        ? 'app.leases.move_in_payment_complete'
                        : 'app.leases.move_in_payment_pending')),
                $hasOpeningPayment ? 'complete' : ($isActive ? 'current' : 'pending'),
                'bi-cash-coin',
                $isActive && $canOpenPayments
                    ? route('payments.create', ['lease_id' => $lease->id])
                    : null,
                $isActive && $canOpenPayments
                    ? $this->copy('app.leases.record_payment')
                    : null,
            ),
        ];
        $completed = collect($steps)->where('state', 'complete')->count();

        return [
            'eyebrow' => trans('app.leases.move_in_eyebrow'),
            'title' => trans('app.leases.move_in_title'),
            'description' => trans('app.leases.move_in_description'),
            'summary' => trans('app.leases.move_in_summary', [
                'completed' => $completed,
                'total' => count($steps),
            ]),
            'completed' => $completed,
            'total' => count($steps),
            'steps' => $steps,
        ];
    }

    /** @return array<string, mixed> */
    private function step(
        string $title,
        string $description,
        string $state,
        string $icon,
        ?string $href,
        ?string $actionLabel,
        bool $download = false,
    ): array {
        return compact('title', 'description', 'state', 'icon', 'href', 'actionLabel', 'download');
    }

    /** @param array<string, int|string> $replace */
    private function copy(string $key, array $replace = []): string
    {
        $copy = trans($key, $replace);

        if (! is_string($copy)) {
            throw new LogicException("Translation [{$key}] must resolve to a string.");
        }

        return $copy;
    }
}
