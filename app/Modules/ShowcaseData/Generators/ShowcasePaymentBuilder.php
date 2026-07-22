<?php

namespace App\Modules\ShowcaseData\Generators;

use App\Models\Asset;
use App\Models\Lease;
use App\Models\Payment;
use App\Models\Portfolio;
use App\Models\ShowcaseDataset;
use App\Models\TenantProfile;
use App\Models\User;
use App\Modules\ShowcaseData\Support\ShowcaseTargets;
use Illuminate\Support\Carbon;

class ShowcasePaymentBuilder
{
    public function __construct(
        private readonly ShowcaseTargets $targets,
    ) {}

    /** @param list<array{lease:Lease, tenant:TenantProfile, unit:Asset, user:User}> $records */
    public function build(
        ShowcaseDataset $dataset,
        Portfolio $portfolio,
        User $manager,
        array $records,
        int $buildingIndex,
    ): void {
        foreach (array_slice($records, 0, 10) as $leaseIndex => $record) {
            $installments = $record['lease']->installments()
                ->orderBy('sequence')
                ->limit(4)
                ->get();

            foreach ($installments as $paymentIndex => $installment) {
                $amount = $leaseIndex >= 8 && $paymentIndex >= 2
                    ? round((float) $record['lease']->rent_amount * 0.5, 2)
                    : (float) $record['lease']->rent_amount;
                $payment = Payment::query()->updateOrCreate(
                    ['reference' => sprintf(
                        '%s-P%02d-%02d',
                        $this->targets->buildingCode($dataset, $buildingIndex),
                        $leaseIndex + 1,
                        $paymentIndex + 1,
                    )],
                    [
                        'portfolio_id' => $portfolio->id,
                        'lease_id' => $record['lease']->id,
                        'tenant_profile_id' => $record['tenant']->id,
                        'recorded_by_user_id' => $manager->id,
                        'type' => 'rent',
                        'method' => $paymentIndex % 3 === 0 ? 'cash' : 'bank_transfer',
                        'status' => 'posted',
                        'received_on' => Carbon::parse((string) $installment->due_date)->addDays(2)->toDateString(),
                        'amount' => $amount,
                        'currency' => 'SAR',
                        'notes' => 'Tagged showcase rent payment.',
                        'meta_json' => ['showcase' => true],
                    ],
                );
                $payment->allocations()->updateOrCreate(
                    ['lease_installment_id' => $installment->id],
                    ['amount' => $amount, 'notes' => 'Showcase allocation.'],
                );
                $installment->update([
                    'amount_paid' => $amount,
                    'status' => $amount >= (float) $installment->amount_due ? 'paid' : 'partial',
                    'paid_at' => $payment->received_on,
                ]);
            }
        }
    }
}
