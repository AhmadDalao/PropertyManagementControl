<?php

namespace App\Modules\ShowcaseData\Support;

use App\Models\ShowcaseDataset;

class ShowcaseTargets
{
    public const BUILDINGS = 40;

    public const BUILDINGS_PER_PORTFOLIO = 8;

    public const MANAGERS_PER_PORTFOLIO = 4;

    /** @return array<string, int> */
    public function all(): array
    {
        return [
            'portfolios' => 5,
            'buildings' => self::BUILDINGS,
            'floors' => 160,
            'units' => 640,
            'owners' => 5,
            'managers' => 20,
            'tenant_accounts' => 480,
            'tenants' => 480,
            'leases' => 480,
            'installments' => 5760,
            'payments' => 1600,
            'maintenance' => 320,
            'expenses' => 240,
            'documents' => 960,
        ];
    }

    public function validBuildingIndex(int $index): bool
    {
        return $index >= 0 && $index < self::BUILDINGS;
    }

    public function portfolioIndex(int $buildingIndex): int
    {
        return intdiv($buildingIndex, self::BUILDINGS_PER_PORTFOLIO);
    }

    public function portfolioCode(ShowcaseDataset $dataset, int $index): string
    {
        return sprintf('SD%d-P%02d', $dataset->id, $index + 1);
    }

    public function buildingCode(ShowcaseDataset $dataset, int $index): string
    {
        return sprintf('SD%d-B%03d', $dataset->id, $index + 1);
    }

    /** @return array<string, bool> */
    public function moduleSettings(): array
    {
        return [
            'assets' => true,
            'tenants' => true,
            'leases' => true,
            'payments' => true,
            'maintenance' => true,
            'expenses' => true,
            'documents' => true,
            'reports' => true,
            'media' => true,
            'users' => true,
        ];
    }
}
