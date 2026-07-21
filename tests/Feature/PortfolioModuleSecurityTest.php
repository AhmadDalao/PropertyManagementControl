<?php

namespace Tests\Feature;

use App\Models\MaintenanceRequest;
use App\Models\Portfolio;
use App\Support\PortfolioModules;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PortfolioModuleSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_manager_portfolio_detail_only_exposes_scoped_people_and_bounded_records(): void
    {
        $portfolio = $this->createPortfolio(['code' => 'PRIVATE-PORT']);
        $owner = $this->createUserWithRole('owner', $portfolio, ['name' => 'Hidden Owner']);
        $manager = $this->createUserWithRole('property_manager', $portfolio, ['name' => 'Current Manager']);
        $peer = $this->createUserWithRole('property_manager', $portfolio, ['name' => 'Hidden Peer']);

        foreach (range(1, 12) as $number) {
            $this->createUserWithRole('tenant', $portfolio, ['name' => "Visible Tenant {$number}"]);
            $this->createAsset($portfolio, ['code' => "PORT-ASSET-{$number}"]);
        }

        $response = $this->actingAs($manager)->get(route('portfolios.show', $portfolio));

        $response->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('admin/resource-show')
            ->where('detailPage.stats.1.value', 13)
            ->has('detailPage.related.0.rows', 8)
            ->has('detailPage.related.1.rows', 8)
            ->where('detailPage.related.1.rows', function ($rows) use ($owner, $peer): bool {
                $serialized = json_encode($rows);

                return is_string($serialized)
                    && ! str_contains($serialized, $owner->name)
                    && ! str_contains($serialized, $owner->email)
                    && ! str_contains($serialized, $peer->name)
                    && ! str_contains($serialized, $peer->email);
            })
            ->where('detailPage.sections.1.items', function ($items): bool {
                $owner = collect($items)->firstWhere('label', trans('app.portfolios.owner'));

                return is_array($owner)
                    && ($owner['href'] ?? null) === null;
            }));
    }

    public function test_owner_cannot_archive_or_reactivate_archived_portfolio_through_edit(): void
    {
        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);

        $this->actingAs($owner)
            ->put(route('portfolios.update', $portfolio), $this->portfolioPayload($portfolio, [
                'status' => 'archived',
            ]))
            ->assertSessionHasErrors('status');

        $portfolio->update(['status' => 'archived']);

        $this->actingAs($owner)
            ->put(route('portfolios.update', $portfolio), $this->portfolioPayload($portfolio, [
                'status' => 'active',
            ]))
            ->assertSessionHasErrors('status');

        $this->assertSame('archived', $portfolio->fresh()->status);
    }

    public function test_archiving_requires_closed_leases_and_maintenance(): void
    {
        $portfolio = $this->createPortfolio();
        $superadmin = $this->createUserWithRole('superadmin');
        $owner = $this->createUserWithRole('owner', $portfolio);
        $tenantUser = $this->createUserWithRole('tenant', $portfolio);
        $tenant = $this->createTenantProfile($portfolio, $tenantUser);
        $asset = $this->createAsset($portfolio);
        $lease = $this->createLease($portfolio, $tenant, $asset, $owner);

        $this->actingAs($superadmin)
            ->delete(route('portfolios.destroy', $portfolio))
            ->assertRedirect()
            ->assertSessionHas('error', trans('app.errors.portfolio_has_active_leases'));

        $lease->update(['status' => 'terminated']);
        $request = MaintenanceRequest::query()->create([
            'portfolio_id' => $portfolio->id,
            'asset_id' => $asset->id,
            'tenant_profile_id' => $tenant->id,
            'submitted_by_user_id' => $tenantUser->id,
            'category' => 'general',
            'priority' => 'medium',
            'status' => 'open',
            'title' => 'Close this work first',
            'description' => 'Archive safety check.',
            'requested_at' => now(),
        ]);

        $this->actingAs($superadmin)
            ->put(route('portfolios.update', $portfolio), $this->portfolioPayload($portfolio, [
                'status' => 'archived',
            ]))
            ->assertSessionHasErrors('status');

        $request->update(['status' => 'resolved', 'resolved_at' => now()]);

        $this->actingAs($superadmin)
            ->delete(route('portfolios.destroy', $portfolio))
            ->assertRedirect(route('portfolios.index'));

        $this->assertSame('archived', $portfolio->fresh()->status);
    }

    public function test_create_normalizes_identifiers_and_form_exposes_all_module_switches(): void
    {
        $superadmin = $this->createUserWithRole('superadmin');

        $this->actingAs($superadmin)
            ->withSession(['locale' => 'ar'])
            ->get(route('portfolios.create'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('formPage.title', 'إنشاء محفظة')
                ->where('formPage.fields', fn ($fields): bool => collect($fields)
                    ->where('type', 'checkbox')
                    ->count() === count(PortfolioModules::defaults())));

        $this->actingAs($superadmin)
            ->post(route('portfolios.store'), [
                'name_en' => '  Modular Account  ',
                'name_ar' => '  حساب معياري  ',
                'code' => ' client-one ',
                'default_currency' => 'usd',
                'status' => 'active',
                'module_settings' => ['assets' => true, 'unknown' => true],
            ])
            ->assertSessionHasErrors('module_settings');

        $this->actingAs($superadmin)
            ->post(route('portfolios.store'), [
                'name_en' => '  Modular Account  ',
                'name_ar' => '  حساب معياري  ',
                'code' => ' client-one ',
                'default_currency' => 'usd',
                'status' => 'active',
                'module_payments' => false,
                'module_expenses' => false,
            ])
            ->assertRedirect();

        $portfolio = Portfolio::query()->where('code', 'CLIENT-ONE')->firstOrFail();
        $this->assertSame('Modular Account', $portfolio->name_en);
        $this->assertSame('حساب معياري', $portfolio->name_ar);
        $this->assertSame('USD', $portfolio->default_currency);
        $this->assertFalse($portfolio->module_settings['payments']);
        $this->assertFalse($portfolio->module_settings['expenses']);
        $this->assertTrue($portfolio->module_settings['assets']);
    }

    public function test_owner_form_module_fields_update_settings_without_dropping_other_modules(): void
    {
        $portfolio = $this->createPortfolio([
            'module_settings' => PortfolioModules::defaults(),
        ]);
        $owner = $this->createUserWithRole('owner', $portfolio);

        $payload = $this->portfolioPayload($portfolio);

        foreach (array_keys(PortfolioModules::defaults()) as $module) {
            $payload["module_{$module}"] = ! in_array($module, ['payments', 'media'], true);
        }

        $this->actingAs($owner)
            ->put(route('portfolios.update', $portfolio), $payload)
            ->assertRedirect(route('portfolios.show', $portfolio));

        $settings = $portfolio->fresh()->module_settings;
        $this->assertCount(count(PortfolioModules::defaults()), $settings);
        $this->assertFalse($settings['payments']);
        $this->assertFalse($settings['media']);
        $this->assertTrue($settings['assets']);
        $this->assertTrue($settings['reports']);

        $this->actingAs($owner)
            ->put(route('portfolios.update', $portfolio), [
                ...$this->portfolioPayload($portfolio),
                'module_settings' => ['payments' => true],
            ])
            ->assertRedirect(route('portfolios.show', $portfolio));

        $settings = $portfolio->fresh()->module_settings;
        $this->assertTrue($settings['payments']);
        $this->assertFalse($settings['media']);
    }

    public function test_portfolio_detail_hides_disabled_module_records_but_keeps_superadmin_oversight(): void
    {
        $portfolio = $this->createPortfolio([
            'module_settings' => [
                ...PortfolioModules::defaults(),
                'users' => false,
                'assets' => false,
                'leases' => false,
                'maintenance' => false,
                'documents' => false,
            ],
        ]);
        $owner = $this->createUserWithRole('owner', $portfolio);
        $tenantUser = $this->createUserWithRole('tenant', $portfolio);
        $tenant = $this->createTenantProfile($portfolio, $tenantUser);
        $asset = $this->createAsset($portfolio);
        $lease = $this->createLease($portfolio, $tenant, $asset, $owner);
        $superadmin = $this->createUserWithRole('superadmin');

        MaintenanceRequest::query()->create([
            'portfolio_id' => $portfolio->id,
            'asset_id' => $asset->id,
            'lease_id' => $lease->id,
            'tenant_profile_id' => $tenant->id,
            'submitted_by_user_id' => $tenantUser->id,
            'category' => 'general',
            'priority' => 'medium',
            'status' => 'open',
            'title' => 'Hidden module request',
            'description' => 'Only platform oversight should show this.',
            'requested_at' => now(),
        ]);

        $this->actingAs($owner)
            ->get(route('portfolios.show', $portfolio))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('detailPage.related.0.rows', 0)
                ->has('detailPage.related.1.rows', 0)
                ->has('detailPage.related.2.rows', 0)
                ->has('detailPage.related.3.rows', 0)
                ->where('detailPage.related.0.actionHref', null)
                ->where('detailPage.related.1.actionHref', null));

        $this->actingAs($superadmin)
            ->get(route('portfolios.show', $portfolio))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('detailPage.related.0.rows', 1)
                ->has('detailPage.related.1.rows', 2)
                ->has('detailPage.related.2.rows', 1)
                ->has('detailPage.related.3.rows', 1));
    }

    public function test_platform_metrics_do_not_add_unrelated_currencies(): void
    {
        $sar = $this->createPortfolio(['code' => 'SAR-PORT', 'default_currency' => 'SAR']);
        $usd = $this->createPortfolio(['code' => 'USD-PORT', 'default_currency' => 'USD']);
        $superadmin = $this->createUserWithRole('superadmin');

        $this->createAsset($sar, ['valuation_amount' => 500000, 'currency' => 'SAR']);
        $this->createAsset($usd, ['valuation_amount' => 100000, 'currency' => 'USD']);

        $this->actingAs($superadmin)
            ->get(route('portfolios.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('portfolioInsights.currency_count', 2)
                ->where('portfolioInsights.valuation_total', null)
                ->where('portfolioInsights.posted_revenue_total', null)
                ->where('portfolioInsights.posted_expense_total', null)
                ->where('portfolioInsights.net_total', null));
    }

    public function test_portfolio_export_and_exact_search_share_the_scoped_query(): void
    {
        $portfolio = $this->createPortfolio([
            'name_en' => 'Scoped Export Account',
            'name_ar' => 'حساب التصدير المحدد',
            'code' => 'PORT-EXACT',
        ]);
        $foreign = $this->createPortfolio([
            'name_en' => 'Foreign Export Account',
            'code' => 'PORT-FOREIGN',
        ]);
        $owner = $this->createUserWithRole('owner', $portfolio);
        $superadmin = $this->createUserWithRole('superadmin');

        $export = $this->actingAs($owner)
            ->withSession(['locale' => 'ar'])
            ->get(route('exports.resource', ['resource' => 'portfolios']));
        $export->assertOk();
        $sheet = $this->xlsxWorksheetXml($export);
        $this->assertStringContainsString('PORT-EXACT', $sheet);
        $this->assertStringContainsString('حساب التصدير المحدد', $sheet);
        $this->assertStringNotContainsString('PORT-FOREIGN', $sheet);
        $this->assertStringNotContainsString($foreign->name_en, $sheet);

        $this->actingAs($owner)
            ->getJson(route('global-search', ['q' => 'PORT-EXACT']))
            ->assertOk()
            ->assertJsonPath('direct_url', route('portfolios.show', $portfolio))
            ->assertJsonFragment(['subtitle' => 'PORT-EXACT']);

        $this->actingAs($owner)
            ->getJson(route('global-search', ['q' => 'PORT-FOREIGN']))
            ->assertOk()
            ->assertJsonPath('direct_url', '')
            ->assertJsonMissing(['subtitle' => 'PORT-FOREIGN']);

        $this->actingAs($superadmin)
            ->getJson(route('global-search', ['q' => 'PORT-EXACT']))
            ->assertOk()
            ->assertJsonPath('direct_url', route('portfolios.show', $portfolio));
    }

    public function test_owner_detail_uses_profile_link_and_resolves_arabic_copy(): void
    {
        $portfolio = $this->createPortfolio([
            'name_en' => 'Arabic Detail Account',
            'name_ar' => 'حساب التفاصيل العربية',
            'code' => 'AR-DETAIL',
        ]);
        $owner = $this->createUserWithRole('owner', $portfolio, ['name' => 'Portfolio Owner']);
        $portfolio->update(['owner_user_id' => $owner->id]);

        $this->actingAs($owner)
            ->withSession(['locale' => 'ar'])
            ->get(route('portfolios.show', $portfolio))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('app.locale', 'ar')
                ->where('detailPage.header.eyebrow', 'حساب المحفظة')
                ->where('detailPage.header.title', 'حساب التفاصيل العربية')
                ->where('detailPage.sections.0.title', 'ملف النشاط')
                ->where('detailPage.sections.1.items', function ($items): bool {
                    $owner = collect($items)->firstWhere('label', 'المالك');

                    return is_array($owner)
                        && ($owner['href'] ?? null) === route('profile.index');
                }));
    }

    /** @param array<string, mixed> $overrides */
    private function portfolioPayload(Portfolio $portfolio, array $overrides = []): array
    {
        return array_merge([
            'name_en' => $portfolio->name_en,
            'name_ar' => $portfolio->name_ar,
            'contact_email' => $portfolio->contact_email,
            'contact_phone' => $portfolio->contact_phone,
            'city' => $portfolio->city,
            'country' => $portfolio->country,
            'address' => $portfolio->address,
            'address_ar' => $portfolio->address_ar,
            'default_currency' => $portfolio->default_currency,
            'status' => $portfolio->status,
            'module_settings' => PortfolioModules::normalize($portfolio->module_settings),
        ], $overrides);
    }
}
