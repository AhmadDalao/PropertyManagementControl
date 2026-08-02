<?php

namespace App\Modules\OpeningData\Support;

final class OpeningDataNormalizer
{
    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, array<string, mixed>>
     */
    public function rows(string $sheet, array $rows): array
    {
        return array_map(
            fn (array $row): array => $this->row($sheet, $row),
            $rows,
        );
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function row(string $sheet, array $row): array
    {
        $row = array_map(
            fn (mixed $value): mixed => is_string($value) ? trim($value) : $value,
            $row,
        );

        return match ($sheet) {
            'Assets' => $this->asset($row),
            'Tenants' => $this->tenant($row),
            'Leases' => $this->lease($row),
            'Payments' => $this->payment($row),
            default => $row,
        };
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function asset(array $row): array
    {
        return [
            ...$row,
            'code' => $this->code($row['code'] ?? null),
            'parent_code' => $this->code($row['parent_code'] ?? null),
            'asset_type' => $this->lower($row['asset_type'] ?? null),
            'usage_type' => $this->lower($row['usage_type'] ?? null),
            'status' => $this->lower($row['status'] ?? null) ?: 'active',
            'occupancy_status' => $this->lower($row['occupancy_status'] ?? null) ?: 'vacant',
            'rentable' => $this->boolean($row['rentable'] ?? null),
            'valuation_amount' => $this->number($row['valuation_amount'] ?? null, 0),
            'currency' => $this->code($row['currency'] ?? null) ?: 'SAR',
            'area' => $this->number($row['area'] ?? null),
            'latitude' => $this->number($row['latitude'] ?? null),
            'longitude' => $this->number($row['longitude'] ?? null),
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function tenant(array $row): array
    {
        return [
            ...$row,
            'email' => mb_strtolower((string) ($row['email'] ?? '')),
            'preferred_locale' => $this->lower($row['preferred_locale'] ?? null) ?: 'en',
            'profile_type' => $this->lower($row['profile_type'] ?? null) ?: 'individual',
            'status' => $this->lower($row['status'] ?? null) ?: 'active',
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function lease(array $row): array
    {
        return [
            ...$row,
            'code' => $this->code($row['code'] ?? null),
            'asset_code' => $this->code($row['asset_code'] ?? null),
            'tenant_email' => mb_strtolower((string) ($row['tenant_email'] ?? '')),
            'status' => $this->lower($row['status'] ?? null) ?: 'active',
            'payment_frequency' => $this->lower($row['payment_frequency'] ?? null) ?: 'monthly',
            'rent_amount' => $this->number($row['rent_amount'] ?? null),
            'deposit_amount' => $this->number($row['deposit_amount'] ?? null, 0),
            'tax_amount' => $this->number($row['tax_amount'] ?? null, 0),
            'discount_amount' => $this->number($row['discount_amount'] ?? null, 0),
            'currency' => $this->code($row['currency'] ?? null) ?: 'SAR',
            'billing_day' => $this->integer($row['billing_day'] ?? null),
            'renewal_notice_days' => $this->integer($row['renewal_notice_days'] ?? null, 30),
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function payment(array $row): array
    {
        return [
            ...$row,
            'lease_code' => $this->code($row['lease_code'] ?? null),
            'reference' => $this->code($row['reference'] ?? null),
            'amount' => $this->number($row['amount'] ?? null),
            'method' => $this->lower($row['method'] ?? null) ?: 'bank_transfer',
            'type' => $this->lower($row['type'] ?? null) ?: 'rent',
        ];
    }

    private function code(mixed $value): string
    {
        return mb_strtoupper(trim((string) $value));
    }

    private function lower(mixed $value): string
    {
        return mb_strtolower(trim((string) $value));
    }

    private function boolean(mixed $value): mixed
    {
        if ($value === null || $value === '') {
            return false;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? $value;
    }

    private function number(mixed $value, int|float|null $default = null): mixed
    {
        if ($value === null || $value === '') {
            return $default;
        }

        return is_numeric($value) ? (float) $value : $value;
    }

    private function integer(mixed $value, ?int $default = null): mixed
    {
        if ($value === null || $value === '') {
            return $default;
        }

        return filter_var($value, FILTER_VALIDATE_INT) !== false
            ? (int) $value
            : $value;
    }
}
