<?php

namespace App\Modules\OpeningData\Support;

final class OpeningDataWorkbookSchema
{
    /** @var array<string, array<int, string>> */
    public const SHEETS = [
        'Assets' => [
            'code',
            'parent_code',
            'asset_type',
            'usage_type',
            'title_en',
            'title_ar',
            'status',
            'occupancy_status',
            'rentable',
            'valuation_amount',
            'currency',
            'area',
            'level_label',
            'unit_label',
            'address_en',
            'address_ar',
            'description_en',
            'description_ar',
            'latitude',
            'longitude',
            'zone_en',
            'zone_ar',
        ],
        'Tenants' => [
            'email',
            'name',
            'phone',
            'preferred_locale',
            'profile_type',
            'national_id',
            'company_name',
            'address',
            'status',
            'notes',
        ],
        'Leases' => [
            'code',
            'asset_code',
            'tenant_email',
            'status',
            'payment_frequency',
            'started_at',
            'ends_at',
            'signed_at',
            'rent_amount',
            'deposit_amount',
            'tax_amount',
            'discount_amount',
            'currency',
            'billing_day',
            'renewal_notice_days',
            'terms_en',
            'terms_ar',
            'notes',
        ],
        'Payments' => [
            'lease_code',
            'reference',
            'received_on',
            'amount',
            'method',
            'type',
            'notes',
        ],
    ];

    /** @var array<string, int> */
    public const ROW_LIMITS = [
        'Assets' => 2000,
        'Tenants' => 1000,
        'Leases' => 2000,
        'Payments' => 5000,
    ];

    /** @return array<int, string> */
    public function sheetNames(): array
    {
        return array_keys(self::SHEETS);
    }

    /** @return array<int, string> */
    public function headers(string $sheet): array
    {
        return self::SHEETS[$sheet] ?? [];
    }
}
