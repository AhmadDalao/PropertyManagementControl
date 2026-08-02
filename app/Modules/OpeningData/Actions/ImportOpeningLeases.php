<?php

namespace App\Modules\OpeningData\Actions;

use App\Models\Asset;
use App\Models\Lease;
use App\Models\TenantProfile;
use App\Models\User;
use App\Modules\Leases\Actions\CreateLease;
use Illuminate\Database\Eloquent\Builder;

final class ImportOpeningLeases
{
    public function __construct(private readonly CreateLease $create) {}

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @param  array<string, Asset>  $assets
     * @param  array<string, TenantProfile>  $tenants
     * @return array<string, Lease>
     */
    public function handle(
        User $actor,
        int $portfolioId,
        array $rows,
        array $assets,
        array $tenants,
    ): array {
        $assetCodes = collect($rows)->pluck('asset_code')->filter()->unique()->all();
        $tenantEmails = collect($rows)->pluck('tenant_email')->filter()->unique()->all();

        foreach (Asset::query()
            ->where('portfolio_id', $portfolioId)
            ->whereIn('code', $assetCodes)
            ->get() as $asset) {
            $assets[mb_strtoupper($asset->code)] = $asset;
        }

        foreach (TenantProfile::query()
            ->where('portfolio_id', $portfolioId)
            ->whereHas('user', fn (Builder $query) => $query->whereIn('email', $tenantEmails))
            ->with('user:id,email')
            ->get() as $tenant) {
            $tenants[mb_strtolower((string) $tenant->user?->email)] = $tenant;
        }

        $leases = [];

        foreach ($rows as $row) {
            $lease = $this->create->handle($actor, [
                'portfolio_id' => $portfolioId,
                'tenant_profile_id' => $tenants[(string) $row['tenant_email']]->id,
                'asset_id' => $assets[(string) $row['asset_code']]->id,
                'code' => $row['code'],
                'status' => $row['status'],
                'payment_frequency' => $row['payment_frequency'],
                'started_at' => $row['started_at'],
                'ends_at' => $row['ends_at'],
                'signed_at' => $row['signed_at'] ?? null,
                'rent_amount' => $row['rent_amount'],
                'deposit_amount' => $row['deposit_amount'],
                'tax_amount' => $row['tax_amount'],
                'discount_amount' => $row['discount_amount'],
                'currency' => $row['currency'],
                'billing_day' => $row['billing_day'],
                'renewal_notice_days' => $row['renewal_notice_days'],
                'terms_en' => $row['terms_en'],
                'terms_ar' => $row['terms_ar'],
                'notes' => $row['notes'] ?? null,
            ], notify: false);
            $leases[(string) $row['code']] = $lease;
        }

        return $leases;
    }
}
