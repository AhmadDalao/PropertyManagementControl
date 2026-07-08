<?php

namespace Database\Seeders;

use App\Models\Asset;
use App\Models\CmsPage;
use App\Models\CmsSection;
use App\Models\ExpenseEntry;
use App\Models\Lease;
use App\Models\MaintenanceRequest;
use App\Models\NavigationItem;
use App\Models\Payment;
use App\Models\Portfolio;
use App\Models\TenantProfile;
use App\Models\User;
use App\Services\LeaseFinancialService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $superadmin = User::query()->create([
            'name' => 'System Owner',
            'email' => 'superadmin@propertycontrol.test',
            'phone' => '+966500000001',
            'preferred_locale' => 'en',
            'status' => 'active',
            'force_password_reset' => false,
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
        ]);
        $superadmin->assignRole('superadmin');

        $owner = User::query()->create([
            'name' => 'Ahmad Owner',
            'email' => 'owner@propertycontrol.test',
            'phone' => '+966500000002',
            'preferred_locale' => 'en',
            'status' => 'active',
            'force_password_reset' => false,
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
        ]);

        $portfolio = Portfolio::query()->create([
            'owner_user_id' => $owner->id,
            'name_en' => 'Ahmad Prime Portfolio',
            'name_ar' => 'محفظة أحمد الرئيسية',
            'code' => 'AHMAD01',
            'slug' => 'ahmad-prime-portfolio',
            'status' => 'active',
            'contact_email' => 'owner@propertycontrol.test',
            'contact_phone' => '+966500000002',
            'city' => 'Riyadh',
            'country' => 'Saudi Arabia',
            'address' => 'King Fahd Road, Riyadh',
            'default_currency' => 'SAR',
            'module_settings' => [
                'assets' => true,
                'tenants' => true,
                'leases' => true,
                'payments' => true,
                'maintenance' => true,
            ],
        ]);

        $owner->update(['portfolio_id' => $portfolio->id]);
        $owner->assignRole('owner');

        $manager = User::query()->create([
            'portfolio_id' => $portfolio->id,
            'name' => 'Mona Manager',
            'email' => 'manager@propertycontrol.test',
            'phone' => '+966500000003',
            'preferred_locale' => 'ar',
            'status' => 'active',
            'force_password_reset' => false,
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
        ]);
        $manager->assignRole('property_manager');

        $tenantUser = User::query()->create([
            'portfolio_id' => $portfolio->id,
            'name' => 'Sara Tenant',
            'email' => 'tenant@propertycontrol.test',
            'phone' => '+966500000004',
            'preferred_locale' => 'en',
            'status' => 'active',
            'force_password_reset' => false,
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
        ]);
        $tenantUser->assignRole('tenant');

        $tenant = TenantProfile::query()->create([
            'portfolio_id' => $portfolio->id,
            'user_id' => $tenantUser->id,
            'profile_type' => 'individual',
            'national_id' => '1234567890',
            'emergency_contact_name' => 'Emergency Contact',
            'emergency_contact_phone' => '+966500000099',
            'address' => 'Al Yasmin, Riyadh',
            'status' => 'active',
        ]);

        $building = Asset::query()->create([
            'portfolio_id' => $portfolio->id,
            'asset_type' => 'building',
            'usage_type' => 'residential',
            'title_en' => 'Rose Tower',
            'title_ar' => 'برج روز',
            'code' => 'ROSE-TOWER',
            'slug' => 'rose-tower',
            'status' => 'active',
            'occupancy_status' => 'occupied',
            'rentable' => false,
            'valuation_amount' => 7200000,
            'currency' => 'SAR',
            'address' => 'Olaya Street, Riyadh',
        ]);

        $floor = Asset::query()->create([
            'portfolio_id' => $portfolio->id,
            'parent_id' => $building->id,
            'asset_type' => 'floor',
            'usage_type' => 'residential',
            'title_en' => 'First Floor',
            'title_ar' => 'الدور الأول',
            'code' => 'ROSE-F1',
            'slug' => 'rose-floor-1',
            'status' => 'active',
            'occupancy_status' => 'occupied',
            'rentable' => false,
            'valuation_amount' => 1400000,
            'currency' => 'SAR',
            'level_label' => '1',
        ]);

        $unit = Asset::query()->create([
            'portfolio_id' => $portfolio->id,
            'parent_id' => $floor->id,
            'asset_type' => 'unit',
            'usage_type' => 'residential',
            'title_en' => 'Apartment 101',
            'title_ar' => 'شقة 101',
            'code' => 'ROSE-101',
            'slug' => 'rose-101',
            'status' => 'active',
            'occupancy_status' => 'occupied',
            'rentable' => true,
            'valuation_amount' => 420000,
            'currency' => 'SAR',
            'area' => 165,
            'unit_label' => '101',
        ]);

        $building->stakeholders()->createMany([
            [
                'portfolio_id' => $portfolio->id,
                'user_id' => $owner->id,
                'relationship_type' => 'owner',
                'is_primary' => true,
                'starts_on' => now()->toDateString(),
            ],
            [
                'portfolio_id' => $portfolio->id,
                'user_id' => $manager->id,
                'relationship_type' => 'manager',
                'is_primary' => true,
                'starts_on' => now()->toDateString(),
            ],
        ]);

        $lease = Lease::query()->create([
            'portfolio_id' => $portfolio->id,
            'tenant_profile_id' => $tenant->id,
            'managed_by_user_id' => $manager->id,
            'leaseable_type' => Asset::class,
            'leaseable_id' => $unit->id,
            'code' => 'LEASE-DEMO-001',
            'status' => 'active',
            'payment_frequency' => 'monthly',
            'started_at' => now()->startOfMonth(),
            'ends_at' => now()->startOfMonth()->addMonthsNoOverflow(11)->endOfMonth(),
            'signed_at' => now()->startOfMonth(),
            'rent_amount' => 4500,
            'deposit_amount' => 4500,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'currency' => 'SAR',
            'billing_day' => 1,
            'notes' => 'Demo lease for local development.',
        ]);

        app(LeaseFinancialService::class)->syncInstallments($lease);

        $payment = Payment::query()->create([
            'portfolio_id' => $portfolio->id,
            'lease_id' => $lease->id,
            'tenant_profile_id' => $tenant->id,
            'recorded_by_user_id' => $manager->id,
            'reference' => 'PAY-001',
            'type' => 'rent',
            'method' => 'bank_transfer',
            'status' => 'posted',
            'received_on' => now()->startOfMonth(),
            'amount' => 9000,
            'currency' => 'SAR',
            'notes' => 'First rent and deposit payment.',
        ]);
        app(LeaseFinancialService::class)->allocatePayment($payment);

        MaintenanceRequest::query()->create([
            'portfolio_id' => $portfolio->id,
            'asset_id' => $unit->id,
            'lease_id' => $lease->id,
            'tenant_profile_id' => $tenant->id,
            'submitted_by_user_id' => $tenantUser->id,
            'assigned_to_user_id' => $manager->id,
            'category' => 'electricity',
            'priority' => 'medium',
            'status' => 'open',
            'title' => 'Living room light issue',
            'description' => 'The living room lights flicker intermittently.',
            'requested_at' => now()->subDays(2),
        ]);

        ExpenseEntry::query()->create([
            'portfolio_id' => $portfolio->id,
            'asset_id' => $unit->id,
            'created_by_user_id' => $manager->id,
            'category' => 'maintenance',
            'title' => 'Electrical inspection',
            'description' => 'Demo maintenance expense.',
            'incurred_on' => now()->subDay(),
            'amount' => 350,
            'currency' => 'SAR',
            'vendor_name' => 'Quick Fix LLC',
            'status' => 'posted',
        ]);

        $homePage = CmsPage::query()->create([
            'slug' => 'home',
            'title_en' => 'Modern property control',
            'title_ar' => 'إدارة عقارات حديثة',
            'excerpt_en' => 'Track buildings, units, tenants, revenue, and maintenance from one place.',
            'excerpt_ar' => 'تابع المباني والوحدات والمستأجرين والإيرادات والصيانة من مكان واحد.',
            'seo_title_en' => 'Property Management Control',
            'seo_title_ar' => 'نظام إدارة العقارات',
            'seo_description_en' => 'Bilingual property operations platform built on Laravel and React.',
            'seo_description_ar' => 'منصة ثنائية اللغة لإدارة العقارات مبنية على لارافيل وريأكت.',
            'status' => 'published',
            'is_homepage' => true,
            'is_visible' => true,
            'published_at' => now(),
        ]);

        $hero = CmsSection::query()->create([
            'section_type' => 'hero',
            'name_en' => 'Homepage hero',
            'name_ar' => 'واجهة الصفحة الرئيسية',
            'content_en' => [
                'headline' => 'Manage your full property operation from one dashboard.',
                'subheadline' => 'Assets, leases, payments, tenants, maintenance, and reports.',
                'ctaPrimary' => 'Access dashboard',
                'ctaSecondary' => 'View sample property',
            ],
            'content_ar' => [
                'headline' => 'أدر كل عمليات عقاراتك من لوحة واحدة.',
                'subheadline' => 'الأصول والعقود والدفعات والمستأجرون والصيانة والتقارير.',
                'ctaPrimary' => 'دخول اللوحة',
                'ctaSecondary' => 'عرض عقار تجريبي',
            ],
            'status' => 'active',
        ]);

        $metrics = CmsSection::query()->create([
            'section_type' => 'metrics',
            'name_en' => 'Metrics strip',
            'name_ar' => 'شريط الأرقام',
            'content_en' => [
                'items' => [
                    ['label' => 'Managed assets', 'value' => '128'],
                    ['label' => 'Active tenants', 'value' => '84'],
                    ['label' => 'Open requests', 'value' => '6'],
                ],
            ],
            'content_ar' => [
                'items' => [
                    ['label' => 'الأصول المُدارة', 'value' => '128'],
                    ['label' => 'المستأجرون النشطون', 'value' => '84'],
                    ['label' => 'الطلبات المفتوحة', 'value' => '6'],
                ],
            ],
            'status' => 'active',
        ]);

        $homePage->pageSections()->createMany([
            ['cms_section_id' => $hero->id, 'sort_order' => 1, 'is_visible' => true],
            ['cms_section_id' => $metrics->id, 'sort_order' => 2, 'is_visible' => true],
        ]);

        NavigationItem::query()->create([
            'location' => 'header',
            'title_en' => 'Home',
            'title_ar' => 'الرئيسية',
            'cms_page_id' => $homePage->id,
            'url' => '/',
            'sort_order' => 1,
            'is_visible' => true,
        ]);
    }
}
