<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\Payment;
use App\Models\Portfolio;
use App\Models\TenantProfile;
use App\Models\User;
use App\Modules\Payments\Actions\ManagePayments;
use App\Modules\Payments\Actions\PaymentAllocator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia as Assert;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class PaymentModuleSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_payment_identity_and_currency_are_derived_from_the_lease(): void
    {
        $portfolio = $this->createPortfolio();
        $foreignPortfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $tenant = $this->createTenantProfile(
            $portfolio,
            $this->createUserWithRole('tenant', $portfolio),
        );
        $decoyTenant = $this->createTenantProfile(
            $portfolio,
            $this->createUserWithRole('tenant', $portfolio),
        );
        $lease = $this->createLease(
            $portfolio,
            $tenant,
            $this->createAsset($portfolio),
            $owner,
            ['currency' => 'USD'],
        );

        $this->actingAs($owner)
            ->post(route('payments.store'), [
                'portfolio_id' => $foreignPortfolio->id,
                'lease_id' => $lease->id,
                'tenant_profile_id' => $decoyTenant->id,
                'type' => 'rent',
                'method' => 'bank_transfer',
                'status' => 'pending',
                'reference' => 'LEASE-SOURCE-OF-TRUTH',
                'received_on' => now()->toDateString(),
                'amount' => 750,
                'currency' => 'BHD',
            ])
            ->assertRedirect();

        $payment = Payment::query()->where('reference', 'LEASE-SOURCE-OF-TRUTH')->firstOrFail();

        $this->assertSame($portfolio->id, $payment->portfolio_id);
        $this->assertSame($tenant->id, $payment->tenant_profile_id);
        $this->assertSame($lease->id, $payment->lease_id);
        $this->assertSame($owner->id, $payment->recorded_by_user_id);
        $this->assertSame('USD', $payment->currency);
    }

    public function test_draft_lease_cannot_receive_a_payment(): void
    {
        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $tenant = $this->createTenantProfile(
            $portfolio,
            $this->createUserWithRole('tenant', $portfolio),
        );
        $lease = $this->createLease(
            $portfolio,
            $tenant,
            $this->createAsset($portfolio),
            $owner,
            ['status' => 'draft'],
        );

        $this->actingAs($owner)
            ->post(route('payments.store'), [
                'lease_id' => $lease->id,
                'type' => 'rent',
                'method' => 'cash',
                'status' => 'posted',
                'reference' => 'DRAFT-REJECTED',
                'received_on' => now()->toDateString(),
                'amount' => 500,
            ])
            ->assertSessionHasErrors('lease_id');

        $this->assertDatabaseMissing('payments', ['reference' => 'DRAFT-REJECTED']);
    }

    public function test_tenant_detail_payment_shortcut_targets_the_active_lease(): void
    {
        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $tenant = $this->createTenantProfile(
            $portfolio,
            $this->createUserWithRole('tenant', $portfolio),
        );
        $lease = $this->createLease(
            $portfolio,
            $tenant,
            $this->createAsset($portfolio),
            $owner,
        );

        $this->actingAs($owner)
            ->get(route('tenants.show', $tenant))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('detailPage.header.actions', fn ($actions) => collect($actions)->contains(
                    fn ($action) => $action['label'] === 'Record payment'
                        && $action['href'] === route('payments.create', ['lease_id' => $lease->id]),
                )));
    }

    public function test_arabic_payment_form_localizes_the_lease_status_and_balance(): void
    {
        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $tenant = $this->createTenantProfile(
            $portfolio,
            $this->createUserWithRole('tenant', $portfolio),
        );
        $lease = $this->createLease(
            $portfolio,
            $tenant,
            $this->createAsset($portfolio),
            $owner,
        );

        $this->actingAs($owner)
            ->withSession(['locale' => 'ar'])
            ->get(route('payments.create', ['lease_id' => $lease->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('app.locale', 'ar')
                ->where('formPage.fields', function ($fields): bool {
                    $leaseField = collect($fields)->firstWhere('name', 'lease_id');
                    $label = (string) data_get($leaseField, 'options.0.label');

                    return str_contains($label, 'نشط')
                        && str_contains($label, 'متبقي')
                        && ! str_contains($label, 'remaining');
                }));
    }

    public function test_superadmin_portfolio_selection_reloads_scoped_payment_options_with_a_lean_payload(): void
    {
        $firstPortfolio = $this->createPortfolio(['name_en' => 'First Portfolio']);
        $secondPortfolio = $this->createPortfolio(['name_en' => 'Second Portfolio']);
        $superadmin = $this->createUserWithRole('superadmin');
        $firstOwner = $this->createUserWithRole('owner', $firstPortfolio);
        $secondOwner = $this->createUserWithRole('owner', $secondPortfolio);
        $firstTenant = $this->createTenantProfile(
            $firstPortfolio,
            $this->createUserWithRole('tenant', $firstPortfolio, [
                'name' => 'First Payment Tenant',
                'email' => 'first-private@example.test',
            ]),
        );
        $secondTenant = $this->createTenantProfile(
            $secondPortfolio,
            $this->createUserWithRole('tenant', $secondPortfolio, [
                'name' => 'Second Payment Tenant',
                'email' => 'second-private@example.test',
            ]),
        );
        $firstLease = $this->createLease(
            $firstPortfolio,
            $firstTenant,
            $this->createAsset($firstPortfolio),
            $firstOwner,
        );
        $secondLease = $this->createLease(
            $secondPortfolio,
            $secondTenant,
            $this->createAsset($secondPortfolio),
            $secondOwner,
        );

        $response = $this->actingAs($superadmin)
            ->get(route('payments.create', ['portfolio_id' => $secondPortfolio->id]));

        $response
            ->assertOk()
            ->assertDontSee('first-private@example.test')
            ->assertDontSee('second-private@example.test')
            ->assertInertia(fn (Assert $page) => $page
                ->where('formPage.initialValues.portfolio_id', (string) $secondPortfolio->id)
                ->where('formPage.initialValues.lease_id', (string) $secondLease->id)
                ->where('formPage.fields', function ($fields) use ($firstLease, $secondLease): bool {
                    $fields = collect($fields);
                    $portfolio = $fields->firstWhere('name', 'portfolio_id');
                    $leaseOptions = collect($fields->firstWhere('name', 'lease_id')['options'] ?? []);

                    return data_get($portfolio, 'reloadOnChange.queryKey') === 'portfolio_id'
                        && $leaseOptions->contains('value', $secondLease->id)
                        && ! $leaseOptions->contains('value', $firstLease->id)
                        && $leaseOptions->every(fn (array $option): bool => array_keys($option) === ['value', 'label']);
                }));
    }

    public function test_tenant_payment_detail_hides_internal_notes_documents_and_history(): void
    {
        Storage::fake('local');

        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $tenantUser = $this->createUserWithRole('tenant', $portfolio);
        $tenant = $this->createTenantProfile($portfolio, $tenantUser);
        $lease = $this->createLease(
            $portfolio,
            $tenant,
            $this->createAsset($portfolio),
            $owner,
        );
        $payment = $this->createPayment($portfolio, $tenant, $owner, $lease->id, [
            'status' => 'posted',
            'notes' => 'Internal reconciliation note.',
        ]);
        Storage::disk('local')->put('payments/receipt.pdf', '%PDF-receipt');
        Storage::disk('local')->put('payments/internal.pdf', '%PDF-internal');
        $receipt = $this->createPaymentDocument($payment, $owner, 'receipt', 'payments/receipt.pdf');
        $this->createPaymentDocument($payment, $owner, 'owner_report', 'payments/internal.pdf');

        $this->actingAs($tenantUser)
            ->get(route('payments.show', $payment))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('detailPage.header.actions', fn ($actions) => collect($actions)->pluck('label')->all() === [
                    'Download receipt',
                    'Open lease',
                ])
                ->where('detailPage.sections.0.items', fn ($items) => ! collect($items)->contains('label', 'Notes')
                    && ! collect($items)->contains('label', 'Recorded by')
                    && collect($items)
                        ->reject(fn ($item) => $item['label'] === 'Lease')
                        ->every(fn ($item) => empty($item['href'])))
                ->where('detailPage.timeline', [])
                ->has('detailPage.documents', 1)
                ->where('detailPage.documents.0.href', route('documents.download', $receipt)));
    }

    public function test_payment_action_rejects_cross_portfolio_mutation_when_reused_directly(): void
    {
        $ownerPortfolio = $this->createPortfolio();
        $foreignPortfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $ownerPortfolio);
        $foreignOwner = $this->createUserWithRole('owner', $foreignPortfolio);
        $tenant = $this->createTenantProfile(
            $foreignPortfolio,
            $this->createUserWithRole('tenant', $foreignPortfolio),
        );
        $lease = $this->createLease(
            $foreignPortfolio,
            $tenant,
            $this->createAsset($foreignPortfolio),
            $foreignOwner,
        );
        $payment = $this->createPayment($foreignPortfolio, $tenant, $foreignOwner, $lease->id);

        try {
            app(ManagePayments::class)->void($owner, $payment);
            $this->fail('Cross-portfolio payment mutation was not rejected.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }

        $this->assertSame('pending', $payment->fresh()->status);
    }

    public function test_voided_payment_cannot_be_reopened(): void
    {
        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $tenant = $this->createTenantProfile(
            $portfolio,
            $this->createUserWithRole('tenant', $portfolio),
        );
        $lease = $this->createLease(
            $portfolio,
            $tenant,
            $this->createAsset($portfolio),
            $owner,
        );
        $payment = $this->createPayment($portfolio, $tenant, $owner, $lease->id);

        $this->actingAs($owner)
            ->delete(route('payments.destroy', $payment))
            ->assertRedirect(route('payments.index'));

        $this->actingAs($owner)
            ->put(route('payments.update', $payment), [
                'status' => 'posted',
                'notes' => 'Attempted reopen.',
            ])
            ->assertSessionHasErrors('status');

        $this->assertSame('void', $payment->fresh()->status);

        $this->actingAs($owner)
            ->get(route('payments.edit', $payment))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('formPage.fields.0.options', [[
                    'value' => 'void',
                    'label' => 'Void',
                ]]));

        $this->assertValidationError(
            fn () => app(ManagePayments::class)->update($owner, $payment, [
                'status' => 'posted',
                'notes' => 'Direct reopen attempt.',
            ]),
            'status',
        );
    }

    public function test_direct_payment_action_rejects_malformed_input_and_inactive_portfolios(): void
    {
        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $tenant = $this->createTenantProfile(
            $portfolio,
            $this->createUserWithRole('tenant', $portfolio),
        );
        $lease = $this->createLease(
            $portfolio,
            $tenant,
            $this->createAsset($portfolio),
            $owner,
        );

        $this->assertValidationError(
            fn () => app(ManagePayments::class)->create($owner, ['lease_id' => $lease->id]),
            'type',
        );
        $this->assertDatabaseCount('payments', 0);

        $portfolio->update(['status' => 'archived']);
        $this->assertValidationError(
            fn () => app(ManagePayments::class)->create(
                $owner,
                $this->paymentPayload($lease->id, ['reference' => 'INACTIVE-PORTFOLIO']),
            ),
            'lease_id',
        );
        $this->assertDatabaseMissing('payments', ['reference' => 'INACTIVE-PORTFOLIO']);
    }

    public function test_direct_payment_action_rejects_duplicate_references_and_non_scalar_input(): void
    {
        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $tenant = $this->createTenantProfile(
            $portfolio,
            $this->createUserWithRole('tenant', $portfolio),
        );
        $lease = $this->createLease(
            $portfolio,
            $tenant,
            $this->createAsset($portfolio),
            $owner,
        );
        $this->createPayment($portfolio, $tenant, $owner, $lease->id, [
            'reference' => 'UNIQUE-PAYMENT-REFERENCE',
        ]);

        $this->assertValidationError(
            fn () => app(ManagePayments::class)->create(
                $owner,
                $this->paymentPayload($lease->id, ['reference' => 'UNIQUE-PAYMENT-REFERENCE']),
            ),
            'reference',
        );
        $this->assertValidationError(
            fn () => app(ManagePayments::class)->create(
                $owner,
                $this->paymentPayload($lease->id, ['amount' => ['500']]),
            ),
            'amount',
        );
        $this->assertValidationError(
            fn () => app(ManagePayments::class)->create(
                $owner,
                $this->paymentPayload($lease->id, ['notes' => ['not', 'text']]),
            ),
            'notes',
        );
        $this->assertDatabaseCount('payments', 1);
    }

    public function test_payment_list_minimizes_personal_data_and_normalizes_filters(): void
    {
        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $tenant = $this->createTenantProfile(
            $portfolio,
            $this->createUserWithRole('tenant', $portfolio, [
                'name' => 'Private Payment Tenant',
                'email' => 'payment-private@example.test',
            ]),
        );
        $lease = $this->createLease(
            $portfolio,
            $tenant,
            $this->createAsset($portfolio),
            $owner,
        );
        $payment = $this->createPayment($portfolio, $tenant, $owner, $lease->id, [
            'reference' => 'PAYMENT-PRIVATE-PAYLOAD',
            'notes' => 'Sensitive internal bank reconciliation note.',
        ]);

        $this->actingAs($owner)
            ->get(route('payments.index', [
                'search' => $payment->reference,
                'status' => 'not-a-status',
                'type' => 'not-a-type',
                'method' => 'not-a-method',
                'date_from' => '2026-99-99',
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('payments.total', 1)
                ->where('payments.data.0.tenant_profile.user.name', 'Private Payment Tenant')
                ->missing('payments.data.0.tenant_profile.user.email')
                ->missing('payments.data.0.notes')
                ->missing('payments.data.0.recorded_by')
                ->missing('payments.data.0.allocations')
                ->where('filters.status', 'all')
                ->where('filters.type', 'all')
                ->where('filters.method', 'all')
                ->where('filters.date_from', ''));
    }

    public function test_arabic_payment_index_create_and_detail_are_translated(): void
    {
        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio, ['preferred_locale' => 'ar']);
        $tenant = $this->createTenantProfile(
            $portfolio,
            $this->createUserWithRole('tenant', $portfolio, ['name' => 'Arabic Payment Tenant']),
        );
        $lease = $this->createLease(
            $portfolio,
            $tenant,
            $this->createAsset($portfolio),
            $owner,
        );
        $payment = $this->createPayment($portfolio, $tenant, $owner, $lease->id);

        $this->actingAs($owner)
            ->withSession(['locale' => 'ar'])
            ->get(route('payments.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('app.locale', 'ar')
                ->where('app.direction', 'rtl')
                ->where('counts.0.label', 'الكل')
                ->where('app.translations.payments.ledger_title', 'سجل الدفعات'));

        $this->actingAs($owner)
            ->withSession(['locale' => 'ar'])
            ->get(route('payments.create'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('formPage.title', 'تسجيل دفعة')
                ->where('formPage.submitLabel', 'تسجيل دفعة')
                ->where('formPage.fields.0.label', 'العقد')
                ->where('formPage.fields.1.label', 'نوع الدفعة'));

        $this->actingAs($owner)
            ->withSession(['locale' => 'ar'])
            ->get(route('payments.show', $payment))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('detailPage.header.eyebrow', 'تفاصيل الدفعة')
                ->where('detailPage.sections.0.title', 'سجل الدفعة')
                ->where('detailPage.related.0.title', 'التوزيعات'));
    }

    public function test_payment_insights_do_not_present_mixed_currencies_as_one_total(): void
    {
        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $tenant = $this->createTenantProfile(
            $portfolio,
            $this->createUserWithRole('tenant', $portfolio),
        );
        $sarLease = $this->createLease(
            $portfolio,
            $tenant,
            $this->createAsset($portfolio),
            $owner,
        );
        $usdLease = $this->createLease(
            $portfolio,
            $tenant,
            $this->createAsset($portfolio),
            $owner,
            ['currency' => 'USD'],
        );
        $this->createPayment($portfolio, $tenant, $owner, $sarLease->id, [
            'status' => 'posted',
            'amount' => 500,
        ]);
        $this->createPayment($portfolio, $tenant, $owner, $usdLease->id, [
            'status' => 'pending',
            'amount' => 100,
            'currency' => 'USD',
        ]);

        $this->actingAs($owner)
            ->get(route('payments.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('paymentInsights.mixed_currencies', true)
                ->where('paymentInsights.currency', null));
    }

    public function test_tenant_receipt_generation_is_attributed_to_the_payment_recorder(): void
    {
        Storage::fake('local');

        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $tenantUser = $this->createUserWithRole('tenant', $portfolio);
        $tenant = $this->createTenantProfile($portfolio, $tenantUser);
        $lease = $this->createLease(
            $portfolio,
            $tenant,
            $this->createAsset($portfolio),
            $owner,
        );
        $payment = $this->createPayment($portfolio, $tenant, $owner, $lease->id, [
            'status' => 'posted',
        ]);

        $this->actingAs($tenantUser)
            ->get(route('payments.receipt', $payment))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $receipt = Document::query()->where('type', 'receipt')->firstOrFail();
        $this->assertSame($owner->id, $receipt->uploaded_by_user_id);
    }

    public function test_allocator_rejects_payment_currency_that_does_not_match_the_lease(): void
    {
        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $tenant = $this->createTenantProfile(
            $portfolio,
            $this->createUserWithRole('tenant', $portfolio),
        );
        $lease = $this->createLease(
            $portfolio,
            $tenant,
            $this->createAsset($portfolio),
            $owner,
        );
        $payment = $this->createPayment($portfolio, $tenant, $owner, $lease->id, [
            'status' => 'posted',
            'currency' => 'USD',
        ]);

        $this->expectException(ValidationException::class);

        try {
            app(PaymentAllocator::class)->allocate($payment);
        } finally {
            $this->assertDatabaseMissing('payment_allocations', ['payment_id' => $payment->id]);
        }
    }

    public function test_receipt_filename_is_safe_and_regeneration_removes_the_previous_file(): void
    {
        Storage::fake('local');

        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $tenant = $this->createTenantProfile(
            $portfolio,
            $this->createUserWithRole('tenant', $portfolio),
        );
        $lease = $this->createLease(
            $portfolio,
            $tenant,
            $this->createAsset($portfolio),
            $owner,
        );
        $payment = $this->createPayment($portfolio, $tenant, $owner, $lease->id, [
            'reference' => '../../unsafe / PAY?',
            'status' => 'posted',
        ]);

        $this->actingAs($owner)
            ->get(route('payments.receipt', $payment))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $document = Document::query()->where('type', 'receipt')->firstOrFail();
        $firstPath = $document->file_path;

        $this->assertMatchesRegularExpression('/\Areceipt-[a-z0-9-]+\.pdf\z/', $document->original_name);
        $this->assertStringNotContainsString('..', $document->file_path);
        Storage::disk('local')->assertExists($firstPath);

        $this->actingAs($owner)
            ->get(route('payments.receipt', $payment))
            ->assertOk();

        $replacementPath = $document->fresh()->file_path;
        $this->assertNotSame($firstPath, $replacementPath);
        Storage::disk('local')->assertMissing($firstPath);
        Storage::disk('local')->assertExists($replacementPath);
    }

    /** @param array<string, mixed> $attributes */
    private function createPayment(
        Portfolio $portfolio,
        TenantProfile $tenant,
        User $owner,
        int $leaseId,
        array $attributes = [],
    ): Payment {
        return Payment::query()->create(array_merge([
            'portfolio_id' => $portfolio->id,
            'lease_id' => $leaseId,
            'tenant_profile_id' => $tenant->id,
            'recorded_by_user_id' => $owner->id,
            'reference' => 'PAY-'.str()->upper(str()->random(10)),
            'type' => 'rent',
            'method' => 'cash',
            'status' => 'pending',
            'received_on' => now()->toDateString(),
            'amount' => 500,
            'currency' => 'SAR',
        ], $attributes));
    }

    private function createPaymentDocument(
        Payment $payment,
        User $owner,
        string $type,
        string $path,
    ): Document {
        return Document::query()->create([
            'portfolio_id' => $payment->portfolio_id,
            'uploaded_by_user_id' => $owner->id,
            'documentable_type' => $payment->getMorphClass(),
            'documentable_id' => $payment->id,
            'type' => $type,
            'title_en' => str($type)->headline()->toString(),
            'title_ar' => 'مستند دفعة',
            'disk' => 'local',
            'file_path' => $path,
            'original_name' => basename($path),
            'mime_type' => 'application/pdf',
            'file_size' => 12,
            'is_public' => $type === 'receipt',
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function paymentPayload(int $leaseId, array $overrides = []): array
    {
        return array_merge([
            'lease_id' => $leaseId,
            'type' => 'rent',
            'method' => 'bank_transfer',
            'status' => 'posted',
            'reference' => null,
            'received_on' => now()->toDateString(),
            'amount' => 500,
            'notes' => null,
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
