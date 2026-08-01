<?php

namespace App\Modules\PortfolioControl\Support;

final class PortfolioControlCurrencyPositions
{
    /**
     * @param  array<string, mixed>  $row
     * @return list<array{
     *     currency:string,
     *     scheduled_due:float,
     *     scheduled_paid:float,
     *     collection_rate:float,
     *     arrears:float,
     *     collected:float,
     *     expenses:float,
     *     net:float
     * }>
     */
    public function from(array $row): array
    {
        $source = $row['currency_totals'] ?? null;

        if (! is_array($source)) {
            return [];
        }

        $positions = [];

        foreach ($source as $item) {
            if (! is_array($item)) {
                continue;
            }

            $positions[] = [
                'currency' => (string) ($item['currency'] ?? 'SAR'),
                'scheduled_due' => (float) ($item['scheduled_due'] ?? 0),
                'scheduled_paid' => (float) ($item['scheduled_paid'] ?? 0),
                'collection_rate' => (float) ($item['collection_rate'] ?? 0),
                'arrears' => (float) ($item['arrears'] ?? 0),
                'collected' => (float) ($item['collected'] ?? 0),
                'expenses' => (float) ($item['expenses'] ?? 0),
                'net' => (float) ($item['net'] ?? 0),
            ];
        }

        return $positions;
    }

    /** @param array<string, mixed> $row */
    public function hasArrears(array $row): bool
    {
        return collect($this->from($row))
            ->contains(fn (array $position): bool => $position['arrears'] > 0);
    }

    /** @param array<string, mixed> $row */
    public function hasNegativeNet(array $row): bool
    {
        return collect($this->from($row))
            ->contains(fn (array $position): bool => $position['net'] < 0);
    }

    /** @param array<string, mixed> $row */
    public function minimumCollectionRate(array $row): float
    {
        return (float) (collect($this->from($row))
            ->min('collection_rate') ?? 0);
    }

    /**
     * @return array{
     *     currency:string,
     *     scheduled_due:float,
     *     scheduled_paid:float,
     *     collection_rate:float,
     *     arrears:float,
     *     collected:float,
     *     expenses:float,
     *     net:float
     * }
     */
    public function empty(string $currency): array
    {
        return [
            'currency' => $currency,
            'scheduled_due' => 0.0,
            'scheduled_paid' => 0.0,
            'collection_rate' => 0.0,
            'arrears' => 0.0,
            'collected' => 0.0,
            'expenses' => 0.0,
            'net' => 0.0,
        ];
    }
}
