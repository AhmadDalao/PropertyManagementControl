<?php

namespace App\Modules\Dashboard\Support;

final class PropertyPerformanceScorer
{
    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    public function score(array $row): array
    {
        $row['occupancy_rate'] = $row['rentable_units'] > 0
            ? round(($row['occupied_units'] / $row['rentable_units']) * 100, 1)
            : 0;
        $row['collection_rate'] = $row['scheduled_due'] > 0
            ? round(min(100, ($row['scheduled_paid'] / $row['scheduled_due']) * 100), 1)
            : 0;
        $row['net'] = $row['collected'] - $row['expenses'];
        $row['attention_score'] = ($row['arrears'] > 0 ? 4 : 0)
            + ($row['open_requests'] > 0 ? 2 : 0)
            + ($row['scheduled_due'] > 0 && $row['collection_rate'] < 80 ? 2 : 0)
            + ($row['rentable_units'] > 0 && $row['occupancy_rate'] < 70 ? 2 : 0)
            + ($row['expiring_leases'] > 0 ? 1 : 0);
        $row['attention'] = $row['attention_score'] >= 4
            ? 'risk'
            : ($row['attention_score'] > 0 ? 'watch' : 'on_track');

        return $row;
    }
}
