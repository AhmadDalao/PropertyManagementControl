<?php

namespace App\Modules\Dashboard\Support;

final class PropertyPerformanceCurrencySummary
{
    /**
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
    public function score(mixed $source): array
    {
        if (! is_array($source)) {
            return [];
        }

        $totals = [];

        foreach ($source as $item) {
            if (! is_array($item)) {
                continue;
            }

            $scheduledDue = (float) ($item['scheduled_due'] ?? 0);
            $scheduledPaid = (float) ($item['scheduled_paid'] ?? 0);
            $collected = (float) ($item['collected'] ?? 0);
            $expenses = (float) ($item['expenses'] ?? 0);
            $totals[] = [
                'currency' => (string) ($item['currency'] ?? 'SAR'),
                'scheduled_due' => $scheduledDue,
                'scheduled_paid' => $scheduledPaid,
                'collection_rate' => $scheduledDue > 0
                    ? round(min(100, ($scheduledPaid / $scheduledDue) * 100), 1)
                    : 0.0,
                'arrears' => (float) ($item['arrears'] ?? 0),
                'collected' => $collected,
                'expenses' => $expenses,
                'net' => $collected - $expenses,
            ];
        }

        usort(
            $totals,
            fn (array $left, array $right): int => $left['currency'] <=> $right['currency'],
        );

        return $totals;
    }

    /** @param list<array<string, float|string>> $totals */
    public function hasArrears(array $totals): bool
    {
        foreach ($totals as $total) {
            if ($total['arrears'] > 0) {
                return true;
            }
        }

        return false;
    }

    /** @param list<array<string, float|string>> $totals */
    public function hasWeakCollection(array $totals): bool
    {
        foreach ($totals as $total) {
            if (
                $total['scheduled_due'] > 0
                && $total['collection_rate'] < 80
            ) {
                return true;
            }
        }

        return false;
    }

    /** @return array<string, float|string> */
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

    public function currency(?string $currency): string
    {
        $normalized = strtoupper(trim((string) $currency));

        return $normalized !== '' ? $normalized : 'SAR';
    }
}
