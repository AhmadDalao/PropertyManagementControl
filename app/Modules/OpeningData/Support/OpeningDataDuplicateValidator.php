<?php

namespace App\Modules\OpeningData\Support;

use App\Models\Asset;
use App\Models\Lease;
use App\Models\Payment;
use App\Models\User;

final class OpeningDataDuplicateValidator
{
    public function __construct(private readonly OpeningDataIssueFactory $issues) {}

    /**
     * @param  array<string, array<int, array<string, mixed>>>  $data
     * @return array<int, array{sheet:string,row:int|null,field:string|null,message:string}>
     */
    public function validate(array $data): array
    {
        $issues = [];
        $this->workbook($data['Assets'] ?? [], 'code', 'Assets', $issues);
        $this->workbook($data['Tenants'] ?? [], 'email', 'Tenants', $issues);
        $this->workbook($data['Leases'] ?? [], 'code', 'Leases', $issues);
        $this->workbook($data['Payments'] ?? [], 'reference', 'Payments', $issues, ignoreBlank: true);

        $this->database(
            $data['Assets'] ?? [],
            'code',
            'Assets',
            Asset::query()->whereIn('code', $this->values($data['Assets'] ?? [], 'code'))->pluck('code')->all(),
            'app.opening_data.errors.asset_code_exists',
            'code',
            $issues,
        );
        $this->database(
            $data['Tenants'] ?? [],
            'email',
            'Tenants',
            User::query()->whereIn('email', $this->values($data['Tenants'] ?? [], 'email'))->pluck('email')->all(),
            'app.opening_data.errors.tenant_email_exists',
            'email',
            $issues,
        );
        $this->database(
            $data['Leases'] ?? [],
            'code',
            'Leases',
            Lease::query()->whereIn('code', $this->values($data['Leases'] ?? [], 'code'))->pluck('code')->all(),
            'app.opening_data.errors.lease_code_exists',
            'code',
            $issues,
        );
        $this->database(
            $data['Payments'] ?? [],
            'reference',
            'Payments',
            Payment::query()->whereIn('reference', $this->values($data['Payments'] ?? [], 'reference'))->pluck('reference')->all(),
            'app.opening_data.errors.payment_reference_exists',
            'reference',
            $issues,
        );

        return $issues;
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @param  array<int, array{sheet:string,row:int|null,field:string|null,message:string}>  $issues
     */
    private function workbook(
        array $rows,
        string $field,
        string $sheet,
        array &$issues,
        bool $ignoreBlank = false,
    ): void {
        $groups = collect($rows)->groupBy(fn (array $row): string => (string) ($row[$field] ?? ''));

        foreach ($groups as $value => $duplicates) {
            if (($ignoreBlank && $value === '') || $duplicates->count() < 2) {
                continue;
            }

            foreach ($duplicates as $row) {
                $issues[] = $this->issues->row(
                    $sheet,
                    $row,
                    $field,
                    trans('app.opening_data.errors.duplicate_value', ['value' => $value]),
                );
            }
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @param  array<int, mixed>  $existing
     * @param  array<int, array{sheet:string,row:int|null,field:string|null,message:string}>  $issues
     */
    private function database(
        array $rows,
        string $field,
        string $sheet,
        array $existing,
        string $messageKey,
        string $replacement,
        array &$issues,
    ): void {
        $existing = array_map(
            fn (mixed $value): string => mb_strtoupper((string) $value),
            $existing,
        );

        foreach ($rows as $row) {
            $value = mb_strtoupper((string) ($row[$field] ?? ''));

            if ($value !== '' && in_array($value, $existing, true)) {
                $issues[] = $this->issues->row(
                    $sheet,
                    $row,
                    $field,
                    trans($messageKey, [$replacement => $value]),
                );
            }
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, string>
     */
    private function values(array $rows, string $field): array
    {
        return collect($rows)
            ->pluck($field)
            ->filter()
            ->map(fn (mixed $value): string => (string) $value)
            ->unique()
            ->values()
            ->all();
    }
}
