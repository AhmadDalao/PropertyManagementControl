<?php

namespace App\Modules\OpeningData\Support;

use App\Models\Lease;
use App\Models\Portfolio;
use App\Modules\Payments\Support\PaymentOptions;

final class OpeningDataPaymentReferenceValidator
{
    public function __construct(private readonly OpeningDataIssueFactory $issues) {}

    /**
     * @param  array<int, array<string, mixed>>  $leaseRows
     * @param  array<int, array<string, mixed>>  $paymentRows
     * @return array<int, array{sheet:string,row:int|null,field:string|null,message:string}>
     */
    public function validate(
        Portfolio $portfolio,
        array $leaseRows,
        array $paymentRows,
    ): array {
        $newLeases = [];

        foreach ($leaseRows as $row) {
            $code = (string) ($row['code'] ?? '');

            if ($code !== '' && ! isset($newLeases[$code])) {
                $newLeases[$code] = $row;
            }
        }

        $existingLeases = Lease::query()
            ->where('portfolio_id', $portfolio->id)
            ->whereIn('code', collect($paymentRows)->pluck('lease_code')->filter()->unique())
            ->get(['id', 'code', 'status'])
            ->keyBy(fn (Lease $lease): string => mb_strtoupper($lease->code));
        $issues = [];

        foreach ($paymentRows as $row) {
            $code = (string) ($row['lease_code'] ?? '');
            $newLease = $newLeases[$code] ?? null;
            $existingLease = $existingLeases->get($code);

            if (! is_array($newLease) && ! $existingLease instanceof Lease) {
                $issues[] = $this->issue(
                    $row,
                    'payment_lease_not_found',
                    $code,
                );

                continue;
            }

            $status = is_array($newLease)
                ? ($newLease['status'] ?? null)
                : $existingLease->status;

            if (! in_array($status, PaymentOptions::PAYABLE_LEASE_STATUSES, true)) {
                $issues[] = $this->issue(
                    $row,
                    'payment_lease_not_active',
                    $code,
                );
            }
        }

        return $issues;
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array{sheet:string,row:int|null,field:string|null,message:string}
     */
    private function issue(array $row, string $key, string $code): array
    {
        return $this->issues->row(
            'Payments',
            $row,
            'lease_code',
            trans("app.opening_data.errors.{$key}", ['code' => $code]),
        );
    }
}
