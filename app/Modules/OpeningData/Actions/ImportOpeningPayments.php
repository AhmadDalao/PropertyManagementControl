<?php

namespace App\Modules\OpeningData\Actions;

use App\Models\Lease;
use App\Models\User;
use App\Modules\Payments\Actions\CreatePayment;

final class ImportOpeningPayments
{
    public function __construct(private readonly CreatePayment $create) {}

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @param  array<string, Lease>  $leases
     */
    public function handle(
        User $actor,
        int $portfolioId,
        array $rows,
        array $leases,
    ): void {
        $leaseCodes = collect($rows)->pluck('lease_code')->filter()->unique()->all();

        foreach (Lease::query()
            ->where('portfolio_id', $portfolioId)
            ->whereIn('code', $leaseCodes)
            ->get() as $lease) {
            $leases[mb_strtoupper($lease->code)] = $lease;
        }

        foreach ($rows as $row) {
            $this->create->handle($actor, [
                'lease_id' => $leases[(string) $row['lease_code']]->id,
                'reference' => $row['reference'] ?: null,
                'type' => $row['type'],
                'method' => $row['method'],
                'status' => 'posted',
                'received_on' => $row['received_on'],
                'amount' => $row['amount'],
                'notes' => $row['notes'] ?? null,
            ], notify: false);
        }
    }
}
