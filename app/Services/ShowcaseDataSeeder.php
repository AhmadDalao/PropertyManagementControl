<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\Document;
use App\Models\ExpenseEntry;
use App\Models\Lease;
use App\Models\MaintenanceRequest;
use App\Models\Payment;
use App\Models\Portfolio;
use App\Models\ReportPreset;
use App\Models\TenantProfile;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ShowcaseDataSeeder
{
    public function __construct(
        private readonly LandingContentSeeder $landingContentSeeder,
        private readonly LeaseFinancialService $leaseFinancials,
    ) {}

    /**
     * @return array<string, int>
     */
    public function seed(): array
    {
        return DB::transaction(function (): array {
            $this->landingContentSeeder->seed();

            $created = [
                'portfolios' => 0,
                'users' => 0,
                'assets' => 0,
                'leases' => 0,
                'payments' => 0,
                'maintenance' => 0,
                'expenses' => 0,
                'documents' => 0,
                'presets' => 0,
            ];

            foreach ($this->showcasePortfolios() as $portfolioData) {
                $owner = $this->user($portfolioData['owner']);
                $created['users'] += (int) $owner->wasRecentlyCreated;

                $portfolio = $this->portfolio($owner, $portfolioData);
                $created['portfolios'] += (int) $portfolio->wasRecentlyCreated;
                $owner->update(['portfolio_id' => $portfolio->id]);

                $manager = $this->user([...$portfolioData['manager'], 'portfolio_id' => $portfolio->id]);
                $tenant = $this->user([...$portfolioData['tenant'], 'portfolio_id' => $portfolio->id]);
                $created['users'] += (int) $manager->wasRecentlyCreated + (int) $tenant->wasRecentlyCreated;

                $tenantProfile = $this->tenantProfile($portfolio, $tenant, $portfolioData);
                $assets = $this->assetTree($portfolio, $owner, $manager, $portfolioData);
                $created['assets'] += $assets['created'];

                $lease = $this->lease($portfolio, $tenantProfile, $assets['occupied_unit'], $manager, $portfolioData);
                $created['leases'] += (int) $lease['created'];

                $created['payments'] += $this->payments($portfolio, $lease['lease'], $tenantProfile, $manager, $portfolioData);
                $created['maintenance'] += $this->maintenance($portfolio, $lease['lease'], $tenantProfile, $tenant, $manager, $assets);
                $created['expenses'] += $this->expenses($portfolio, $manager, $assets);
                $created['documents'] += $this->documents($portfolio, $lease['lease'], $manager);
                $created['presets'] += $this->reportPresets($portfolio, $owner);
            }

            $created['presets'] += $this->globalReportPresets();

            return $created;
        });
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function showcasePortfolios(): array
    {
        return [
            [
                'code' => 'SHOW-RIYADH',
                'slug' => 'showcase-riyadh-prime',
                'name_en' => 'Showcase Riyadh Prime',
                'name_ar' => 'عرض الرياض برايم',
                'city' => 'Riyadh',
                'address' => 'King Fahd Road, Riyadh',
                'building' => ['name_en' => 'Olaya Heights', 'name_ar' => 'مرتفعات العليا', 'code' => 'OLAYA', 'value' => 9200000],
                'map' => ['zone' => 'Riyadh Prime', 'land_number' => 'RP-118', 'latitude' => 24.7136, 'longitude' => 46.6753, 'x' => 31, 'y' => 36],
                'owner' => ['name' => 'Noura Showcase Owner', 'email' => 'showcase.owner.riyadh@property.ahmaddalao.com', 'phone' => '+966550100001', 'role' => 'owner'],
                'manager' => ['name' => 'Faisal Showcase Manager', 'email' => 'showcase.manager.riyadh@property.ahmaddalao.com', 'phone' => '+966550100002', 'role' => 'property_manager'],
                'tenant' => ['name' => 'Sara Showcase Tenant', 'email' => 'showcase.tenant.riyadh@property.ahmaddalao.com', 'phone' => '+966550100003', 'role' => 'tenant'],
                'rent' => 6200,
                'deposit' => 6200,
                'paid_months' => 3,
                'usage' => 'mixed',
            ],
            [
                'code' => 'SHOW-JEDDAH',
                'slug' => 'showcase-jeddah-coast',
                'name_en' => 'Showcase Jeddah Coast',
                'name_ar' => 'عرض جدة الساحل',
                'city' => 'Jeddah',
                'address' => 'Al Shati District, Jeddah',
                'building' => ['name_en' => 'Coral Gate Residence', 'name_ar' => 'بوابة كورال السكنية', 'code' => 'CORAL', 'value' => 7800000],
                'map' => ['zone' => 'Jeddah Coast', 'land_number' => 'JC-404', 'latitude' => 21.5433, 'longitude' => 39.1728, 'x' => 68, 'y' => 52],
                'owner' => ['name' => 'Omar Showcase Owner', 'email' => 'showcase.owner.jeddah@property.ahmaddalao.com', 'phone' => '+966550200001', 'role' => 'owner'],
                'manager' => ['name' => 'Lina Showcase Manager', 'email' => 'showcase.manager.jeddah@property.ahmaddalao.com', 'phone' => '+966550200002', 'role' => 'property_manager'],
                'tenant' => ['name' => 'Yousef Showcase Tenant', 'email' => 'showcase.tenant.jeddah@property.ahmaddalao.com', 'phone' => '+966550200003', 'role' => 'tenant'],
                'rent' => 5400,
                'deposit' => 5400,
                'paid_months' => 4,
                'usage' => 'residential',
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function user(array $data): User
    {
        $user = User::query()->firstOrNew(['email' => $data['email']]);
        $user->fill([
            'portfolio_id' => $data['portfolio_id'] ?? $user->portfolio_id,
            'name' => $data['name'],
            'phone' => $data['phone'],
            'preferred_locale' => 'en',
            'status' => 'active',
            'force_password_reset' => false,
        ]);

        if (! $user->exists) {
            $user->email_verified_at = now();
            $user->password = Hash::make(Str::password(32));
        }

        $user->save();
        $user->syncRoles([$data['role']]);

        return $user;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function portfolio(User $owner, array $data): Portfolio
    {
        return Portfolio::query()->updateOrCreate(
            ['code' => $data['code']],
            [
                'owner_user_id' => $owner->id,
                'name_en' => $data['name_en'],
                'name_ar' => $data['name_ar'],
                'slug' => $data['slug'],
                'status' => 'active',
                'contact_email' => $owner->email,
                'contact_phone' => $owner->phone,
                'city' => $data['city'],
                'country' => 'Saudi Arabia',
                'address' => $data['address'],
                'default_currency' => 'SAR',
                'module_settings' => [
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
                ],
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function tenantProfile(Portfolio $portfolio, User $tenant, array $data): TenantProfile
    {
        return TenantProfile::query()->updateOrCreate(
            ['user_id' => $tenant->id],
            [
                'portfolio_id' => $portfolio->id,
                'profile_type' => 'individual',
                'national_id' => substr('9'.abs(crc32($tenant->email)).'000000000', 0, 10),
                'emergency_contact_name' => 'Showcase Emergency Contact',
                'emergency_contact_phone' => '+966559999999',
                'address' => $data['address'],
                'status' => 'active',
                'notes' => 'Showcase tenant used to demonstrate lease, payment, document, and maintenance workflows.',
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{created:int,building:Asset,occupied_unit:Asset,vacant_unit:Asset,maintenance_unit:Asset}
     */
    private function assetTree(Portfolio $portfolio, User $owner, User $manager, array $data): array
    {
        $prefix = $data['building']['code'];
        $created = 0;
        $building = $this->asset($portfolio, null, [
            'asset_type' => 'building',
            'usage_type' => $data['usage'],
            'title_en' => $data['building']['name_en'],
            'title_ar' => $data['building']['name_ar'],
            'code' => "{$prefix}-BUILDING",
            'address' => $data['address'],
            'valuation_amount' => $data['building']['value'],
            'occupancy_status' => 'partially_occupied',
            'rentable' => false,
            'map_zone' => $data['map']['zone'],
            'land_number' => $data['map']['land_number'],
            'latitude' => $data['map']['latitude'],
            'longitude' => $data['map']['longitude'],
            'map_x' => $data['map']['x'],
            'map_y' => $data['map']['y'],
        ]);
        $created += (int) $building->wasRecentlyCreated;

        $floorOne = $this->asset($portfolio, $building, [
            'asset_type' => 'floor',
            'usage_type' => 'residential',
            'title_en' => 'First Floor',
            'title_ar' => 'الدور الأول',
            'code' => "{$prefix}-F1",
            'valuation_amount' => 1800000,
            'occupancy_status' => 'occupied',
            'rentable' => false,
            'level_label' => '1',
            'sort_order' => 1,
        ]);
        $floorTwo = $this->asset($portfolio, $building, [
            'asset_type' => 'floor',
            'usage_type' => 'residential',
            'title_en' => 'Second Floor',
            'title_ar' => 'الدور الثاني',
            'code' => "{$prefix}-F2",
            'valuation_amount' => 1700000,
            'occupancy_status' => 'partially_occupied',
            'rentable' => false,
            'level_label' => '2',
            'sort_order' => 2,
        ]);
        $created += (int) $floorOne->wasRecentlyCreated + (int) $floorTwo->wasRecentlyCreated;

        $occupied = $this->asset($portfolio, $floorOne, [
            'asset_type' => 'unit',
            'usage_type' => 'residential',
            'title_en' => 'Apartment 101',
            'title_ar' => 'شقة 101',
            'code' => "{$prefix}-101",
            'valuation_amount' => 520000,
            'area' => 165,
            'occupancy_status' => 'occupied',
            'rentable' => true,
            'unit_label' => '101',
            'sort_order' => 1,
        ]);
        $vacant = $this->asset($portfolio, $floorOne, [
            'asset_type' => 'unit',
            'usage_type' => 'residential',
            'title_en' => 'Apartment 102',
            'title_ar' => 'شقة 102',
            'code' => "{$prefix}-102",
            'valuation_amount' => 495000,
            'area' => 152,
            'occupancy_status' => 'vacant',
            'rentable' => true,
            'unit_label' => '102',
            'sort_order' => 2,
        ]);
        $maintenance = $this->asset($portfolio, $floorTwo, [
            'asset_type' => 'unit',
            'usage_type' => 'residential',
            'title_en' => 'Apartment 201',
            'title_ar' => 'شقة 201',
            'code' => "{$prefix}-201",
            'valuation_amount' => 535000,
            'area' => 170,
            'occupancy_status' => 'maintenance',
            'rentable' => true,
            'unit_label' => '201',
            'sort_order' => 1,
        ]);
        $created += (int) $occupied->wasRecentlyCreated + (int) $vacant->wasRecentlyCreated + (int) $maintenance->wasRecentlyCreated;

        if ($data['usage'] === 'mixed') {
            $retail = $this->asset($portfolio, $building, [
                'asset_type' => 'space',
                'usage_type' => 'commercial',
                'title_en' => 'Ground Retail Space',
                'title_ar' => 'مساحة تجارية أرضية',
                'code' => "{$prefix}-SHOP",
                'valuation_amount' => 980000,
                'area' => 220,
                'occupancy_status' => 'vacant',
                'rentable' => true,
                'unit_label' => 'SHOP',
                'sort_order' => 0,
            ]);
            $created += (int) $retail->wasRecentlyCreated;
        }

        foreach ([$building, $floorOne, $floorTwo, $occupied, $vacant, $maintenance] as $asset) {
            $asset->stakeholders()->updateOrCreate(
                ['user_id' => $owner->id, 'relationship_type' => 'owner'],
                ['portfolio_id' => $portfolio->id, 'is_primary' => true, 'starts_on' => now()->subYear()->toDateString()],
            );
            $asset->stakeholders()->updateOrCreate(
                ['user_id' => $manager->id, 'relationship_type' => 'manager'],
                ['portfolio_id' => $portfolio->id, 'is_primary' => true, 'starts_on' => now()->subMonths(10)->toDateString()],
            );
        }

        return [
            'created' => $created,
            'building' => $building,
            'occupied_unit' => $occupied,
            'vacant_unit' => $vacant,
            'maintenance_unit' => $maintenance,
        ];
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function asset(Portfolio $portfolio, ?Asset $parent, array $attributes): Asset
    {
        return Asset::query()->updateOrCreate(
            ['code' => $attributes['code']],
            [
                'portfolio_id' => $portfolio->id,
                'parent_id' => $parent?->id,
                'asset_type' => $attributes['asset_type'],
                'usage_type' => $attributes['usage_type'],
                'title_en' => $attributes['title_en'],
                'title_ar' => $attributes['title_ar'],
                'slug' => Str::slug($attributes['title_en'].' '.$attributes['code']),
                'status' => 'active',
                'occupancy_status' => $attributes['occupancy_status'],
                'rentable' => (bool) $attributes['rentable'],
                'valuation_amount' => $attributes['valuation_amount'],
                'currency' => 'SAR',
                'area' => $attributes['area'] ?? null,
                'sort_order' => $attributes['sort_order'] ?? 0,
                'level_label' => $attributes['level_label'] ?? null,
                'unit_label' => $attributes['unit_label'] ?? null,
                'address' => $attributes['address'] ?? $parent?->address,
                'description_en' => 'Showcase record for ownership, occupancy, valuation, reporting, and maintenance workflows.',
                'description_ar' => 'سجل تجريبي لعرض الملكية والإشغال والتقييم والتقارير والصيانة.',
                'meta_json' => $this->assetMeta($attributes),
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>|null
     */
    private function assetMeta(array $attributes): ?array
    {
        $map = [
            'zone' => $attributes['map_zone'] ?? null,
            'land_number' => $attributes['land_number'] ?? null,
            'latitude' => $attributes['latitude'] ?? null,
            'longitude' => $attributes['longitude'] ?? null,
            'x' => $attributes['map_x'] ?? null,
            'y' => $attributes['map_y'] ?? null,
        ];

        $map = array_filter($map, fn ($value) => $value !== null && $value !== '');

        return $map === [] ? null : ['map' => $map];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    /**
     * @param  array<string, mixed>  $data
     * @return array{lease:Lease,created:bool}
     */
    private function lease(Portfolio $portfolio, TenantProfile $tenant, Asset $asset, User $manager, array $data): array
    {
        $code = $data['building']['code'].'-SHOW-LEASE';
        $lease = Lease::query()->updateOrCreate(
            ['code' => $code],
            [
                'portfolio_id' => $portfolio->id,
                'tenant_profile_id' => $tenant->id,
                'managed_by_user_id' => $manager->id,
                'leaseable_type' => $asset->getMorphClass(),
                'leaseable_id' => $asset->id,
                'status' => 'active',
                'payment_frequency' => 'monthly',
                'started_at' => now()->startOfMonth()->subMonths(5)->toDateString(),
                'ends_at' => now()->startOfMonth()->addMonths(7)->endOfMonth()->toDateString(),
                'signed_at' => now()->startOfMonth()->subMonths(5)->toDateString(),
                'renewal_notice_days' => 45,
                'rent_amount' => $data['rent'],
                'deposit_amount' => $data['deposit'],
                'tax_amount' => 0,
                'discount_amount' => 0,
                'currency' => 'SAR',
                'billing_day' => 1,
                'notes' => 'Showcase active lease with partial payment history and a remaining balance.',
                'terms_json' => [
                    'utilities' => 'Tenant pays electricity. Owner covers shared-area service.',
                    'notice' => 'Forty-five-day renewal notice.',
                ],
            ],
        );

        Payment::query()->where('lease_id', $lease->id)->delete();
        $this->leaseFinancials->syncInstallments($lease);

        return [
            'lease' => $lease->fresh(['installments']),
            'created' => $lease->wasRecentlyCreated,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function payments(Portfolio $portfolio, Lease $lease, TenantProfile $tenant, User $manager, array $data): int
    {
        $created = 0;
        $prefix = $data['building']['code'];
        $payments = [
            ['reference' => "{$prefix}-SHOW-PAY-001", 'months_ago' => 5, 'amount' => $data['deposit'] + $data['rent'], 'notes' => 'Deposit and first month rent.'],
        ];

        for ($month = 4; $month >= max(1, 5 - (int) $data['paid_months'] + 1); $month--) {
            $payments[] = [
                'reference' => "{$prefix}-SHOW-PAY-00".(6 - $month),
                'months_ago' => $month,
                'amount' => $data['rent'],
                'notes' => 'Monthly rent payment.',
            ];
        }

        foreach ($payments as $paymentData) {
            $payment = Payment::query()->updateOrCreate(
                ['reference' => $paymentData['reference']],
                [
                    'portfolio_id' => $portfolio->id,
                    'lease_id' => $lease->id,
                    'tenant_profile_id' => $tenant->id,
                    'recorded_by_user_id' => $manager->id,
                    'type' => 'rent',
                    'method' => 'bank_transfer',
                    'status' => 'posted',
                    'received_on' => now()->startOfMonth()->subMonths($paymentData['months_ago'])->addDays(2)->toDateString(),
                    'amount' => $paymentData['amount'],
                    'currency' => 'SAR',
                    'notes' => $paymentData['notes'],
                ],
            );
            $created += (int) $payment->wasRecentlyCreated;
            $this->leaseFinancials->allocatePayment($payment);
        }

        return $created;
    }

    /**
     * @param  array<string, Asset|int>  $assets
     */
    private function maintenance(Portfolio $portfolio, Lease $lease, TenantProfile $tenant, User $tenantUser, User $manager, array $assets): int
    {
        $created = 0;
        $items = [
            ['title' => 'AC cooling check', 'category' => 'hvac', 'priority' => 'high', 'status' => 'open', 'asset' => $assets['occupied_unit'], 'days' => 2],
            ['title' => 'Kitchen sink leak', 'category' => 'plumbing', 'priority' => 'medium', 'status' => 'in_progress', 'asset' => $assets['occupied_unit'], 'days' => 6],
            ['title' => 'Common hallway paint touch-up', 'category' => 'general', 'priority' => 'low', 'status' => 'resolved', 'asset' => $assets['building'], 'days' => 18],
        ];

        foreach ($items as $item) {
            /** @var Asset $asset */
            $asset = $item['asset'];
            $request = MaintenanceRequest::query()->updateOrCreate(
                [
                    'portfolio_id' => $portfolio->id,
                    'title' => $item['title'],
                ],
                [
                    'asset_id' => $asset->id,
                    'lease_id' => $lease->id,
                    'tenant_profile_id' => $tenant->id,
                    'submitted_by_user_id' => $tenantUser->id,
                    'assigned_to_user_id' => $manager->id,
                    'category' => $item['category'],
                    'priority' => $item['priority'],
                    'status' => $item['status'],
                    'description' => 'Showcase maintenance request for owner and tenant workflow testing.',
                    'requested_at' => now()->subDays($item['days']),
                    'resolved_at' => $item['status'] === 'resolved' ? now()->subDays(12) : null,
                ],
            );
            $created += (int) $request->wasRecentlyCreated;
            $request->updates()->updateOrCreate(
                ['comment' => 'Showcase workflow update.'],
                ['user_id' => $manager->id, 'status_to' => $item['status'], 'is_public_comment' => true],
            );
        }

        return $created;
    }

    /**
     * @param  array<string, Asset|int>  $assets
     */
    private function expenses(Portfolio $portfolio, User $manager, array $assets): int
    {
        $created = 0;
        $items = [
            ['title' => 'AC service visit', 'category' => 'maintenance', 'amount' => 850, 'asset' => $assets['occupied_unit'], 'days' => 2, 'vendor' => 'Fast Cool Services'],
            ['title' => 'Shared utilities', 'category' => 'utilities', 'amount' => 1640, 'asset' => $assets['building'], 'days' => 8, 'vendor' => 'Utility Provider'],
            ['title' => 'Elevator inspection', 'category' => 'compliance', 'amount' => 1200, 'asset' => $assets['building'], 'days' => 20, 'vendor' => 'LiftCare Saudi'],
        ];

        foreach ($items as $item) {
            /** @var Asset $asset */
            $asset = $item['asset'];
            $expense = ExpenseEntry::query()->updateOrCreate(
                [
                    'portfolio_id' => $portfolio->id,
                    'title' => $item['title'],
                ],
                [
                    'asset_id' => $asset->id,
                    'created_by_user_id' => $manager->id,
                    'category' => $item['category'],
                    'description' => 'Showcase expense for net revenue and owner reports.',
                    'incurred_on' => now()->subDays($item['days'])->toDateString(),
                    'amount' => $item['amount'],
                    'currency' => 'SAR',
                    'vendor_name' => $item['vendor'],
                    'status' => 'posted',
                ],
            );
            $created += (int) $expense->wasRecentlyCreated;
        }

        return $created;
    }

    private function documents(Portfolio $portfolio, Lease $lease, User $manager): int
    {
        $created = 0;
        $items = [
            ['type' => 'lease_contract', 'title_en' => "Generated lease {$lease->code}", 'title_ar' => "عقد مولد {$lease->code}"],
            ['type' => 'signed_contract', 'title_en' => "Signed lease {$lease->code}", 'title_ar' => "عقد موقع {$lease->code}"],
            ['type' => 'tenant_statement', 'title_en' => "Tenant statement {$lease->code}", 'title_ar' => "كشف حساب المستأجر {$lease->code}"],
        ];

        foreach ($items as $item) {
            $path = "showcase/documents/{$lease->code}-{$item['type']}.txt";
            $content = "{$item['title_en']}\n\nShowcase document. Replace with PDF output or uploaded signed files.";
            Storage::disk('local')->put($path, $content);

            $document = Document::query()->updateOrCreate(
                ['file_path' => $path],
                [
                    'portfolio_id' => $portfolio->id,
                    'uploaded_by_user_id' => $manager->id,
                    'documentable_type' => $lease->getMorphClass(),
                    'documentable_id' => $lease->id,
                    'type' => $item['type'],
                    'title_en' => $item['title_en'],
                    'title_ar' => $item['title_ar'],
                    'disk' => 'local',
                    'original_name' => basename($path),
                    'mime_type' => 'text/plain',
                    'file_size' => strlen($content),
                    'is_public' => $item['type'] !== 'signed_contract',
                ],
            );
            $created += (int) $document->wasRecentlyCreated;
        }

        return $created;
    }

    private function reportPresets(Portfolio $portfolio, User $owner): int
    {
        $created = 0;
        $items = [
            ['title_en' => 'This month collection health', 'title_ar' => 'صحة التحصيل لهذا الشهر', 'filters_json' => ['date_from' => now()->startOfMonth()->toDateString(), 'date_to' => now()->toDateString()]],
            ['title_en' => 'Open maintenance backlog', 'title_ar' => 'طلبات الصيانة المفتوحة', 'filters_json' => ['date_from' => now()->subMonths(2)->toDateString(), 'date_to' => now()->toDateString()]],
            ['title_en' => 'Quarter net revenue', 'title_ar' => 'صافي إيراد الربع', 'filters_json' => ['date_from' => now()->subMonths(3)->startOfMonth()->toDateString(), 'date_to' => now()->toDateString()]],
        ];

        foreach ($items as $item) {
            $preset = ReportPreset::query()->updateOrCreate(
                [
                    'portfolio_id' => $portfolio->id,
                    'resource' => 'portfolio-report',
                    'title_en' => $item['title_en'],
                ],
                [
                    'user_id' => $owner->id,
                    'title_ar' => $item['title_ar'],
                    'filters_json' => $item['filters_json'],
                    'visibility' => 'portfolio',
                    'is_default' => false,
                ],
            );
            $created += (int) $preset->wasRecentlyCreated;
        }

        return $created;
    }

    private function globalReportPresets(): int
    {
        $superadmin = User::role('superadmin')->oldest('id')->first();

        if (! $superadmin) {
            return 0;
        }

        $created = 0;
        $items = [
            ['title_en' => 'Platform collection health', 'title_ar' => 'صحة التحصيل للمنصة', 'filters_json' => ['date_from' => now()->startOfYear()->toDateString(), 'date_to' => now()->toDateString()]],
            ['title_en' => 'Platform maintenance pressure', 'title_ar' => 'ضغط الصيانة للمنصة', 'filters_json' => ['date_from' => now()->subMonths(2)->toDateString(), 'date_to' => now()->toDateString()]],
            ['title_en' => 'Portfolio net revenue comparison', 'title_ar' => 'مقارنة صافي إيرادات المحافظ', 'filters_json' => ['date_from' => now()->subMonths(3)->startOfMonth()->toDateString(), 'date_to' => now()->toDateString()]],
        ];

        foreach ($items as $item) {
            $preset = ReportPreset::query()->updateOrCreate(
                [
                    'portfolio_id' => null,
                    'resource' => 'portfolio-report',
                    'title_en' => $item['title_en'],
                ],
                [
                    'user_id' => $superadmin->id,
                    'title_ar' => $item['title_ar'],
                    'filters_json' => $item['filters_json'],
                    'visibility' => 'global',
                    'is_default' => false,
                ],
            );
            $created += (int) $preset->wasRecentlyCreated;
        }

        return $created;
    }
}
