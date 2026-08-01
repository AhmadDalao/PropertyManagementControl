<?php

namespace App\Modules\Reports\Support;

use Carbon\CarbonImmutable;

class ReportFilterSet
{
    public function __construct(private readonly ReportPeriod $periods) {}

    /**
     * @param  array<string, mixed>  $validated
     * @return array{period:string,date_from:string,date_to:string,portfolio_id:int|null,property_id:int|null}
     */
    public function current(array $validated): array
    {
        return [
            'period' => $this->periods->normalize($validated['period'] ?? null),
            'date_from' => (string) $validated['date_from'],
            'date_to' => (string) $validated['date_to'],
            'portfolio_id' => isset($validated['portfolio_id']) ? (int) $validated['portfolio_id'] : null,
            'property_id' => isset($validated['property_id']) ? (int) $validated['property_id'] : null,
        ];
    }

    /**
     * Keep saved links portable and discard legacy or injected query keys.
     *
     * @return array{period?:string,date_from?:string,date_to?:string,portfolio_id?:int,property_id?:int}
     */
    public function stored(mixed $filters): array
    {
        if (! is_array($filters)) {
            return [];
        }

        $normalized = [];
        $period = $this->periods->normalize($filters['period'] ?? null);

        if ($this->periods->rolling($period)) {
            $normalized['period'] = $period;
        } else {
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
        }

        $portfolioId = filter_var($filters['portfolio_id'] ?? null, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1],
        ]);

        if ($portfolioId !== false) {
            $normalized['portfolio_id'] = $portfolioId;
        }

        $propertyId = filter_var($filters['property_id'] ?? null, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1],
        ]);

        if ($propertyId !== false) {
            $normalized['property_id'] = $propertyId;
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
