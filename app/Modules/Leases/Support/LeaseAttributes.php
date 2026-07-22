<?php

namespace App\Modules\Leases\Support;

use App\Models\Asset;
use App\Models\Lease;
use App\Models\TenantProfile;
use App\Models\User;
use Illuminate\Support\Str;

final class LeaseAttributes
{
    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function forCreate(
        User $actor,
        int $portfolioId,
        TenantProfile $tenant,
        Asset $asset,
        array $data,
    ): array {
        return [
            'renewed_from_lease_id' => $data['renewed_from_lease_id'] ?? null,
            'portfolio_id' => $portfolioId,
            'tenant_profile_id' => $tenant->id,
            'managed_by_user_id' => $actor->id,
            'leaseable_type' => $asset->getMorphClass(),
            'leaseable_id' => $asset->id,
            'code' => $this->nextCode(),
            'status' => $data['status'],
            'payment_frequency' => $data['payment_frequency'],
            'started_at' => $data['started_at'],
            'ends_at' => $data['ends_at'],
            'signed_at' => $this->optional($data['signed_at'] ?? null),
            'rent_amount' => $data['rent_amount'],
            'deposit_amount' => $data['deposit_amount'] ?? 0,
            'tax_amount' => $data['tax_amount'] ?? 0,
            'discount_amount' => $data['discount_amount'] ?? 0,
            'currency' => Str::upper((string) ($data['currency'] ?? 'SAR')),
            'billing_day' => ($data['billing_day'] ?? null) ?: null,
            'terms_json' => $this->terms($data),
            'notes' => $this->optional($data['notes'] ?? null),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function forUpdate(array $data): array
    {
        return [
            'status' => $data['status'],
            'signed_at' => $this->optional($data['signed_at'] ?? null),
            'terms_json' => $this->terms($data),
            'notes' => $this->optional($data['notes'] ?? null),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{en:?string,ar:?string}
     */
    private function terms(array $data): array
    {
        return [
            'en' => $this->optional($data['terms_en'] ?? null),
            'ar' => $this->optional($data['terms_ar'] ?? null),
        ];
    }

    private function nextCode(): string
    {
        do {
            $code = 'LEASE-'.Str::upper(Str::random(8));
        } while (Lease::query()->where('code', $code)->exists());

        return $code;
    }

    private function optional(mixed $value): ?string
    {
        $normalized = is_string($value) ? trim($value) : '';

        return $normalized !== '' ? $normalized : null;
    }
}
