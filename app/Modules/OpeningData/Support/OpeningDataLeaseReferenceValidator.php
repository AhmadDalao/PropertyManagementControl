<?php

namespace App\Modules\OpeningData\Support;

use App\Models\Asset;
use App\Models\Lease;
use App\Models\Portfolio;
use App\Models\TenantProfile;
use App\Modules\Shared\MorphTypes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

final class OpeningDataLeaseReferenceValidator
{
    public function __construct(
        private readonly OpeningDataIssueFactory $issues,
        private readonly MorphTypes $morphTypes,
    ) {}

    /**
     * @param  array<int, array<string, mixed>>  $assetRows
     * @param  array<int, array<string, mixed>>  $tenantRows
     * @param  array<int, array<string, mixed>>  $leaseRows
     * @return array<int, array{sheet:string,row:int|null,field:string|null,message:string}>
     */
    public function validate(
        Portfolio $portfolio,
        array $assetRows,
        array $tenantRows,
        array $leaseRows,
    ): array {
        $assets = $this->index($assetRows, 'code');
        $tenants = $this->index($tenantRows, 'email');
        $existingAssets = $this->existingAssets($portfolio, $leaseRows);
        $existingTenants = $this->existingTenants($portfolio, $leaseRows);
        $issues = [];

        foreach ($leaseRows as $row) {
            $assetCode = (string) ($row['asset_code'] ?? '');
            $assetRow = $assets[$assetCode] ?? null;
            $asset = $existingAssets->get($assetCode);

            if (! is_array($assetRow) && ! $asset instanceof Asset) {
                $issues[] = $this->issue(
                    $row,
                    'asset_code',
                    'lease_asset_not_found',
                    ['code' => $assetCode],
                );
            } elseif (! $this->assetRentable($assetRow, $asset)) {
                $issues[] = $this->issue(
                    $row,
                    'asset_code',
                    'lease_asset_not_rentable',
                    ['code' => $assetCode],
                );
            }

            $tenantEmail = (string) ($row['tenant_email'] ?? '');
            $tenantRow = $tenants[$tenantEmail] ?? null;
            $tenant = $existingTenants->get($tenantEmail);

            if (! is_array($tenantRow) && ! $tenant instanceof TenantProfile) {
                $issues[] = $this->issue(
                    $row,
                    'tenant_email',
                    'lease_tenant_not_found',
                    ['email' => $tenantEmail],
                );
            } elseif (! $this->tenantActive($tenantRow, $tenant)) {
                $issues[] = $this->issue(
                    $row,
                    'tenant_email',
                    'lease_tenant_inactive',
                    ['email' => $tenantEmail],
                );
            }

            if ((float) ($row['discount_amount'] ?? 0)
                > (float) ($row['rent_amount'] ?? 0) + (float) ($row['tax_amount'] ?? 0)) {
                $issues[] = $this->issues->row(
                    'Leases',
                    $row,
                    'discount_amount',
                    trans('app.errors.lease_discount_exceeds_charges'),
                );
            }
        }

        return [
            ...$issues,
            ...$this->activeConflicts($leaseRows, $existingAssets),
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<string, array<string, mixed>>
     */
    private function index(array $rows, string $field): array
    {
        $index = [];

        foreach ($rows as $row) {
            $value = (string) ($row[$field] ?? '');

            if ($value !== '' && ! isset($index[$value])) {
                $index[$value] = $row;
            }
        }

        return $index;
    }

    /**
     * @param  array<int, array<string, mixed>>  $leaseRows
     * @return Collection<string, Asset>
     */
    private function existingAssets(Portfolio $portfolio, array $leaseRows): Collection
    {
        return Asset::query()
            ->where('portfolio_id', $portfolio->id)
            ->whereIn('code', collect($leaseRows)->pluck('asset_code')->filter()->unique())
            ->get(['id', 'code', 'status', 'rentable'])
            ->keyBy(fn (Asset $asset): string => mb_strtoupper($asset->code));
    }

    /**
     * @param  array<int, array<string, mixed>>  $leaseRows
     * @return Collection<string, TenantProfile>
     */
    private function existingTenants(Portfolio $portfolio, array $leaseRows): Collection
    {
        $emails = collect($leaseRows)->pluck('tenant_email')->filter()->unique();

        return TenantProfile::query()
            ->where('portfolio_id', $portfolio->id)
            ->whereHas('user', fn (Builder $query) => $query->whereIn('email', $emails))
            ->with('user:id,email,status')
            ->get()
            ->keyBy(fn (TenantProfile $tenant): string => mb_strtolower((string) $tenant->user?->email));
    }

    /** @param array<string, mixed>|null $row */
    private function assetRentable(?array $row, ?Asset $asset): bool
    {
        return is_array($row)
            ? ($row['rentable'] ?? false) === true && ($row['status'] ?? null) === 'active'
            : $asset instanceof Asset && $asset->rentable && $asset->status === 'active';
    }

    /** @param array<string, mixed>|null $row */
    private function tenantActive(?array $row, ?TenantProfile $tenant): bool
    {
        return is_array($row)
            ? ($row['status'] ?? null) === 'active'
            : $tenant instanceof TenantProfile
                && $tenant->status === 'active'
                && $tenant->user?->status === 'active';
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @param  Collection<string, Asset>  $existingAssets
     * @return array<int, array{sheet:string,row:int|null,field:string|null,message:string}>
     */
    private function activeConflicts(array $rows, Collection $existingAssets): array
    {
        $issues = [];
        $active = collect($rows)
            ->filter(fn (array $row): bool => ($row['status'] ?? null) === 'active')
            ->groupBy('asset_code');

        foreach ($active as $code => $leases) {
            if ($leases->count() > 1) {
                foreach ($leases as $row) {
                    $issues[] = $this->issue(
                        $row,
                        'asset_code',
                        'multiple_active_leases',
                        ['code' => $code],
                    );
                }
            }
        }

        $leasedIds = Lease::query()
            ->whereIn('leaseable_type', $this->morphTypes->for(new Asset))
            ->whereIn('leaseable_id', $existingAssets->pluck('id'))
            ->where('status', 'active')
            ->pluck('leaseable_id')
            ->all();
        $leasedCodes = $existingAssets
            ->filter(fn (Asset $asset): bool => in_array($asset->id, $leasedIds, true))
            ->keys()
            ->all();

        foreach ($active as $code => $leases) {
            if (in_array($code, $leasedCodes, true)) {
                foreach ($leases as $row) {
                    $issues[] = $this->issue(
                        $row,
                        'asset_code',
                        'existing_active_lease',
                        ['code' => $code],
                    );
                }
            }
        }

        return $issues;
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  array<string, scalar>  $replace
     * @return array{sheet:string,row:int|null,field:string|null,message:string}
     */
    private function issue(
        array $row,
        string $field,
        string $key,
        array $replace,
    ): array {
        return $this->issues->row(
            'Leases',
            $row,
            $field,
            trans("app.opening_data.errors.{$key}", $replace),
        );
    }
}
