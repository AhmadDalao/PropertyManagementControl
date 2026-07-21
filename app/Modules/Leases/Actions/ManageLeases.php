<?php

namespace App\Modules\Leases\Actions;

use App\Models\Asset;
use App\Models\Lease;
use App\Models\TenantProfile;
use App\Models\User;
use App\Modules\Leases\LeaseLifecycle;
use App\Modules\Leases\Support\LeaseAccess;
use App\Modules\Shared\PortfolioScope;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ManageLeases
{
    public function __construct(
        private readonly LeaseAccess $access,
        private readonly PortfolioScope $portfolios,
        private readonly LeaseLifecycle $lifecycle,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(User $actor, array $data): Lease
    {
        $this->access->ensureManager($actor);
        $portfolioId = $data['portfolio_id'] ?? $actor->portfolio_id;

        if (! $portfolioId) {
            throw ValidationException::withMessages([
                'portfolio_id' => trans('validation.required', ['attribute' => trans('app.fields.portfolio')]),
            ]);
        }

        $portfolioId = (int) $portfolioId;
        $this->portfolios->ensureAccess($actor, $portfolioId);
        $asset = Asset::query()->whereKey((int) $data['asset_id'])->firstOrFail();

        abort_if(
            $asset->portfolio_id !== $portfolioId,
            422,
            trans('app.errors.lease_asset_portfolio_mismatch')
        );

        if (! $asset->rentable || $asset->status !== 'active') {
            throw ValidationException::withMessages([
                'asset_id' => trans('app.errors.asset_not_rentable'),
            ]);
        }

        abort_unless(
            TenantProfile::query()
                ->whereKey($data['tenant_profile_id'])
                ->where('portfolio_id', $portfolioId)
                ->exists(),
            422,
            trans('app.errors.tenant_portfolio_mismatch')
        );

        return $this->lifecycle->create($asset, [
            'portfolio_id' => $portfolioId,
            'tenant_profile_id' => $data['tenant_profile_id'],
            'managed_by_user_id' => $actor->id,
            'leaseable_type' => $asset->getMorphClass(),
            'leaseable_id' => $asset->id,
            'code' => $this->nextCode(),
            'status' => $data['status'],
            'payment_frequency' => $data['payment_frequency'],
            'started_at' => $data['started_at'],
            'ends_at' => $data['ends_at'],
            'signed_at' => $data['signed_at'] ?? null,
            'rent_amount' => $data['rent_amount'],
            'deposit_amount' => $data['deposit_amount'] ?? 0,
            'tax_amount' => $data['tax_amount'] ?? 0,
            'discount_amount' => $data['discount_amount'] ?? 0,
            'currency' => Str::upper((string) ($data['currency'] ?? 'SAR')),
            'billing_day' => $data['billing_day'] ?? null,
            'terms_json' => [
                'en' => $data['terms_en'] ?? null,
                'ar' => $data['terms_ar'] ?? null,
            ],
            'notes' => $data['notes'] ?? null,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(User $actor, Lease $lease, array $data): Lease
    {
        $this->access->ensureCanManage($actor, $lease);

        return $this->lifecycle->update($lease, [
            'status' => $data['status'],
            'signed_at' => $data['signed_at'] ?? null,
            'terms_json' => [
                'en' => $data['terms_en'] ?? null,
                'ar' => $data['terms_ar'] ?? null,
            ],
            'notes' => $data['notes'] ?? null,
        ], (bool) ($data['resync_installments'] ?? false));
    }

    public function terminate(User $actor, Lease $lease): Lease
    {
        $this->access->ensureCanManage($actor, $lease);

        return $this->lifecycle->update($lease, ['status' => 'terminated']);
    }

    private function nextCode(): string
    {
        do {
            $code = 'LEASE-'.Str::upper(Str::random(8));
        } while (Lease::query()->where('code', $code)->exists());

        return $code;
    }
}
