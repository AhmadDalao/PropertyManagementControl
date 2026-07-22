<?php

namespace Database\Seeders;

use App\Models\Asset;
use App\Models\Document;
use App\Models\ExpenseEntry;
use App\Models\Lease;
use App\Models\MaintenanceRequest;
use App\Models\MediaFile;
use App\Models\Payment;
use App\Models\Portfolio;
use App\Models\TenantProfile;
use App\Models\User;
use App\Modules\Documents\Support\DocumentOptions;
use App\Modules\Leases\Actions\InstallmentSchedule;
use App\Modules\Payments\Actions\PaymentAllocator;
use App\Modules\PublicSite\Actions\SeedLandingContent;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DemoDataSeeder extends Seeder
{
    private InstallmentSchedule $installments;

    private PaymentAllocator $payments;

    public function run(): void
    {
        if (app()->environment('production')) {
            return;
        }

        $this->installments = app(InstallmentSchedule::class);
        $this->payments = app(PaymentAllocator::class);

        DB::transaction(function (): void {
            $superadmin = $this->user('System Owner', 'superadmin@propertycontrol.test', 'superadmin', null, '+966500000001');

            $riyadhOwner = $this->user('Ahmad Owner', 'owner@propertycontrol.test', 'owner', null, '+966500000002');
            $riyadhPortfolio = $this->portfolio($riyadhOwner, [
                'name_en' => 'Ahmad Prime Portfolio',
                'name_ar' => 'محفظة أحمد الرئيسية',
                'code' => 'AHMAD01',
                'slug' => 'ahmad-prime-portfolio',
                'city' => 'Riyadh',
                'address' => 'King Fahd Road, Riyadh',
            ]);
            $riyadhOwner->update(['portfolio_id' => $riyadhPortfolio->id]);

            $jeddahOwner = $this->user('Lina Owner', 'owner.jeddah@propertycontrol.test', 'owner', null, '+966500000012');
            $jeddahPortfolio = $this->portfolio($jeddahOwner, [
                'name_en' => 'Jeddah Coast Assets',
                'name_ar' => 'أصول جدة الساحلية',
                'code' => 'JEDCOAST',
                'slug' => 'jeddah-coast-assets',
                'city' => 'Jeddah',
                'address' => 'Prince Sultan Road, Jeddah',
            ]);
            $jeddahOwner->update(['portfolio_id' => $jeddahPortfolio->id]);

            $this->seedPortfolioCycle($riyadhPortfolio, $riyadhOwner, [
                'manager' => ['Mona Manager', 'manager@propertycontrol.test', '+966500000003'],
                'tenant' => ['Sara Tenant', 'tenant@propertycontrol.test', '+966500000004'],
                'building' => ['Rose Tower', 'برج روز', 'ROSE', 'Olaya Street, Riyadh', 7200000],
                'commercial' => true,
            ]);

            $this->seedPortfolioCycle($jeddahPortfolio, $jeddahOwner, [
                'manager' => ['Yousef Manager', 'manager.jeddah@propertycontrol.test', '+966500000013'],
                'tenant' => ['Nora Tenant', 'tenant.jeddah@propertycontrol.test', '+966500000014'],
                'building' => ['Coral Residence', 'سكن كورال', 'CORAL', 'Al Shati District, Jeddah', 5600000],
                'commercial' => false,
            ]);

            $this->seedCmsAndMedia($superadmin);
        });
    }

    /**
     * @param  array{name_en?:string,name_ar?:string,code?:string,slug?:string,city?:string,address?:string}  $attributes
     */
    private function portfolio(User $owner, array $attributes): Portfolio
    {
        return Portfolio::query()->create([
            'owner_user_id' => $owner->id,
            'name_en' => $attributes['name_en'],
            'name_ar' => $attributes['name_ar'],
            'code' => $attributes['code'],
            'slug' => $attributes['slug'],
            'status' => 'active',
            'contact_email' => $owner->email,
            'contact_phone' => $owner->phone,
            'city' => $attributes['city'],
            'country' => 'Saudi Arabia',
            'address' => $attributes['address'],
            'default_currency' => 'SAR',
            'module_settings' => [
                'assets' => true,
                'tenants' => true,
                'leases' => true,
                'payments' => true,
                'maintenance' => true,
                'documents' => true,
            ],
        ]);
    }

    /**
     * @param  array{manager:array{string,string,string},tenant:array{string,string,string},building:array{string,string,string,string,int},commercial:bool}  $config
     */
    private function seedPortfolioCycle(Portfolio $portfolio, User $owner, array $config): void
    {
        [$managerName, $managerEmail, $managerPhone] = $config['manager'];
        [$tenantName, $tenantEmail, $tenantPhone] = $config['tenant'];
        [$buildingName, $buildingNameAr, $prefix, $address, $buildingValue] = $config['building'];

        $manager = $this->user($managerName, $managerEmail, 'property_manager', $portfolio, $managerPhone, 'ar');
        $tenantUser = $this->user($tenantName, $tenantEmail, 'tenant', $portfolio, $tenantPhone);

        $tenant = TenantProfile::query()->create([
            'portfolio_id' => $portfolio->id,
            'user_id' => $tenantUser->id,
            'profile_type' => 'individual',
            'national_id' => (string) random_int(1000000000, 9999999999),
            'emergency_contact_name' => 'Emergency Contact',
            'emergency_contact_phone' => '+966500000099',
            'address' => $address,
            'status' => 'active',
            'notes' => 'Local demo tenant with active portal history.',
        ]);

        $building = $this->asset($portfolio, null, [
            'asset_type' => 'building',
            'usage_type' => $config['commercial'] ? 'mixed' : 'residential',
            'title_en' => $buildingName,
            'title_ar' => $buildingNameAr,
            'code' => "{$prefix}-TOWER",
            'address' => $address,
            'valuation_amount' => $buildingValue,
            'occupancy_status' => 'occupied',
            'rentable' => false,
            'map_zone' => $prefix === 'ROSE' ? 'Riyadh North' : 'Jeddah Coast',
            'land_number' => $prefix === 'ROSE' ? 'RN-742' : 'JC-318',
            'latitude' => $prefix === 'ROSE' ? 24.7136 : 21.5433,
            'longitude' => $prefix === 'ROSE' ? 46.6753 : 39.1728,
            'map_x' => $prefix === 'ROSE' ? 34 : 66,
            'map_y' => $prefix === 'ROSE' ? 38 : 54,
        ]);

        $floorOne = $this->asset($portfolio, $building, [
            'asset_type' => 'floor',
            'usage_type' => 'residential',
            'title_en' => 'First Floor',
            'title_ar' => 'الدور الأول',
            'code' => "{$prefix}-F1",
            'valuation_amount' => 1450000,
            'level_label' => '1',
            'occupancy_status' => 'occupied',
            'rentable' => false,
            'sort_order' => 1,
        ]);

        $floorTwo = $this->asset($portfolio, $building, [
            'asset_type' => 'floor',
            'usage_type' => 'residential',
            'title_en' => 'Second Floor',
            'title_ar' => 'الدور الثاني',
            'code' => "{$prefix}-F2",
            'valuation_amount' => 1380000,
            'level_label' => '2',
            'occupancy_status' => 'partially_occupied',
            'rentable' => false,
            'sort_order' => 2,
        ]);

        $unitA = $this->asset($portfolio, $floorOne, [
            'asset_type' => 'unit',
            'usage_type' => 'residential',
            'title_en' => 'Apartment 101',
            'title_ar' => 'شقة 101',
            'code' => "{$prefix}-101",
            'valuation_amount' => 420000,
            'area' => 165,
            'unit_label' => '101',
            'occupancy_status' => 'occupied',
            'rentable' => true,
            'sort_order' => 1,
        ]);

        $unitB = $this->asset($portfolio, $floorOne, [
            'asset_type' => 'unit',
            'usage_type' => 'residential',
            'title_en' => 'Apartment 102',
            'title_ar' => 'شقة 102',
            'code' => "{$prefix}-102",
            'valuation_amount' => 395000,
            'area' => 150,
            'unit_label' => '102',
            'occupancy_status' => 'vacant',
            'rentable' => true,
            'sort_order' => 2,
        ]);

        $this->asset($portfolio, $floorTwo, [
            'asset_type' => 'unit',
            'usage_type' => 'residential',
            'title_en' => 'Apartment 201',
            'title_ar' => 'شقة 201',
            'code' => "{$prefix}-201",
            'valuation_amount' => 440000,
            'area' => 172,
            'unit_label' => '201',
            'occupancy_status' => 'maintenance',
            'rentable' => true,
            'sort_order' => 1,
        ]);

        if ($config['commercial']) {
            $this->asset($portfolio, $building, [
                'asset_type' => 'space',
                'usage_type' => 'commercial',
                'title_en' => 'Ground Retail Space',
                'title_ar' => 'مساحة تجارية أرضية',
                'code' => "{$prefix}-SHOP-1",
                'valuation_amount' => 860000,
                'area' => 240,
                'unit_label' => 'SHOP-1',
                'occupancy_status' => 'vacant',
                'rentable' => true,
                'sort_order' => 0,
            ]);
        }

        $building->stakeholders()->createMany([
            [
                'portfolio_id' => $portfolio->id,
                'user_id' => $owner->id,
                'relationship_type' => 'owner',
                'is_primary' => true,
                'starts_on' => now()->subMonths(8)->toDateString(),
            ],
            [
                'portfolio_id' => $portfolio->id,
                'user_id' => $manager->id,
                'relationship_type' => 'manager',
                'is_primary' => true,
                'starts_on' => now()->subMonths(7)->toDateString(),
            ],
        ]);

        $activeLease = $this->lease($portfolio, $tenant, $unitA, $manager, [
            'code' => "{$prefix}-LEASE-001",
            'status' => 'active',
            'started_at' => now()->startOfMonth()->subMonths(2),
            'ends_at' => now()->startOfMonth()->addMonthsNoOverflow(9)->endOfMonth(),
            'signed_at' => now()->startOfMonth()->subMonths(2),
            'rent_amount' => $config['commercial'] ? 4500 : 3900,
            'deposit_amount' => $config['commercial'] ? 4500 : 3900,
            'notes' => 'Active demo lease with generated installments.',
        ]);

        $this->payment($portfolio, $activeLease, $tenant, $manager, "{$prefix}-PAY-001", now()->startOfMonth()->subMonths(2), (float) $activeLease->rent_amount + (float) $activeLease->deposit_amount, 'First rent and deposit.');
        $this->payment($portfolio, $activeLease, $tenant, $manager, "{$prefix}-PAY-002", now()->startOfMonth()->subMonth(), (float) $activeLease->rent_amount, 'Second month rent.');

        $this->lease($portfolio, $tenant, $unitB, $manager, [
            'code' => "{$prefix}-LEASE-OLD",
            'status' => 'expired',
            'started_at' => now()->startOfMonth()->subMonths(15),
            'ends_at' => now()->startOfMonth()->subMonths(4)->endOfMonth(),
            'signed_at' => now()->startOfMonth()->subMonths(15),
            'rent_amount' => 3200,
            'deposit_amount' => 3200,
            'notes' => 'Historical lease used for report history.',
        ], false);

        $this->maintenance($portfolio, $unitA, $activeLease, $tenant, $tenantUser, $manager, [
            'title' => 'Living room light issue',
            'description' => 'The living room lights flicker intermittently.',
            'category' => 'electricity',
            'priority' => 'medium',
            'status' => 'open',
            'requested_at' => now()->subDays(2),
        ]);

        $this->maintenance($portfolio, $unitA, $activeLease, $tenant, $tenantUser, $manager, [
            'title' => 'Kitchen sink leak',
            'description' => 'Small leak under the kitchen sink cabinet.',
            'category' => 'plumbing',
            'priority' => 'high',
            'status' => 'in_progress',
            'requested_at' => now()->subDays(6),
        ]);

        ExpenseEntry::query()->create([
            'portfolio_id' => $portfolio->id,
            'asset_id' => $unitA->id,
            'created_by_user_id' => $manager->id,
            'category' => 'maintenance',
            'title' => 'Electrical inspection',
            'description' => 'Local demo maintenance expense tied to open request.',
            'incurred_on' => now()->subDay(),
            'amount' => 350,
            'currency' => 'SAR',
            'vendor_name' => 'Quick Fix LLC',
            'status' => 'posted',
        ]);

        ExpenseEntry::query()->create([
            'portfolio_id' => $portfolio->id,
            'asset_id' => $building->id,
            'created_by_user_id' => $manager->id,
            'category' => 'utilities',
            'title' => 'Shared water bill',
            'description' => 'Monthly shared utilities.',
            'incurred_on' => now()->subDays(8),
            'amount' => 1280,
            'currency' => 'SAR',
            'vendor_name' => 'Utility Provider',
            'status' => 'posted',
        ]);

        $this->document($portfolio, $activeLease, $manager, 'lease_contract', "Lease contract {$activeLease->code}", "عقد الإيجار {$activeLease->code}");
        $this->document($portfolio, $activeLease, $manager, 'signed_contract', "Signed contract {$activeLease->code}", "العقد الموقع {$activeLease->code}");
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function asset(Portfolio $portfolio, ?Asset $parent, array $attributes): Asset
    {
        return Asset::query()->create([
            'portfolio_id' => $portfolio->id,
            'parent_id' => $parent?->id,
            'asset_type' => $attributes['asset_type'],
            'usage_type' => $attributes['usage_type'],
            'title_en' => $attributes['title_en'],
            'title_ar' => $attributes['title_ar'],
            'code' => $attributes['code'],
            'slug' => Str::slug($attributes['title_en']).'-'.Str::lower(Str::random(4)),
            'status' => 'active',
            'occupancy_status' => $attributes['occupancy_status'] ?? 'vacant',
            'rentable' => (bool) ($attributes['rentable'] ?? false),
            'valuation_amount' => $attributes['valuation_amount'] ?? 0,
            'currency' => 'SAR',
            'area' => $attributes['area'] ?? null,
            'sort_order' => $attributes['sort_order'] ?? 0,
            'level_label' => $attributes['level_label'] ?? null,
            'unit_label' => $attributes['unit_label'] ?? null,
            'address' => $attributes['address'] ?? $parent?->address,
            'description_en' => 'Local demo asset for understanding ownership, occupancy, rent, and maintenance.',
            'description_ar' => 'أصل تجريبي محلي لفهم الملكية والإشغال والإيجار والصيانة.',
            'meta_json' => $this->assetMeta($attributes),
        ]);
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
     * @param  array<string, mixed>  $attributes
     */
    private function lease(Portfolio $portfolio, TenantProfile $tenant, Asset $asset, User $manager, array $attributes, bool $syncInstallments = true): Lease
    {
        $lease = Lease::query()->create([
            'portfolio_id' => $portfolio->id,
            'tenant_profile_id' => $tenant->id,
            'managed_by_user_id' => $manager->id,
            'leaseable_type' => Asset::class,
            'leaseable_id' => $asset->id,
            'code' => $attributes['code'],
            'status' => $attributes['status'],
            'payment_frequency' => 'monthly',
            'started_at' => $attributes['started_at'],
            'ends_at' => $attributes['ends_at'],
            'signed_at' => $attributes['signed_at'],
            'rent_amount' => $attributes['rent_amount'],
            'deposit_amount' => $attributes['deposit_amount'],
            'tax_amount' => 0,
            'discount_amount' => 0,
            'currency' => 'SAR',
            'billing_day' => 1,
            'notes' => $attributes['notes'] ?? null,
            'terms_json' => [
                'utilities' => 'Tenant pays electricity. Owner covers common area service.',
                'notice' => 'Thirty-day renewal notice.',
            ],
        ]);

        if ($syncInstallments) {
            $this->installments->sync($lease);
        }

        return $lease->fresh(['installments']);
    }

    private function payment(Portfolio $portfolio, Lease $lease, TenantProfile $tenant, User $manager, string $reference, mixed $receivedOn, float $amount, string $notes): void
    {
        $payment = Payment::query()->create([
            'portfolio_id' => $portfolio->id,
            'lease_id' => $lease->id,
            'tenant_profile_id' => $tenant->id,
            'recorded_by_user_id' => $manager->id,
            'reference' => $reference,
            'type' => 'rent',
            'method' => 'bank_transfer',
            'status' => 'posted',
            'received_on' => $receivedOn,
            'amount' => $amount,
            'currency' => 'SAR',
            'notes' => $notes,
        ]);

        $this->payments->allocate($payment);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function maintenance(Portfolio $portfolio, Asset $asset, Lease $lease, TenantProfile $tenant, User $tenantUser, User $manager, array $attributes): void
    {
        $request = MaintenanceRequest::query()->create([
            'portfolio_id' => $portfolio->id,
            'asset_id' => $asset->id,
            'lease_id' => $lease->id,
            'tenant_profile_id' => $tenant->id,
            'submitted_by_user_id' => $tenantUser->id,
            'assigned_to_user_id' => $manager->id,
            'category' => $attributes['category'],
            'priority' => $attributes['priority'],
            'status' => $attributes['status'],
            'title' => $attributes['title'],
            'description' => $attributes['description'],
            'requested_at' => $attributes['requested_at'],
        ]);

        $request->updates()->create([
            'user_id' => $manager->id,
            'status_to' => $attributes['status'],
            'is_public_comment' => true,
            'comment' => 'Demo request is visible in the tenant and owner dashboards.',
        ]);
    }

    private function document(Portfolio $portfolio, Lease $lease, User $uploader, string $type, string $titleEn, string $titleAr): void
    {
        $path = "demo/documents/{$lease->code}-{$type}.pdf";
        $content = $this->fakePdfContent($titleEn, $lease->code);
        Storage::disk('local')->put($path, $content);

        Document::query()->create([
            'portfolio_id' => $portfolio->id,
            'uploaded_by_user_id' => $uploader->id,
            'documentable_type' => $lease->getMorphClass(),
            'documentable_id' => $lease->id,
            'type' => $type,
            'title_en' => $titleEn,
            'title_ar' => $titleAr,
            'disk' => 'local',
            'file_path' => $path,
            'original_name' => basename($path),
            'mime_type' => 'application/pdf',
            'file_size' => strlen($content),
            'is_public' => DocumentOptions::canShowInPortal('lease', $type),
        ]);
    }

    private function fakePdfContent(string $title, string $leaseCode): string
    {
        return "%PDF-1.4\n1 0 obj<<>>endobj\n2 0 obj<</Length 84>>stream\nDemo PDF placeholder: {$title}\nLease: {$leaseCode}\nLocal demo data only.\nendstream\nendobj\ntrailer<</Root 1 0 R>>\n%%EOF\n";
    }

    private function seedCmsAndMedia(User $superadmin): void
    {
        app(SeedLandingContent::class)->handle();

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="1200" height="720"><rect width="1200" height="720" fill="#fff7ed"/><circle cx="980" cy="110" r="190" fill="#0f766e" opacity=".16"/><circle cx="180" cy="620" r="220" fill="#f97316" opacity=".2"/><text x="90" y="360" font-family="Arial" font-size="64" font-weight="700" fill="#172033">Property Control</text></svg>';
        Storage::disk('public')->put('demo/property-control-hero.svg', $svg);

        MediaFile::query()->create([
            'uploaded_by_user_id' => $superadmin->id,
            'portfolio_id' => null,
            'collection' => 'landing',
            'disk' => 'public',
            'path' => 'demo/property-control-hero.svg',
            'mime_type' => 'image/svg+xml',
            'size' => strlen($svg),
            'width' => 1200,
            'height' => 720,
            'title_en' => 'Property control hero graphic',
            'title_ar' => 'صورة واجهة إدارة العقارات',
            'alt_text_en' => 'Abstract property operations dashboard',
            'alt_text_ar' => 'رسم تجريدي للوحة إدارة العقارات',
            'visibility' => 'public',
        ]);
    }

    private function user(string $name, string $email, string $role, ?Portfolio $portfolio, string $phone, string $locale = 'en'): User
    {
        $user = User::query()->create([
            'portfolio_id' => $portfolio?->id,
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'preferred_locale' => $locale,
            'status' => 'active',
            'force_password_reset' => false,
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
        ]);

        $user->assignRole($role);

        return $user;
    }
}
