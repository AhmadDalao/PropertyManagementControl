<?php

namespace App\Modules\ShowcaseData\Generators;

use App\Models\Asset;
use App\Models\Lease;
use App\Models\Portfolio;
use App\Models\ShowcaseDataset;
use App\Models\TenantProfile;
use App\Models\User;
use App\Modules\ShowcaseData\Support\ShowcaseLeasePeriod;
use App\Modules\ShowcaseData\Support\ShowcaseTargets;

class ShowcaseLeaseBuilder
{
    public function __construct(
        private readonly ShowcaseTargets $targets,
        private readonly ShowcaseUserFactory $users,
        private readonly ShowcaseInstallmentBuilder $installments,
        private readonly ShowcaseLeasePeriod $periods,
    ) {}

    /**
     * @param  list<Asset>  $units
     * @return list<array{lease:Lease, tenant:TenantProfile, unit:Asset, user:User}>
     */
    public function build(
        ShowcaseDataset $dataset,
        Portfolio $portfolio,
        User $manager,
        array $units,
        int $buildingIndex,
    ): array {
        $records = [];

        for ($tenantIndex = 0; $tenantIndex < 12; $tenantIndex++) {
            $sequence = ($buildingIndex * 12) + $tenantIndex + 1;
            $user = $this->users->make(
                $dataset,
                $portfolio,
                sprintf('tenant.b%03d.t%02d.d%d@showcase.invalid', $buildingIndex + 1, $tenantIndex + 1, $dataset->id),
                "Showcase Tenant {$sequence}",
                'tenant',
                1000 + $sequence,
            );
            $tenant = TenantProfile::query()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'portfolio_id' => $portfolio->id,
                    'profile_type' => $tenantIndex % 7 === 0 ? 'company' : 'individual',
                    'national_id' => '9'.str_pad((string) $sequence, 9, '0', STR_PAD_LEFT),
                    'company_name' => $tenantIndex % 7 === 0 ? "Showcase Company {$sequence}" : null,
                    'emergency_contact_name' => "Emergency Contact {$sequence}",
                    'emergency_contact_phone' => '+96656'.str_pad((string) $sequence, 7, '0', STR_PAD_LEFT),
                    'address' => $portfolio->address,
                    'status' => 'inactive',
                    'meta_json' => ['showcase' => true, 'dataset_key' => $dataset->key],
                    'notes' => 'Tagged showcase tenant profile.',
                ],
            );
            $period = $this->periods->forTenant($buildingIndex, $tenantIndex);
            $rent = 3_600 + (($buildingIndex % 5) * 450) + (($tenantIndex % 4) * 250);
            $lease = Lease::query()->updateOrCreate(
                ['code' => sprintf('%s-L%02d', $this->targets->buildingCode($dataset, $buildingIndex), $tenantIndex + 1)],
                [
                    'portfolio_id' => $portfolio->id,
                    'tenant_profile_id' => $tenant->id,
                    'managed_by_user_id' => $manager->id,
                    'leaseable_type' => (new Asset)->getMorphClass(),
                    'leaseable_id' => $units[$tenantIndex]->id,
                    'status' => $period['status'],
                    'payment_frequency' => 'monthly',
                    'started_at' => $period['starts_at'],
                    'ends_at' => $period['ends_at'],
                    'signed_at' => $period['status'] === 'draft' ? null : $period['starts_at'],
                    'renewal_notice_days' => 45,
                    'rent_amount' => $rent,
                    'deposit_amount' => $rent,
                    'tax_amount' => 0,
                    'discount_amount' => 0,
                    'currency' => 'SAR',
                    'billing_day' => 1,
                    'notes' => 'Tagged showcase lease for payment and arrears testing.',
                    'terms_json' => ['showcase' => true],
                ],
            );
            $this->installments->build($lease);
            $records[] = compact('lease', 'tenant', 'user') + ['unit' => $units[$tenantIndex]];
        }

        return $records;
    }
}
