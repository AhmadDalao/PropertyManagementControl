<?php

namespace App\Modules\Reports\Support;

use Carbon\CarbonImmutable;

class ReportFilterSet
{
    /**
     * @param  array<string, mixed>  $validated
     * @return array{date_from:string,date_to:string,portfolio_id:int|null}
     */
    public function current(array $validated): array
    {
        return [
            'date_from' => (string) $validated['date_from'],
            'date_to' => (string) $validated['date_to'],
            'portfolio_id' => isset($validated['portfolio_id']) ? (int) $validated['portfolio_id'] : null,
        ];
    }

    /**
     * Keep saved links portable and discard legacy or injected query keys.
     *
     * @return array{date_from?:string,date_to?:string,portfolio_id?:int}
     */
    public function stored(mixed $filters): array
    {
        if (! is_array($filters)) {
            return [];
        }

        $normalized = [];

        foreach (['date_from', 'date_to'] as $key) {
            $value = trim((string) ($filters[$key] ?? ''));

            if ($this->isDate($value)) {
                $normalized[$key] = $value;
            }
        }

        if (isset($normalized['date_from'], $normalized['date_to'])
            && $normalized['date_to'] < $normalized['date_from']) {
            unset($normalized['date_to']);
        }

        $portfolioId = filter_var($filters['portfolio_id'] ?? null, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1],
        ]);

        if ($portfolioId !== false) {
            $normalized['portfolio_id'] = $portfolioId;
        }

        return $normalized;
    }

    private function isDate(string $value): bool
    {
        if ($value === '') {
            return false;
        }

        try {
            return CarbonImmutable::createFromFormat('!Y-m-d', $value)->format('Y-m-d') === $value;
        } catch (\Throwable) {
            return false;
        }
    }
}
