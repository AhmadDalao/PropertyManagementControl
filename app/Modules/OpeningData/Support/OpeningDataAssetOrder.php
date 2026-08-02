<?php

namespace App\Modules\OpeningData\Support;

final class OpeningDataAssetOrder
{
    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, array<string, mixed>>
     */
    public function sort(array $rows): array
    {
        $byCode = [];

        foreach ($rows as $row) {
            $byCode[(string) $row['code']] = $row;
        }

        $sorted = [];
        $visited = [];

        $visit = function (string $code) use (&$visit, &$sorted, &$visited, $byCode): void {
            if (isset($visited[$code])) {
                return;
            }

            $visited[$code] = true;
            $parent = (string) ($byCode[$code]['parent_code'] ?? '');

            if ($parent !== '' && isset($byCode[$parent])) {
                $visit($parent);
            }

            $sorted[] = $byCode[$code];
        };

        foreach (array_keys($byCode) as $code) {
            $visit($code);
        }

        return $sorted;
    }
}
