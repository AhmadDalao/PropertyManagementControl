<?php

namespace App\Modules\Leases\Presenters;

use App\Models\Asset;
use App\Models\Lease;
use App\Models\TenantProfile;
use App\Models\User;
use App\Modules\Leases\Support\LeaseAccess;
use App\Modules\Leases\Support\LeaseOptions;
use App\Modules\Shared\PortfolioScope;
use App\Modules\Shared\ResourcePresenter;

class LeaseFormPresenter
{
    public function __construct(
        private readonly LeaseAccess $access,
        private readonly PortfolioScope $portfolios,
        private readonly ResourcePresenter $resources,
    ) {}

    /**
     * @param  array{portfolio_id?:mixed,tenant_profile_id?:mixed,asset_id?:mixed}  $defaults
     * @return array<string, mixed>
     */
    public function present(User $actor, ?Lease $lease = null, array $defaults = []): array
    {
        $this->access->ensureManager($actor);

        return $lease
            ? $this->editForm($lease)
            : $this->createForm($actor, $defaults);
    }

    /** @return array<string, mixed> */
    private function editForm(Lease $lease): array
    {
        return [
            'title' => trans('app.actions.edit').' '.$lease->code,
            'description' => 'Update the lease state, signature date, and notes. Financial edits stay locked once payments exist.',
            'backHref' => route('leases.show', $lease),
            'backLabel' => 'Lease detail',
            'action' => route('leases.update', $lease),
            'method' => 'put',
            'submitLabel' => 'Update lease',
            'fields' => [
                ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => $this->resources->fieldOptions(LeaseOptions::STATUSES)],
                ['name' => 'signed_at', 'label' => 'Signed at', 'type' => 'date'],
                ['name' => 'terms_en', 'label' => 'Approved terms in English', 'type' => 'textarea', 'help' => 'Use only portfolio-approved legal wording.'],
                ['name' => 'terms_ar', 'label' => 'Approved terms in Arabic', 'type' => 'textarea', 'help' => 'Use only portfolio-approved legal wording.'],
                ['name' => 'notes', 'label' => 'Notes', 'type' => 'textarea'],
                ['name' => 'resync_installments', 'label' => 'Regenerate installments', 'type' => 'checkbox', 'help' => 'Only works when no payments exist.'],
            ],
            'initialValues' => [
                'status' => $lease->status,
                'signed_at' => $lease->signed_at?->toDateString() ?? '',
                'terms_en' => data_get($lease->terms_json, 'en', ''),
                'terms_ar' => data_get($lease->terms_json, 'ar', ''),
                'notes' => $lease->notes ?? '',
                'resync_installments' => false,
            ],
        ];
    }

    /**
     * @param  array{portfolio_id?:mixed,tenant_profile_id?:mixed,asset_id?:mixed}  $defaults
     * @return array<string, mixed>
     */
    private function createForm(User $actor, array $defaults): array
    {
        $portfolioOptions = $this->portfolios->options($actor);
        $tenantOptions = $this->portfolios->apply(
            TenantProfile::query()->with('user')->whereNotNull('user_id')->orderBy('id'),
            $actor
        )->get()
            ->map(fn (TenantProfile $tenant) => $this->option(
                $tenant->id,
                $tenant->user->name ?? 'Tenant #'.$tenant->id,
            ))->all();
        $assetOptions = $this->portfolios->apply(
            Asset::query()
                ->where('rentable', true)
                ->where('status', 'active')
                ->orderBy('title_en'),
            $actor
        )->get()
            ->map(fn (Asset $asset) => $this->option(
                $asset->id,
                $this->resources->localized($asset->title_en, $asset->title_ar).' · '.$asset->code,
            ))->all();
        $fields = [];

        if ($actor->hasRole('superadmin')) {
            $fields[] = [
                'name' => 'portfolio_id',
                'label' => 'Portfolio',
                'type' => 'select',
                'options' => collect($portfolioOptions)
                    ->map(fn (array $portfolio) => $this->option($portfolio['id'], $portfolio['name']))
                    ->all(),
            ];
        }

        $fields = [
            ...$fields,
            ['name' => 'tenant_profile_id', 'label' => 'Tenant', 'type' => 'select', 'required' => true, 'options' => $tenantOptions],
            ['name' => 'asset_id', 'label' => 'Rentable asset', 'type' => 'select', 'required' => true, 'options' => $assetOptions],
            ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => $this->resources->fieldOptions(LeaseOptions::STATUSES)],
            ['name' => 'payment_frequency', 'label' => 'Payment frequency', 'type' => 'select', 'options' => $this->resources->fieldOptions(LeaseOptions::PAYMENT_FREQUENCIES)],
            ['name' => 'started_at', 'label' => 'Start date', 'type' => 'date', 'required' => true],
            ['name' => 'ends_at', 'label' => 'End date', 'type' => 'date', 'required' => true],
            ['name' => 'signed_at', 'label' => 'Signed date', 'type' => 'date'],
            ['name' => 'rent_amount', 'label' => 'Rent amount', 'type' => 'number', 'min' => 0, 'required' => true],
            ['name' => 'deposit_amount', 'label' => 'Deposit', 'type' => 'number', 'min' => 0],
            ['name' => 'tax_amount', 'label' => 'Tax', 'type' => 'number', 'min' => 0],
            ['name' => 'discount_amount', 'label' => 'Discount', 'type' => 'number', 'min' => 0],
            ['name' => 'currency', 'label' => 'Currency'],
            ['name' => 'billing_day', 'label' => 'Billing day', 'type' => 'number', 'min' => 1, 'max' => 31],
            ['name' => 'terms_en', 'label' => 'Approved terms in English', 'type' => 'textarea', 'help' => 'Use only portfolio-approved legal wording.'],
            ['name' => 'terms_ar', 'label' => 'Approved terms in Arabic', 'type' => 'textarea', 'help' => 'Use only portfolio-approved legal wording.'],
            ['name' => 'notes', 'label' => 'Notes', 'type' => 'textarea'],
        ];
        $fields = $this->resources->sectionFields($fields, [
            'Contract scope' => [
                'description' => 'Select the tenant and rentable asset before setting dates or money.',
                'fields' => ['portfolio_id', 'tenant_profile_id', 'asset_id', 'status'],
            ],
            'Contract period' => [
                'description' => 'Set the signed period and the installment frequency.',
                'fields' => ['payment_frequency', 'started_at', 'ends_at', 'signed_at'],
            ],
            'Rent schedule' => [
                'description' => 'These values generate the installment schedule after the lease is saved.',
                'fields' => ['rent_amount', 'deposit_amount', 'tax_amount', 'discount_amount', 'currency', 'billing_day'],
            ],
            'Internal notes' => [
                'description' => 'Keep operational context here; signed legal terms belong in the contract PDF.',
                'fields' => ['notes'],
            ],
            'Approved legal wording' => [
                'description' => 'Paste the bilingual clauses approved for this portfolio. The system does not invent legal terms.',
                'fields' => ['terms_en', 'terms_ar'],
            ],
        ]);
        $portfolioId = $defaults['portfolio_id']
            ?? $actor->portfolio_id
            ?? ($portfolioOptions[0]['id'] ?? '');

        return [
            'title' => 'Create lease',
            'description' => 'Attach one tenant to one rentable asset and generate the installment schedule.',
            'backHref' => route('leases.index'),
            'backLabel' => 'All leases',
            'action' => route('leases.store'),
            'method' => 'post',
            'submitLabel' => 'Create lease',
            'fields' => $fields,
            'initialValues' => [
                'portfolio_id' => (string) $portfolioId,
                'tenant_profile_id' => (string) ($defaults['tenant_profile_id'] ?? ($tenantOptions[0]['value'] ?? '')),
                'asset_id' => (string) ($defaults['asset_id'] ?? ($assetOptions[0]['value'] ?? '')),
                'status' => 'active',
                'payment_frequency' => 'monthly',
                'started_at' => now()->toDateString(),
                'ends_at' => now()->addYear()->toDateString(),
                'signed_at' => '',
                'rent_amount' => 0,
                'deposit_amount' => 0,
                'tax_amount' => 0,
                'discount_amount' => 0,
                'currency' => 'SAR',
                'billing_day' => 1,
                'terms_en' => '',
                'terms_ar' => '',
                'notes' => '',
            ],
        ];
    }

    /** @return array{value:string,label:string} */
    private function option(int|string $value, string $label): array
    {
        return ['value' => (string) $value, 'label' => $label];
    }
}
