<?php

namespace App\Modules\ShowcaseData\Support;

class ShowcaseLeasePeriod
{
    /** @return array{status:string, starts_at:string, ends_at:string} */
    public function forTenant(int $buildingIndex, int $tenantIndex): array
    {
        if ($tenantIndex < 10) {
            $start = now()->startOfMonth()->subMonths(7 + (($buildingIndex + $tenantIndex) % 4));
            $end = $tenantIndex === 0
                ? now()->addDays(20 + ($buildingIndex % 30))
                : $start->copy()->addYear()->subDay();

            return [
                'status' => 'active',
                'starts_at' => $start->toDateString(),
                'ends_at' => $end->toDateString(),
            ];
        }

        if ($tenantIndex === 10) {
            return [
                'status' => 'expired',
                'starts_at' => now()->subMonths(18)->startOfMonth()->toDateString(),
                'ends_at' => now()->subMonths(6)->endOfMonth()->toDateString(),
            ];
        }

        if ($buildingIndex % 2 === 0) {
            return [
                'status' => 'terminated',
                'starts_at' => now()->subMonths(15)->startOfMonth()->toDateString(),
                'ends_at' => now()->subMonths(3)->endOfMonth()->toDateString(),
            ];
        }

        return [
            'status' => 'draft',
            'starts_at' => now()->addMonth()->startOfMonth()->toDateString(),
            'ends_at' => now()->addMonths(13)->endOfMonth()->toDateString(),
        ];
    }
}
