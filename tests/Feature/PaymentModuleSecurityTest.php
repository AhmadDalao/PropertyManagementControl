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
            'is_public' => false,
        ]);
    }
}
