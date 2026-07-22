<?php

namespace Tests\Feature;

use App\Models\ExpenseEntry;
use App\Models\MaintenanceRequest;
use App\Modules\Expenses\Actions\ManageExpenses;
use App\Modules\Expenses\Support\ExpenseOptions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ExpenseModuleSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_owner_index_export_and_direct_routes_never_leak_another_portfolio(): void
    {
        $portfolio = $this->createPortfolio();
        $foreignPortfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $visible = $this->createExpense($portfolio->id, $owner->id, [
            'title' => 'Visible scoped expense',
        ]);
        $hidden = $this->createExpense($foreignPortfolio->id, null, [
            'title' => 'Hidden scoped expense',
        ]);

        $this->actingAs($owner)
            ->get(route('expenses.index', ['search' => 'scoped expense', 'per_page' => 100]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/expenses/index')
                ->where('expenses.total', 1)
                ->where('expenses.data.0.id', $visible->id));

        $this->actingAs($owner)->get(route('expenses.show', $hidden))->assertForbidden();
        $this->actingAs($owner)->get(route('expenses.edit', $hidden))->assertForbidden();
        $this->actingAs($owner)
            ->put(route('expenses.update', $hidden), $this->expensePayload())
            ->assertForbidden();
        $this->actingAs($owner)->delete(route('expenses.destroy', $hidden))->assertForbidden();

        $export = $this->actingAs($owner)->get(route('exports.resource', [
            'resource' => 'expenses',
            'search' => 'scoped expense',
        ]));
        $export->assertOk();
        $sheet = $this->xlsxWorksheetXml($export);
        $this->assertStringContainsString('Visible scoped expense', $sheet);
        $this->assertStringNotContainsString('Hidden scoped expense', $sheet);
    }

    public function test_owner_cannot_create_an_expense_or_reference_outside_their_portfolio(): void
    {
        $portfolio = $this->createPortfolio();
        $foreignPortfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $foreignAsset = $this->createAsset($foreignPortfolio);

        $this->actingAs($owner)
            ->post(route('expenses.store'), $this->expensePayload([
                'portfolio_id' => $foreignPortfolio->id,
                'title' => 'Cross portfolio expense',
            ]))
            ->assertForbidden();

        $this->actingAs($owner)
            ->post(route('expenses.store'), $this->expensePayload([
                'asset_id' => $foreignAsset->id,
                'title' => 'Cross portfolio asset',
            ]))
            ->assertSessionHasErrors('asset_id');

        $this->assertDatabaseMissing('expense_entries', ['title' => 'Cross portfolio expense']);
        $this->assertDatabaseMissing('expense_entries', ['title' => 'Cross portfolio asset']);
    }

    public function test_superadmin_must_choose_an_active_portfolio(): void
    {
        $superadmin = $this->createUserWithRole('superadmin');
        $inactivePortfolio = $this->createPortfolio(['status' => 'archived']);

        $this->actingAs($superadmin)
            ->post(route('expenses.store'), $this->expensePayload([
                'portfolio_id' => null,
                'title' => 'Missing portfolio expense',
            ]))
            ->assertSessionHasErrors('portfolio_id');

        $this->actingAs($superadmin)
            ->post(route('expenses.store'), $this->expensePayload([
                'portfolio_id' => $inactivePortfolio->id,
                'title' => 'Inactive portfolio expense',
            ]))
            ->assertSessionHasErrors('portfolio_id');

        $this->assertDatabaseCount('expense_entries', 0);

        $this->assertValidationError(
            fn () => app(ManageExpenses::class)->create($superadmin, $this->expensePayload([
                'portfolio_id' => $inactivePortfolio->id,
            ])),
            'portfolio_id',
        );
    }

    public function test_expense_values_are_strictly_validated(): void
    {
        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);

        $this->actingAs($owner)
            ->post(route('expenses.store'), $this->expensePayload([
                'category' => 'miscellaneous_magic',
                'status' => 'void',
                'amount' => 0,
                'currency' => 'EU1',
                'incurred_on' => now()->addDay()->toDateString(),
            ]))
            ->assertSessionHasErrors(['category', 'status', 'amount', 'currency', 'incurred_on']);

        $this->assertDatabaseCount('expense_entries', 0);
    }

    public function test_maintenance_link_derives_asset_creator_and_portfolio_currency(): void
    {
        $portfolio = $this->createPortfolio(['default_currency' => 'USD']);
        $owner = $this->createUserWithRole('owner', $portfolio);
        $tenant = $this->createTenantProfile(
            $portfolio,
            $this->createUserWithRole('tenant', $portfolio),
        );
        $asset = $this->createAsset($portfolio);
        $request = MaintenanceRequest::query()->create([
            'portfolio_id' => $portfolio->id,
            'asset_id' => $asset->id,
            'tenant_profile_id' => $tenant->id,
            'submitted_by_user_id' => $tenant->user_id,
            'category' => 'electrical',
            'priority' => 'medium',
            'status' => 'open',
            'title' => 'Panel repair',
            'description' => 'Replace the damaged panel.',
            'requested_at' => now(),
        ]);

        $response = $this->actingAs($owner)
            ->post(route('expenses.store'), $this->expensePayload([
                'maintenance_request_id' => $request->id,
                'title' => 'Panel contractor',
                'currency' => 'SAR',
            ]));
        $expense = ExpenseEntry::query()->where('title', 'Panel contractor')->firstOrFail();

        $response->assertRedirect(route('expenses.show', $expense));
        $this->assertSame($asset->id, $expense->asset_id);
        $this->assertSame($request->id, $expense->maintenance_request_id);
        $this->assertSame($owner->id, $expense->created_by_user_id);
        $this->assertSame('USD', $expense->currency);
    }

    public function test_corrupt_maintenance_asset_reference_cannot_cross_portfolios(): void
    {
        $portfolio = $this->createPortfolio();
        $foreignPortfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $tenant = $this->createTenantProfile(
            $portfolio,
            $this->createUserWithRole('tenant', $portfolio),
        );
        $foreignAsset = $this->createAsset($foreignPortfolio);
        $request = MaintenanceRequest::query()->create([
            'portfolio_id' => $portfolio->id,
            'asset_id' => $foreignAsset->id,
            'tenant_profile_id' => $tenant->id,
            'submitted_by_user_id' => $owner->id,
            'category' => 'electrical',
            'priority' => 'medium',
            'status' => 'open',
            'title' => 'Legacy corrupt asset link',
            'description' => 'The guard must not inherit a foreign asset.',
            'requested_at' => now(),
        ]);

        $this->assertValidationError(
            fn () => app(ManageExpenses::class)->create($owner, $this->expensePayload([
                'maintenance_request_id' => $request->id,
            ])),
            'asset_id',
        );
        $this->assertDatabaseCount('expense_entries', 0);
    }

    public function test_direct_actions_validate_input_preserve_currency_and_keep_void_terminal(): void
    {
        $portfolio = $this->createPortfolio(['default_currency' => 'USD']);
        $owner = $this->createUserWithRole('owner', $portfolio);
        $expenses = app(ManageExpenses::class);

        $this->assertValidationError(
            fn () => $expenses->create($owner, ['portfolio_id' => $portfolio->id]),
            'category',
        );
        $this->assertValidationError(
            fn () => $expenses->create($owner, $this->expensePayload([
                'title' => ['not', 'text'],
                'amount' => ['250'],
                'status' => 'void',
            ])),
            'title',
        );
        $this->assertDatabaseCount('expense_entries', 0);

        $expense = $expenses->create($owner, $this->expensePayload([
            'title' => 'Original currency expense',
            'currency' => 'SAR',
        ]));
        $this->assertSame('USD', $expense->currency);

        $portfolio->update(['default_currency' => 'EUR']);
        $updated = $expenses->update($owner, $expense, $this->expensePayload([
            'title' => 'Updated without currency rewrite',
            'currency' => 'EUR',
        ]));
        $this->assertSame('USD', $updated->currency);

        $expenses->void($owner, $updated);
        $this->assertValidationError(
            fn () => $expenses->update($owner, $updated, $this->expensePayload([
                'status' => 'posted',
            ])),
            'status',
        );
        $this->assertDatabaseHas('expense_entries', [
            'id' => $expense->id,
            'title' => 'Updated without currency rewrite',
            'currency' => 'USD',
            'status' => 'void',
        ]);
    }

    public function test_expense_list_normalizes_filters_and_omits_internal_descriptions(): void
    {
        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $expense = $this->createExpense($portfolio->id, $owner->id, [
            'title' => 'Lean expense payload',
            'description' => 'Private invoice reconciliation detail.',
        ]);

        $this->actingAs($owner)
            ->get(route('expenses.index', [
                'search' => $expense->title,
                'status' => 'not-a-status',
                'category' => 'not-a-category',
                'date_from' => '2026-99-99',
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('expenses.total', 1)
                ->where('expenses.data.0.title', 'Lean expense payload')
                ->missing('expenses.data.0.description')
                ->missing('expenses.data.0.created_by')
                ->where('filters.status', 'all')
                ->where('filters.category', 'all')
                ->where('filters.date_from', ''));
    }

    public function test_arabic_expense_export_uses_localized_workbook_labels(): void
    {
        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $this->createExpense($portfolio->id, $owner->id, [
            'title' => 'Arabic workbook expense',
            'category' => 'maintenance',
        ]);

        $export = $this->actingAs($owner)
            ->withSession(['locale' => 'ar'])
            ->get(route('exports.resource', ['resource' => 'expenses']));

        $export->assertOk();
        $sheet = $this->xlsxWorksheetXml($export);
        $this->assertStringContainsString('عنوان المصروف', $sheet);
        $this->assertStringContainsString('الصيانة', $sheet);
        $this->assertStringNotContainsString('Property management', $sheet);
    }

    public function test_void_expenses_are_terminal_and_cannot_be_edited_or_restored(): void
    {
        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $expense = $this->createExpense($portfolio->id, $owner->id, [
            'title' => 'Terminal expense',
        ]);

        $this->actingAs($owner)
            ->delete(route('expenses.destroy', $expense))
            ->assertRedirect(route('expenses.index'));
        $this->actingAs($owner)
            ->delete(route('expenses.destroy', $expense))
            ->assertRedirect(route('expenses.index'));

        $this->assertSame('void', $expense->fresh()->status);
        $this->actingAs($owner)->get(route('expenses.edit', $expense))->assertStatus(409);
        $this->actingAs($owner)
            ->put(route('expenses.update', $expense), $this->expensePayload([
                'title' => 'Restored expense',
                'status' => 'posted',
            ]))
            ->assertSessionHasErrors('status');

        $this->assertDatabaseHas('expense_entries', [
            'id' => $expense->id,
            'title' => 'Terminal expense',
            'status' => 'void',
        ]);
        $this->actingAs($owner)
            ->get(route('expenses.show', $expense))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->has('detailPage.header.actions', 0));
    }

    public function test_superadmin_form_scopes_reference_options_and_index_drops_unused_payloads(): void
    {
        $firstPortfolio = $this->createPortfolio();
        $secondPortfolio = $this->createPortfolio();
        $superadmin = $this->createUserWithRole('superadmin');
        $firstAsset = $this->createAsset($firstPortfolio, ['title_en' => 'First scoped asset']);
        $this->createAsset($secondPortfolio, ['title_en' => 'Hidden scoped asset']);

        $this->actingAs($superadmin)
            ->get(route('expenses.create'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/resource-form')
                ->where('formPage.fields.0.reloadOnChange.queryKey', 'portfolio_id')
                ->has('formPage.fields.1.options', 1)
                ->where('formPage.initialValues.portfolio_id', ''));

        $this->actingAs($superadmin)
            ->get(route('expenses.create', ['portfolio_id' => $firstPortfolio->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('formPage.initialValues.portfolio_id', (string) $firstPortfolio->id)
                ->where('formPage.fields.1.options.1.value', $firstAsset->id)
                ->where('formPage.fields.1.options.1.label', fn (string $label) => str_contains($label, 'First scoped asset'))
                ->has('formPage.fields.1.options', 2));

        $this->actingAs($superadmin)
            ->get(route('expenses.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->missing('assetOptions')
                ->missing('maintenanceOptions')
                ->where('statusOptions.2', 'void'));
    }

    public function test_mixed_currency_scope_never_displays_a_false_combined_total(): void
    {
        $sarPortfolio = $this->createPortfolio(['default_currency' => 'SAR']);
        $usdPortfolio = $this->createPortfolio(['default_currency' => 'USD']);
        $superadmin = $this->createUserWithRole('superadmin');
        $owner = $this->createUserWithRole('owner', $sarPortfolio);
        $this->createExpense($sarPortfolio->id, $owner->id, ['amount' => 100, 'currency' => 'SAR']);
        $this->createExpense($usdPortfolio->id, null, ['amount' => 200, 'currency' => 'USD']);

        $this->actingAs($superadmin)
            ->get(route('expenses.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('expenseInsights.currency_count', 2)
                ->where('expenseInsights.currency', null)
                ->where('expenseInsights.posted_amount', null));

        $this->actingAs($owner)
            ->get(route('expenses.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('expenseInsights.currency_count', 1)
                ->where('expenseInsights.currency', 'SAR')
                ->where('expenseInsights.posted_amount', 100));
    }

    public function test_tenant_is_denied_and_arabic_forms_and_details_are_translated(): void
    {
        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $tenant = $this->createUserWithRole('tenant', $portfolio);
        $expense = $this->createExpense($portfolio->id, $owner->id);

        $this->actingAs($tenant)->get(route('expenses.index'))->assertForbidden();
        $this->actingAs($tenant)
            ->post(route('expenses.store'), $this->expensePayload())
            ->assertForbidden();

        $this->actingAs($owner)
            ->withSession(['locale' => 'ar'])
            ->get(route('expenses.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('app.direction', 'rtl')
                ->where('counts.0.label', 'الكل')
                ->where('counts.1.label', 'مرحل'));

        $this->actingAs($owner)
            ->withSession(['locale' => 'ar'])
            ->get(route('expenses.create'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('app.locale', 'ar')
                ->where('formPage.title', 'تسجيل مصروف')
                ->where('formPage.fields.0.label', 'الأصل')
                ->where('formPage.fields.2.options.0.label', 'الصيانة'));

        $this->actingAs($owner)
            ->withSession(['locale' => 'ar'])
            ->get(route('expenses.show', $expense))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('detailPage.header.eyebrow', 'سجل المصروف')
                ->where('detailPage.sections.0.title', 'سياق التكلفة')
                ->where('detailPage.sections.1.tab', 'financial'));
    }

    public function test_every_stored_category_remains_editable_and_has_arabic_wording(): void
    {
        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $expense = $this->createExpense($portfolio->id, $owner->id);

        foreach (['cleaning', 'security', 'management', 'compliance'] as $category) {
            $this->actingAs($owner)
                ->put(route('expenses.update', $expense), $this->expensePayload([
                    'category' => $category,
                    'title' => "{$category} expense",
                ]))
                ->assertRedirect(route('expenses.show', $expense));

            $this->assertDatabaseHas('expense_entries', [
                'id' => $expense->id,
                'category' => $category,
            ]);
        }

        $this->assertEmpty(array_diff(ExpenseOptions::SHOWCASE_CATEGORIES, ExpenseOptions::CATEGORIES));
        $this->actingAs($owner)
            ->withSession(['locale' => 'ar'])
            ->get(route('expenses.create'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('formPage.fields.2.options', function ($options): bool {
                    $labels = collect($options)->pluck('label', 'value');

                    return $labels->get('cleaning') === 'التنظيف'
                        && $labels->get('security') === 'الأمن'
                        && $labels->get('management') === 'إدارة العقار'
                        && $labels->get('compliance') === 'الامتثال'
                        && $labels->every(fn (string $label): bool => ! str_starts_with($label, 'app.'));
                }));
    }

    /** @param array<string, mixed> $overrides */
    private function createExpense(int $portfolioId, ?int $creatorId, array $overrides = []): ExpenseEntry
    {
        return ExpenseEntry::query()->create(array_merge([
            'portfolio_id' => $portfolioId,
            'created_by_user_id' => $creatorId,
            'category' => 'maintenance',
            'title' => 'Scoped expense',
            'description' => 'Expense module test.',
            'incurred_on' => now()->toDateString(),
            'amount' => 125,
            'currency' => 'SAR',
            'vendor_name' => 'Test Vendor',
            'status' => 'posted',
        ], $overrides));
    }

    /** @return array<string, mixed> */
    private function expensePayload(array $overrides = []): array
    {
        return array_merge([
            'asset_id' => null,
            'maintenance_request_id' => null,
            'category' => 'maintenance',
            'title' => 'Expense entry',
            'description' => 'Expense module request.',
            'incurred_on' => now()->toDateString(),
            'amount' => 250,
            'currency' => 'SAR',
            'vendor_name' => 'Expense Vendor',
            'status' => 'posted',
        ], $overrides);
    }

    private function assertValidationError(callable $action, string $field): void
    {
        try {
            $action();
            $this->fail("Expected a validation error for {$field}.");
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey($field, $exception->errors());
        }
    }
}
