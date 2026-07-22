<?php

namespace App\Modules\Leases\Presenters;

use App\Models\Lease;
use App\Models\User;
use App\Modules\Leases\Data\LeaseFormData;
use App\Modules\Leases\Queries\LeaseFormOptionsQuery;
use App\Modules\Leases\Support\LeaseRenewalGuard;

final class LeaseRenewalFormPresenter
{
    public function __construct(
        private readonly LeaseRenewalGuard $guard,
        private readonly LeaseFormOptionsQuery $options,
        private readonly LeaseCreateFormPresenter $create,
    ) {}

    /** @return array<string, mixed> */
    public function present(User $actor, Lease $target): array
    {
        $source = $this->guard->sourceForForm($actor, $target);
        $start = $source->ends_at->copy()->addDay();
        $duration = max(1, (int) $source->started_at->diffInDays($source->ends_at));
        $data = $this->options->get($actor, defaults: [
            'portfolio_id' => $source->portfolio_id,
            'tenant_profile_id' => $source->tenant_profile_id,
            'include_tenant_id' => $source->tenant_profile_id,
            'asset_id' => $source->leaseable_id,
            'include_asset_id' => $source->leaseable_id,
            'renewed_from_lease_id' => $source->id,
            'status' => 'draft',
            'payment_frequency' => $source->payment_frequency,
            'started_at' => $start->toDateString(),
            'ends_at' => $start->copy()->addDays($duration)->toDateString(),
            'rent_amount' => $source->rent_amount,
            'deposit_amount' => 0,
            'tax_amount' => $source->tax_amount,
            'discount_amount' => $source->discount_amount,
            'currency' => $source->currency,
            'billing_day' => $source->billing_day,
            'terms_en' => data_get($source->terms_json, 'en', ''),
            'terms_ar' => data_get($source->terms_json, 'ar', ''),
        ]);
        abort_unless(
            collect($data->tenants)->contains('value', $source->tenant_profile_id)
                && collect($data->assets)->contains('value', $source->leaseable_id),
            409,
            trans('app.errors.lease_renewal_participants_unavailable'),
        );
        $renewalData = new LeaseFormData(
            actor: $data->actor,
            lease: null,
            defaults: $data->defaults,
            portfolioId: $data->portfolioId,
            portfolios: collect($data->portfolios)->where('value', $source->portfolio_id)->values()->all(),
            tenants: collect($data->tenants)->where('value', $source->tenant_profile_id)->values()->all(),
            assets: collect($data->assets)->where('value', $source->leaseable_id)->values()->all(),
        );
        $form = $this->create->present($renewalData);

        return [
            ...$form,
            'title' => trans('app.leases.renew_lease', ['code' => $source->code]),
            'description' => trans('app.leases.renew_description'),
            'backHref' => route('leases.show', $source),
            'backLabel' => trans('app.leases.lease_detail'),
            'submitLabel' => trans('app.leases.create_renewal'),
        ];
    }
}
