<?php

namespace App\Modules\OpeningData\Support;

use App\Models\Asset;
use App\Models\Portfolio;

final class OpeningDataAssetReferenceValidator
{
    public function __construct(private readonly OpeningDataIssueFactory $issues) {}

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, array{sheet:string,row:int|null,field:string|null,message:string}>
     */
    public function validate(Portfolio $portfolio, array $rows): array
    {
        $issues = [];
        $assets = $this->index($rows);
        $parentCodes = collect($rows)->pluck('parent_code')->filter()->unique()->values()->all();
        $existingParents = Asset::query()
            ->where('portfolio_id', $portfolio->id)
            ->whereIn('code', $parentCodes)
            ->pluck('code')
            ->map(fn (string $code): string => mb_strtoupper($code))
            ->all();

        foreach ($rows as $row) {
            $parent = (string) ($row['parent_code'] ?? '');

            if ($parent !== ''
                && ! isset($assets[$parent])
                && ! in_array($parent, $existingParents, true)) {
                $issues[] = $this->issues->row(
                    'Assets',
                    $row,
                    'parent_code',
                    trans('app.opening_data.errors.parent_not_found', ['code' => $parent]),
                );
            }
        }

        return [...$issues, ...$this->cycles($assets)];
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<string, array<string, mixed>>
     */
    private function index(array $rows): array
    {
        $assets = [];

        foreach ($rows as $row) {
            $code = (string) ($row['code'] ?? '');

            if ($code !== '' && ! isset($assets[$code])) {
                $assets[$code] = $row;
            }
        }

        return $assets;
    }

    /**
     * @param  array<string, array<string, mixed>>  $assets
     * @return array<int, array{sheet:string,row:int|null,field:string|null,message:string}>
     */
    private function cycles(array $assets): array
    {
        $issues = [];
        $state = [];
        $visit = function (string $code) use (&$visit, &$issues, &$state, $assets): void {
            if (($state[$code] ?? null) === 'done') {
                return;
            }

            if (($state[$code] ?? null) === 'visiting') {
                $issues[] = $this->issues->row(
                    'Assets',
                    $assets[$code],
                    'parent_code',
                    trans('app.opening_data.errors.asset_cycle', ['code' => $code]),
                );

                return;
            }

            $state[$code] = 'visiting';
            $parent = (string) ($assets[$code]['parent_code'] ?? '');

            if ($parent !== '' && isset($assets[$parent])) {
                $visit($parent);
            }

            $state[$code] = 'done';
        };

        foreach (array_keys($assets) as $code) {
            $visit($code);
        }

        return $issues;
    }
}
