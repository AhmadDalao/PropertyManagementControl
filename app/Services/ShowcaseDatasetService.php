<?php

namespace App\Services;

use App\Jobs\GenerateShowcaseBuilding;
use App\Models\Asset;
use App\Models\Document;
use App\Models\ExpenseEntry;
use App\Models\Lease;
use App\Models\LeaseInstallment;
use App\Models\MaintenanceRequest;
use App\Models\Payment;
use App\Models\Portfolio;
use App\Models\ShowcaseDataset;
use App\Models\TenantProfile;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ShowcaseDatasetService
{
    public const TARGET_BUILDINGS = 40;

    private const BUILDINGS_PER_PORTFOLIO = 8;

    public function start(User $initiator): ShowcaseDataset
    {
        if (ShowcaseDataset::query()->whereIn('status', ['queued', 'generating'])->exists()) {
            throw ValidationException::withMessages([
                'showcase' => trans('app.showcase.already_running'),
            ]);
        }

        $this->tagLegacyData();

        $dataset = DB::transaction(function () use ($initiator): ShowcaseDataset {
            $dataset = ShowcaseDataset::query()->create([
                'key' => 'SHOWCASE-'.now()->format('Ymd-His').'-'.Str::upper(Str::random(5)),
                'name' => 'Property operations showcase '.now()->format('Y-m-d H:i'),
                'status' => 'generating',
                'target_properties' => self::TARGET_BUILDINGS,
                'generated_properties' => 0,
                'counts_json' => [],
                'initiated_by_user_id' => $initiator->id,
                'started_at' => now(),
            ]);

            $this->createFoundation($dataset);
            $this->refreshCounts($dataset);

            return $dataset->fresh();
        });

        $this->dispatchMissing($dataset);

        return $dataset;
    }

    public function retry(ShowcaseDataset $dataset): ShowcaseDataset
    {
        abort_if($dataset->status === 'purged', 422, trans('app.errors.not_allowed'));

        $dataset->update([
            'status' => 'generating',
            'failure_details' => null,
            'completed_at' => null,
        ]);
        $this->dispatchMissing($dataset);

        return $dataset->fresh();
    }

    public function purge(ShowcaseDataset $dataset): void
    {
        abort_if(in_array($dataset->status, ['purging', 'purged'], true), 422, trans('app.errors.not_allowed'));

        $dataset->update(['status' => 'purging']);

        DB::transaction(function () use ($dataset): void {
            Portfolio::query()
                ->where('showcase_dataset_id', $dataset->id)
                ->each(function (Portfolio $portfolio): void {
                    $portfolio->delete();
                });
            User::query()
                ->where('showcase_dataset_id', $dataset->id)
                ->each(function (User $user): void {
                    $user->delete();
                });
        });

        Storage::disk('local')->deleteDirectory("showcase/{$dataset->key}");

        $dataset->update([
            'status' => 'purged',
            'generated_properties' => 0,
            'counts_json' => [],
            'purged_at' => now(),
        ]);
    }

    public function generateBuilding(int $datasetId, int $buildingIndex): void
    {
        $dataset = ShowcaseDataset::query()->findOrFail($datasetId);

        if (in_array($dataset->status, ['purging', 'purged'], true) || $buildingIndex < 0 || $buildingIndex >= self::TARGET_BUILDINGS) {
            return;
        }

        DB::transaction(function () use ($dataset, $buildingIndex): void {
            $portfolioIndex = intdiv($buildingIndex, self::BUILDINGS_PER_PORTFOLIO);
            $portfolio = Portfolio::query()
                ->where('showcase_dataset_id', $dataset->id)
                ->where('code', $this->portfolioCode($dataset, $portfolioIndex))
                ->firstOrFail();
            $owner = User::query()->findOrFail((int) $portfolio->owner_user_id);
            $manager = User::query()
                ->where('showcase_dataset_id', $dataset->id)
                ->where('portfolio_id', $portfolio->id)
                ->role('property_manager')
                ->orderBy('id')
                ->get()
                ->values()
                ->get($buildingIndex % 4);

            if (! $manager) {
                throw new \RuntimeException("No showcase manager for building {$buildingIndex}.");
            }

            $building = $this->building($dataset, $portfolio, $owner, $manager, $buildingIndex);
            $units = $this->floorsAndUnits($dataset, $portfolio, $building, $buildingIndex);
            $leases = $this->tenantsAndLeases($dataset, $portfolio, $manager, $units, $buildingIndex);
            $this->payments($dataset, $portfolio, $manager, $leases, $buildingIndex);
            $maintenance = $this->maintenance($portfolio, $manager, $leases, $buildingIndex);
            $this->expenses($portfolio, $manager, $building, $maintenance, $buildingIndex);
            $this->documents($dataset, $portfolio, $manager, $leases);
        }, 3);

        $this->refreshProgress($dataset->fresh());
    }

    public function recordFailure(int $datasetId, int $buildingIndex, string $message): void
    {
        $dataset = ShowcaseDataset::query()->find($datasetId);

        if (! $dataset || $dataset->status === 'purged') {
            return;
        }

        $failure = 'Building '.($buildingIndex + 1).': '.Str::limit($message, 600);
        $dataset->update([
            'status' => 'failed',
            'failure_details' => trim(($dataset->failure_details ? $dataset->failure_details."\n" : '').$failure),
        ]);
    }

    public function tagLegacyData(): ?ShowcaseDataset
    {
        $legacyPortfolios = Portfolio::query()
            ->whereNull('showcase_dataset_id')
            ->where('code', 'like', 'SHOW-%')
            ->get();

        if ($legacyPortfolios->isEmpty()) {
            return null;
        }

        $dataset = ShowcaseDataset::query()->firstOrCreate(
            ['key' => 'LEGACY-SHOWCASE'],
            [
                'name' => trans('app.showcase.legacy_name'),
                'status' => 'complete',
                'target_properties' => $legacyPortfolios->count(),
                'generated_properties' => $legacyPortfolios->count(),
                'started_at' => now(),
                'completed_at' => now(),
            ],
        );

        foreach ($legacyPortfolios as $portfolio) {
            $portfolio->update(['showcase_dataset_id' => $dataset->id]);
            User::query()
                ->where('portfolio_id', $portfolio->id)
                ->get()
                ->each(function (User $user) use ($dataset): void {
                    $user->update([
                        'showcase_dataset_id' => $dataset->id,
                        'status' => 'inactive',
                        'email' => "legacy-{$user->id}@showcase.invalid",
                        'password' => Hash::make(Str::password(40)),
                    ]);
                });
        }

        $this->refreshCounts($dataset);

        return $dataset->fresh();
    }

    private function createFoundation(ShowcaseDataset $dataset): void
    {
        foreach ($this->locations() as $portfolioIndex => $location) {
            $portfolio = Portfolio::query()->updateOrCreate(
                ['code' => $this->portfolioCode($dataset, $portfolioIndex)],
                [
                    'showcase_dataset_id' => $dataset->id,
                    'owner_user_id' => null,
                    'name_en' => "{$location['city_en']} Showcase Portfolio",
                    'name_ar' => "محفظة {$location['city_ar']} التجريبية",
                    'slug' => Str::slug("{$dataset->key}-{$location['city_en']}"),
                    'status' => 'active',
                    'contact_email' => 'portfolio.p'.($portfolioIndex + 1).".d{$dataset->id}@showcase.invalid",
                    'contact_phone' => '+966500'.str_pad((string) $portfolioIndex, 6, '0', STR_PAD_LEFT),
                    'city' => $location['city_en'],
                    'country' => 'Saudi Arabia',
                    'address' => $location['address_en'],
                    'address_ar' => $location['address_ar'],
                    'default_currency' => 'SAR',
                    'module_settings' => $this->moduleSettings(),
                ],
            );

            $owner = $this->showcaseUser(
                $dataset,
                $portfolio,
                'owner.p'.($portfolioIndex + 1).".d{$dataset->id}@showcase.invalid",
                'Showcase Owner '.($portfolioIndex + 1),
                'owner',
                $portfolioIndex * 10,
            );
            $portfolio->update(['owner_user_id' => $owner->id]);

            for ($managerIndex = 0; $managerIndex < 4; $managerIndex++) {
                $this->showcaseUser(
                    $dataset,
                    $portfolio,
                    'manager.p'.($portfolioIndex + 1).'.m'.($managerIndex + 1).".d{$dataset->id}@showcase.invalid",
                    'Showcase Manager '.($portfolioIndex + 1).'-'.($managerIndex + 1),
                    'property_manager',
                    ($portfolioIndex * 10) + $managerIndex + 1,
                );
            }
        }
    }

    private function showcaseUser(
        ShowcaseDataset $dataset,
        Portfolio $portfolio,
        string $email,
        string $name,
        string $role,
        int $phoneSuffix,
    ): User {
        $user = User::query()->updateOrCreate(
            ['email' => $email],
            [
                'showcase_dataset_id' => $dataset->id,
                'portfolio_id' => $portfolio->id,
                'name' => $name,
                'phone' => '+96655'.str_pad((string) $phoneSuffix, 7, '0', STR_PAD_LEFT),
                'preferred_locale' => $phoneSuffix % 2 === 0 ? 'ar' : 'en',
                'status' => 'inactive',
                'force_password_reset' => false,
                'email_verified_at' => null,
                'password' => Hash::make(Str::password(40)),
            ],
        );
        $user->syncRoles([$role]);

        return $user;
    }

    private function building(
        ShowcaseDataset $dataset,
        Portfolio $portfolio,
        User $owner,
        User $manager,
        int $index,
    ): Asset {
        $location = $this->locations()[intdiv($index, self::BUILDINGS_PER_PORTFOLIO)];
        $number = $index + 1;
        $usage = match ($index % 5) {
            0 => 'commercial',
            1 => 'mixed',
            default => 'residential',
        };
        $building = Asset::query()->updateOrCreate(
            ['code' => $this->buildingCode($dataset, $index)],
            [
                'portfolio_id' => $portfolio->id,
                'parent_id' => null,
                'asset_type' => 'building',
                'usage_type' => $usage,
                'title_en' => "{$location['city_en']} Operations Building {$number}",
                'title_ar' => "مبنى العمليات {$number} - {$location['city_ar']}",
                'slug' => Str::slug("{$dataset->key}-building-{$number}"),
                'status' => 'active',
                'occupancy_status' => 'partially_occupied',
                'rentable' => false,
                'valuation_amount' => 8_500_000 + ($index * 175_000),
                'currency' => 'SAR',
                'area' => 2_400 + ($index * 10),
                'sort_order' => $index,
                'address' => "{$number} {$location['address_en']}",
                'address_ar' => "{$location['address_ar']}، مبنى {$number}",
                'description_en' => 'Tagged showcase building for property operations scale testing.',
                'description_ar' => 'مبنى تجريبي موسوم لاختبار عمليات إدارة العقارات تحت الحمل.',
                'meta_json' => [
                    'map' => [
                        'zone' => $location['zone_en'],
                        'zone_en' => $location['zone_en'],
                        'zone_ar' => $location['zone_ar'],
                        'land_number' => sprintf('%s-%03d', $location['land_prefix'], $number),
                        'latitude' => $location['latitude'] + ((($index % 8) - 3.5) * 0.011),
                        'longitude' => $location['longitude'] + ((($index % 7) - 3) * 0.012),
                    ],
                ],
            ],
        );

        $building->stakeholders()->updateOrCreate(
            ['user_id' => $owner->id, 'relationship_type' => 'owner'],
            ['portfolio_id' => $portfolio->id, 'is_primary' => true, 'starts_on' => now()->subYears(2)->toDateString()],
        );
        $building->stakeholders()->updateOrCreate(
            ['user_id' => $manager->id, 'relationship_type' => 'manager'],
            ['portfolio_id' => $portfolio->id, 'is_primary' => true, 'starts_on' => now()->subYear()->toDateString()],
        );

        return $building;
    }

    /**
     * @return array<int, Asset>
     */
    private function floorsAndUnits(
        ShowcaseDataset $dataset,
        Portfolio $portfolio,
        Asset $building,
        int $buildingIndex,
    ): array {
        $units = [];

        for ($floorIndex = 0; $floorIndex < 4; $floorIndex++) {
            $floorNumber = $floorIndex + 1;
            $floor = Asset::query()->updateOrCreate(
                ['code' => $this->buildingCode($dataset, $buildingIndex)."-F{$floorNumber}"],
                [
                    'portfolio_id' => $portfolio->id,
                    'parent_id' => $building->id,
                    'asset_type' => 'floor',
                    'usage_type' => $building->usage_type,
                    'title_en' => "Floor {$floorNumber}",
                    'title_ar' => "الطابق {$floorNumber}",
                    'slug' => Str::slug("{$building->code}-floor-{$floorNumber}"),
                    'status' => 'active',
                    'occupancy_status' => 'partially_occupied',
                    'rentable' => false,
                    'valuation_amount' => 1_600_000,
                    'currency' => 'SAR',
                    'area' => 540,
                    'sort_order' => $floorIndex,
                    'level_label' => (string) $floorNumber,
                    'address' => $building->address,
                    'address_ar' => $building->address_ar,
                ],
            );

            for ($unitIndex = 0; $unitIndex < 4; $unitIndex++) {
                $unitNumber = ($floorNumber * 100) + $unitIndex + 1;
                $globalUnitIndex = ($floorIndex * 4) + $unitIndex;
                $occupied = $globalUnitIndex < 10;
                $maintenance = $globalUnitIndex === 14;
                $assetType = 'unit';
                $unit = Asset::query()->updateOrCreate(
                    ['code' => $this->buildingCode($dataset, $buildingIndex)."-U{$unitNumber}"],
                    [
                        'portfolio_id' => $portfolio->id,
                        'parent_id' => $floor->id,
                        'asset_type' => $assetType,
                        'usage_type' => $building->usage_type === 'mixed' && $floorIndex === 0 ? 'commercial' : $building->usage_type,
                        'title_en' => ($building->usage_type === 'commercial' ? 'Commercial Unit ' : 'Apartment ').$unitNumber,
                        'title_ar' => ($building->usage_type === 'commercial' ? 'وحدة تجارية ' : 'شقة ').$unitNumber,
                        'slug' => Str::slug("{$building->code}-unit-{$unitNumber}"),
                        'status' => 'active',
                        'occupancy_status' => $maintenance ? 'maintenance' : ($occupied ? 'occupied' : 'vacant'),
                        'rentable' => true,
                        'valuation_amount' => 420_000 + ($globalUnitIndex * 7_500),
                        'currency' => 'SAR',
                        'area' => 105 + ($unitIndex * 8),
                        'sort_order' => $unitIndex,
                        'level_label' => (string) $floorNumber,
                        'unit_label' => (string) $unitNumber,
                        'address' => $building->address,
                        'address_ar' => $building->address_ar,
                    ],
                );
                $units[] = $unit;
            }
        }

        return $units;
    }

    /**
     * @param  array<int, Asset>  $units
     * @return array<int, array{lease:Lease,tenant:TenantProfile,unit:Asset,user:User}>
     */
    private function tenantsAndLeases(
        ShowcaseDataset $dataset,
        Portfolio $portfolio,
        User $manager,
        array $units,
        int $buildingIndex,
    ): array {
        $records = [];

        for ($tenantIndex = 0; $tenantIndex < 12; $tenantIndex++) {
            $sequence = ($buildingIndex * 12) + $tenantIndex + 1;
            $user = $this->showcaseUser(
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
            [$status, $startedAt, $endsAt] = $this->leasePeriod($buildingIndex, $tenantIndex);
            $rent = 3_600 + (($buildingIndex % 5) * 450) + (($tenantIndex % 4) * 250);
            $lease = Lease::query()->updateOrCreate(
                ['code' => sprintf('%s-L%02d', $this->buildingCode($dataset, $buildingIndex), $tenantIndex + 1)],
                [
                    'portfolio_id' => $portfolio->id,
                    'tenant_profile_id' => $tenant->id,
                    'managed_by_user_id' => $manager->id,
                    'leaseable_type' => (new Asset)->getMorphClass(),
                    'leaseable_id' => $units[$tenantIndex]->id,
                    'status' => $status,
                    'payment_frequency' => 'monthly',
                    'started_at' => $startedAt,
                    'ends_at' => $endsAt,
                    'signed_at' => $status === 'draft' ? null : $startedAt,
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
            $this->installments($lease);
            $records[] = compact('lease', 'tenant', 'user') + ['unit' => $units[$tenantIndex]];
        }

        return $records;
    }

    private function installments(Lease $lease): void
    {
        $leaseStart = Carbon::parse((string) $lease->started_at);

        for ($sequence = 1; $sequence <= 12; $sequence++) {
            $periodStart = $leaseStart->copy()->addMonths($sequence - 1)->startOfMonth();
            LeaseInstallment::query()->updateOrCreate(
                ['lease_id' => $lease->id, 'sequence' => $sequence],
                [
                    'line_type' => 'rent',
                    'label' => "Rent {$sequence}",
                    'period_start' => $periodStart->toDateString(),
                    'period_end' => $periodStart->copy()->endOfMonth()->toDateString(),
                    'due_date' => $periodStart->toDateString(),
                    'amount_due' => $lease->rent_amount,
                    'amount_paid' => 0,
                    'status' => $periodStart->isPast() ? 'overdue' : 'pending',
                    'paid_at' => null,
                    'notes' => 'Showcase installment.',
                ],
            );
        }
    }

    /**
     * @param  array<int, array{lease:Lease,tenant:TenantProfile,unit:Asset,user:User}>  $records
     */
    private function payments(
        ShowcaseDataset $dataset,
        Portfolio $portfolio,
        User $manager,
        array $records,
        int $buildingIndex,
    ): void {
        foreach (array_slice($records, 0, 10) as $leaseIndex => $record) {
            $installments = LeaseInstallment::query()
                ->where('lease_id', $record['lease']->id)
                ->orderBy('sequence')
                ->limit(4)
                ->get();

            foreach ($installments as $paymentIndex => $installment) {
                $amount = $leaseIndex >= 8 && $paymentIndex >= 2
                    ? round((float) $record['lease']->rent_amount * 0.5, 2)
                    : (float) $record['lease']->rent_amount;
                $payment = Payment::query()->updateOrCreate(
                    ['reference' => sprintf('%s-P%02d-%02d', $this->buildingCode($dataset, $buildingIndex), $leaseIndex + 1, $paymentIndex + 1)],
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

    /**
     * @param  array<int, array{lease:Lease,tenant:TenantProfile,unit:Asset,user:User}>  $records
     * @return array<int, MaintenanceRequest>
     */
    private function maintenance(
        Portfolio $portfolio,
        User $manager,
        array $records,
        int $buildingIndex,
    ): array {
        $items = [];
        $categories = ['electrical', 'plumbing', 'hvac', 'appliance'];
        $statuses = ['open', 'in_progress', 'resolved', 'open'];
        $priorities = ['low', 'medium', 'high', 'urgent'];

        for ($index = 0; $index < 8; $index++) {
            $record = $records[$index];
            $status = $statuses[$index % count($statuses)];
            $request = MaintenanceRequest::query()->updateOrCreate(
                [
                    'portfolio_id' => $portfolio->id,
                    'asset_id' => $record['unit']->id,
                    'title' => sprintf('SHOW-B%03d maintenance %02d', $buildingIndex + 1, $index + 1),
                ],
                [
                    'lease_id' => $record['lease']->id,
                    'tenant_profile_id' => $record['tenant']->id,
                    'submitted_by_user_id' => $record['user']->id,
                    'assigned_to_user_id' => $manager->id,
                    'category' => $categories[$index % count($categories)],
                    'priority' => $priorities[$index % count($priorities)],
                    'status' => $status,
                    'description' => 'Showcase service request used to test queues, filters, and reports.',
                    'requested_at' => now()->subDays(($buildingIndex * 2) + $index),
                    'due_at' => now()->addDays(($index % 5) + 1),
                    'resolved_at' => $status === 'resolved' ? now()->subDay() : null,
                    'internal_notes' => 'Tagged showcase request.',
                    'meta_json' => ['showcase' => true],
                ],
            );
            $request->updates()->updateOrCreate(
                ['user_id' => $manager->id, 'comment' => 'Showcase request reviewed by property management.'],
                ['status_from' => null, 'status_to' => $status, 'is_public_comment' => true],
            );
            $items[] = $request;
        }

        return $items;
    }

    /**
     * @param  array<int, MaintenanceRequest>  $maintenance
     */
    private function expenses(
        Portfolio $portfolio,
        User $manager,
        Asset $building,
        array $maintenance,
        int $buildingIndex,
    ): void {
        $categories = ['maintenance', 'utilities', 'cleaning', 'security', 'insurance', 'management'];

        for ($index = 0; $index < 6; $index++) {
            ExpenseEntry::query()->updateOrCreate(
                [
                    'portfolio_id' => $portfolio->id,
                    'title' => sprintf('SHOW-B%03d expense %02d', $buildingIndex + 1, $index + 1),
                ],
                [
                    'asset_id' => $building->id,
                    'lease_id' => null,
                    'maintenance_request_id' => $index < 3 ? $maintenance[$index]->id : null,
                    'created_by_user_id' => $manager->id,
                    'category' => $categories[$index],
                    'description' => 'Tagged showcase operating expense.',
                    'incurred_on' => now()->subDays(($buildingIndex * 3) + ($index * 4))->toDateString(),
                    'amount' => 450 + ($index * 325) + ($buildingIndex * 15),
                    'currency' => 'SAR',
                    'vendor_name' => 'Showcase Vendor '.($index + 1),
                    'status' => 'posted',
                    'meta_json' => ['showcase' => true],
                ],
            );
        }
    }

    /**
     * @param  array<int, array{lease:Lease,tenant:TenantProfile,unit:Asset,user:User}>  $records
     */
    private function documents(
        ShowcaseDataset $dataset,
        Portfolio $portfolio,
        User $manager,
        array $records,
    ): void {
        foreach ($records as $record) {
            foreach (['lease_contract', 'signed_contract'] as $type) {
                $path = "showcase/{$dataset->key}/documents/{$record['lease']->code}-{$type}.pdf";
                $content = $this->pdf("{$record['lease']->code} {$type}");
                Storage::disk('local')->put($path, $content);
                Document::query()->updateOrCreate(
                    [
                        'portfolio_id' => $portfolio->id,
                        'documentable_type' => $record['lease']->getMorphClass(),
                        'documentable_id' => $record['lease']->id,
                        'type' => $type,
                    ],
                    [
                        'uploaded_by_user_id' => $manager->id,
                        'title_en' => ucfirst(str_replace('_', ' ', $type))." {$record['lease']->code}",
                        'title_ar' => ($type === 'signed_contract' ? 'العقد الموقع ' : 'عقد الإيجار ').$record['lease']->code,
                        'disk' => 'local',
                        'file_path' => $path,
                        'original_name' => basename($path),
                        'mime_type' => 'application/pdf',
                        'file_size' => strlen($content),
                        'is_public' => $type === 'lease_contract',
                        'meta_json' => ['showcase' => true, 'dataset_key' => $dataset->key],
                    ],
                );
            }
        }
    }

    private function refreshProgress(ShowcaseDataset $dataset): void
    {
        $generated = Asset::query()
            ->whereIn('portfolio_id', $dataset->portfolios()->pluck('id'))
            ->where('asset_type', 'building')
            ->where('code', 'like', "SD{$dataset->id}-B%")
            ->count();
        $dataset->update([
            'generated_properties' => $generated,
            'status' => $generated >= $dataset->target_properties ? 'complete' : 'generating',
            'completed_at' => $generated >= $dataset->target_properties ? now() : null,
        ]);
        $this->refreshCounts($dataset);
    }

    private function refreshCounts(ShowcaseDataset $dataset): void
    {
        $portfolioIds = $dataset->portfolios()->pluck('id');
        $leaseIds = Lease::query()->whereIn('portfolio_id', $portfolioIds)->pluck('id');
        $counts = [
            'portfolios' => $portfolioIds->count(),
            'buildings' => Asset::query()->whereIn('portfolio_id', $portfolioIds)->where('asset_type', 'building')->count(),
            'floors' => Asset::query()->whereIn('portfolio_id', $portfolioIds)->where('asset_type', 'floor')->count(),
            'units' => Asset::query()->whereIn('portfolio_id', $portfolioIds)->whereIn('asset_type', ['unit', 'space'])->count(),
            'users' => User::query()->where('showcase_dataset_id', $dataset->id)->count(),
            'owners' => User::query()->where('showcase_dataset_id', $dataset->id)->role('owner')->count(),
            'managers' => User::query()->where('showcase_dataset_id', $dataset->id)->role('property_manager')->count(),
            'tenant_accounts' => User::query()->where('showcase_dataset_id', $dataset->id)->role('tenant')->count(),
            'tenants' => TenantProfile::query()->whereIn('portfolio_id', $portfolioIds)->count(),
            'leases' => $leaseIds->count(),
            'installments' => LeaseInstallment::query()->whereIn('lease_id', $leaseIds)->count(),
            'payments' => Payment::query()->whereIn('portfolio_id', $portfolioIds)->count(),
            'maintenance' => MaintenanceRequest::query()->whereIn('portfolio_id', $portfolioIds)->count(),
            'expenses' => ExpenseEntry::query()->whereIn('portfolio_id', $portfolioIds)->count(),
            'documents' => Document::query()->whereIn('portfolio_id', $portfolioIds)->count(),
        ];
        $dataset->update(['counts_json' => $counts]);
    }

    private function dispatchMissing(ShowcaseDataset $dataset): void
    {
        $existingCodes = Asset::query()
            ->whereIn('portfolio_id', $dataset->portfolios()->pluck('id'))
            ->where('asset_type', 'building')
            ->pluck('code')
            ->all();

        for ($index = 0; $index < self::TARGET_BUILDINGS; $index++) {
            if (! in_array($this->buildingCode($dataset, $index), $existingCodes, true)) {
                GenerateShowcaseBuilding::dispatch($dataset->id, $index);
            }
        }
    }

    /**
     * @return array{0:string,1:string,2:string}
     */
    private function leasePeriod(int $buildingIndex, int $tenantIndex): array
    {
        if ($tenantIndex < 10) {
            $start = now()->startOfMonth()->subMonths(7 + (($buildingIndex + $tenantIndex) % 4));
            $end = $tenantIndex === 0
                ? now()->addDays(20 + ($buildingIndex % 30))
                : $start->copy()->addYear()->subDay();

            return ['active', $start->toDateString(), $end->toDateString()];
        }

        if ($tenantIndex === 10) {
            return [
                'expired',
                now()->subMonths(18)->startOfMonth()->toDateString(),
                now()->subMonths(6)->endOfMonth()->toDateString(),
            ];
        }

        if ($buildingIndex % 2 === 0) {
            return [
                'terminated',
                now()->subMonths(15)->startOfMonth()->toDateString(),
                now()->subMonths(3)->endOfMonth()->toDateString(),
            ];
        }

        return [
            'draft',
            now()->addMonth()->startOfMonth()->toDateString(),
            now()->addMonths(13)->endOfMonth()->toDateString(),
        ];
    }

    private function portfolioCode(ShowcaseDataset $dataset, int $index): string
    {
        return sprintf('SD%d-P%02d', $dataset->id, $index + 1);
    }

    private function buildingCode(ShowcaseDataset $dataset, int $index): string
    {
        return sprintf('SD%d-B%03d', $dataset->id, $index + 1);
    }

    /**
     * @return array<int, array{
     *     city_en:string,
     *     city_ar:string,
     *     zone_en:string,
     *     zone_ar:string,
     *     address_en:string,
     *     address_ar:string,
     *     land_prefix:string,
     *     latitude:float,
     *     longitude:float
     * }>
     */
    private function locations(): array
    {
        return [
            [
                'city_en' => 'Riyadh',
                'city_ar' => 'الرياض',
                'zone_en' => 'North Riyadh',
                'zone_ar' => 'شمال الرياض',
                'address_en' => 'King Fahd Road, Riyadh',
                'address_ar' => 'طريق الملك فهد، الرياض',
                'land_prefix' => 'RUH',
                'latitude' => 24.7136,
                'longitude' => 46.6753,
            ],
            [
                'city_en' => 'Jeddah',
                'city_ar' => 'جدة',
                'zone_en' => 'Jeddah Coast',
                'zone_ar' => 'ساحل جدة',
                'address_en' => 'Prince Sultan Road, Jeddah',
                'address_ar' => 'طريق الأمير سلطان، جدة',
                'land_prefix' => 'JED',
                'latitude' => 21.5433,
                'longitude' => 39.1728,
            ],
            [
                'city_en' => 'Dammam',
                'city_ar' => 'الدمام',
                'zone_en' => 'Dammam Business District',
                'zone_ar' => 'منطقة أعمال الدمام',
                'address_en' => 'King Saud Street, Dammam',
                'address_ar' => 'شارع الملك سعود، الدمام',
                'land_prefix' => 'DMM',
                'latitude' => 26.4207,
                'longitude' => 50.0888,
            ],
            [
                'city_en' => 'Makkah',
                'city_ar' => 'مكة المكرمة',
                'zone_en' => 'Makkah Central',
                'zone_ar' => 'وسط مكة',
                'address_en' => 'Ibrahim Al Khalil Road, Makkah',
                'address_ar' => 'طريق إبراهيم الخليل، مكة المكرمة',
                'land_prefix' => 'MAK',
                'latitude' => 21.3891,
                'longitude' => 39.8579,
            ],
            [
                'city_en' => 'Madinah',
                'city_ar' => 'المدينة المنورة',
                'zone_en' => 'Madinah North',
                'zone_ar' => 'شمال المدينة',
                'address_en' => 'King Abdullah Road, Madinah',
                'address_ar' => 'طريق الملك عبدالله، المدينة المنورة',
                'land_prefix' => 'MED',
                'latitude' => 24.5247,
                'longitude' => 39.5692,
            ],
        ];
    }

    /**
     * @return array<string, bool>
     */
    private function moduleSettings(): array
    {
        return [
            'assets' => true,
            'tenants' => true,
            'leases' => true,
            'payments' => true,
            'maintenance' => true,
            'expenses' => true,
            'documents' => true,
            'reports' => true,
            'media' => true,
            'users' => true,
        ];
    }

    private function pdf(string $title): string
    {
        $safeTitle = preg_replace('/[^\x20-\x7E]/', '', $title) ?: 'Showcase document';
        $stream = 'BT /F1 16 Tf 72 740 Td ('.str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $safeTitle).') Tj ET';
        $objects = [
            '<< /Type /Catalog /Pages 2 0 R >>',
            '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>',
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
            '<< /Length '.strlen($stream)." >>\nstream\n{$stream}\nendstream",
        ];
        $pdf = "%PDF-1.4\n";
        $offsets = [0];

        foreach ($objects as $index => $object) {
            $offsets[] = strlen($pdf);
            $pdf .= ($index + 1)." 0 obj\n{$object}\nendobj\n";
        }

        $xref = strlen($pdf);
        $pdf .= "xref\n0 ".(count($objects) + 1)."\n0000000000 65535 f \n";
        foreach (array_slice($offsets, 1) as $offset) {
            $pdf .= sprintf("%010d 00000 n \n", $offset);
        }

        return $pdf.'trailer << /Size '.(count($objects) + 1)." /Root 1 0 R >>\nstartxref\n{$xref}\n%%EOF\n";
    }
}
