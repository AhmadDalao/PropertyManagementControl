<?php

namespace App\Modules\SystemReadiness\Support;

use InvalidArgumentException;

final class ReadinessCheckCatalog
{
    public const SYSTEM_KEYS = [
        'smtp_delivery',
        'database_backup',
        'document_backup',
        'restore_drill',
    ];

    public const PORTFOLIO_KEYS = [
        'legal_terms_en',
        'legal_terms_ar',
        'opening_data',
        'billing_rules',
        'retention_policy',
        'pilot_completed',
    ];

    /** @return list<string> */
    public function allKeys(): array
    {
        return [...self::SYSTEM_KEYS, ...self::PORTFOLIO_KEYS];
    }

    public function scope(string $key): string
    {
        if (in_array($key, self::SYSTEM_KEYS, true)) {
            return 'system';
        }

        if (in_array($key, self::PORTFOLIO_KEYS, true)) {
            return 'portfolio';
        }

        throw new InvalidArgumentException("Unknown readiness check [{$key}].");
    }

    public function scopeKey(string $scope, ?int $portfolioId): string
    {
        return $scope === 'system' ? 'system' : "portfolio:{$portfolioId}";
    }
}
