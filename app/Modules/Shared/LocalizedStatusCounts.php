<?php

namespace App\Modules\Shared;

final class LocalizedStatusCounts
{
    /**
     * @param  array<int, array<string, mixed>>  $counts
     * @return array<int, array<string, mixed>>
     */
    public function present(array $counts, string $allKey): array
    {
        return collect($counts)->map(function (array $count) use ($allKey): array {
            $status = (string) data_get($count, 'filter.status', 'all');
            $count['label'] = $status === 'all'
                ? trans($allKey)
                : trans("app.status.{$status}");

            return $count;
        })->all();
    }
}
