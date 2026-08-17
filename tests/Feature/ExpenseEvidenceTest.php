<?php

namespace Tests\Feature;

use App\Models\AssetStakeholder;
use App\Models\Document;
use App\Models\ExpenseEntry;
use App\Modules\Portfolios\Support\PortfolioModules;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ExpenseEvidenceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        Storage::fake('local');
    }

    public function test_expense_detail_is_focused_and_uploads_private_pdf_evidence(): void
    {
        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $asset = $this->createAsset($portfolio, ['title_en' => 'Evidence property']);
        $expense = $this->expense($portfolio->id, $owner->id, [
            'asset_id' => $asset->id,
            'title' => 'Water pump invoice',
            'vendor_name' => 'Water Works',
        ]);

        $this->actingAs($owner)
            ->get(route('expenses.show', $expense))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/expenses/show')
                ->where('detailPage.availableTabs', ['overview', 'financial', 'evidence', 'history'])
                ->where('detailPage.sections.0.key', 'context')
                ->where('detailPage.sections.1.key', 'financial')
                ->where('detailPage.evidence.enabled', true)
                ->where('detailPage.evidence.can_upload', true)
                ->has('detailPage.evidence.documents', 0)
                ->missing('detailPage.decisionCards')
                ->where('detailPage.evidence.upload_url', fn (string $url): bool => str_contains($url, 'documentable_type=expense')));

        $this->actingAs($owner)
            ->get(route('documents.create', [
                'documentable_type' => 'expense',
                'documentable_id' => $expense->id,
                'type' => 'other',
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('formPage.initialValues.documentable_type', 'expense')
                ->where('formPage.initialValues.documentable_id', (string) $expense->id)
                ->where('formPage.initialValues.is_public', false)
                ->where('formPage.fields', fn ($fields): bool => collect($fields)
                    ->where('name', 'documentable_type')
                    ->first()['type'] === 'hidden'));

        $response = $this->actingAs($owner)->post(route('documents.store'), [
            'documentable_type' => 'expense',
            'documentable_id' => $expense->id,
            'type' => 'other',
            'title_en' => 'Water pump receipt',
            'title_ar' => 'إيصال مضخة المياه',
            'is_public' => true,
            'file' => $this->fakePdf('water-pump-receipt.pdf'),
        ]);
        $document = Document::query()->firstOrFail();

        $response->assertRedirect(route('documents.show', $document));
        $this->assertSame('expense_entry', $document->documentable_type);
        $this->assertSame($expense->id, $document->documentable_id);
        $this->assertFalse($document->is_public);
        Storage::disk('local')->assertExists($document->file_path);

        $this->actingAs($owner)
            ->get(route('expenses.show', $expense, false).'?tab=evidence')
            ->assertInertia(fn (Assert $page) => $page
                ->has('detailPage.evidence.documents', 1)
                ->where('detailPage.evidence.documents.0.title', 'Water pump receipt')
                ->where('detailPage.evidence.documents.0.href', route('documents.download', $document)));

        $download = $this->actingAs($owner)->get(route('documents.download', $document));
        $download->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $download->streamedContent());
    }

    public function test_expense_evidence_obeys_property_assignments_and_tenant_isolation(): void
    {
        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $manager = $this->createUserWithRole('property_manager', $portfolio);
        $otherManager = $this->createUserWithRole('property_manager', $portfolio);
        $tenant = $this->createUserWithRole('tenant', $portfolio);
        $asset = $this->createAsset($portfolio, ['asset_type' => 'building']);
        $expense = $this->expense($portfolio->id, $owner->id, [
            'asset_id' => $asset->id,
            'title' => 'Assigned roof repair',
        ]);

        AssetStakeholder::query()->create([
            'asset_id' => $asset->id,
            'portfolio_id' => $portfolio->id,
            'user_id' => $manager->id,
            'relationship_type' => 'manager',
            'is_primary' => true,
        ]);

        $this->actingAs($owner)->post(route('documents.store'), [
            'documentable_type' => 'expense',
            'documentable_id' => $expense->id,
            'type' => 'other',
            'title_en' => 'Assigned repair invoice',
            'title_ar' => 'فاتورة الإصلاح المسند',
            'file' => $this->fakePdf('assigned-repair.pdf'),
        ])->assertRedirect();
        $document = Document::query()->firstOrFail();

        $this->actingAs($manager)->get(route('expenses.show', $expense))->assertOk();
        $this->actingAs($manager)->get(route('documents.download', $document))->assertOk();
        $this->actingAs($otherManager)->get(route('expenses.show', $expense))->assertForbidden();
        $this->actingAs($otherManager)->get(route('documents.download', $document))->assertForbidden();
        $this->actingAs($tenant)->get(route('documents.download', $document))->assertForbidden();

        $this->actingAs($manager)
            ->get(route('documents.index', ['property_id' => $asset->id, 'search' => 'Assigned repair']))
            ->assertInertia(fn (Assert $page) => $page
                ->where('documents.total', 1)
                ->where('documents.data.0.attachment.type', 'expense')
                ->where('documents.data.0.attachment.url', route('expenses.show', $expense)));

        $search = $this->actingAs($manager)->getJson(route('global-search', [
            'q' => 'Assigned roof repair',
        ]));
        $search->assertOk();
        $this->assertTrue(collect($search->json('results'))->contains(
            fn (array $result): bool => $result['group'] === 'Documents'
                && $result['title'] === 'Assigned repair invoice',
        ));
    }

    public function test_disabled_documents_module_removes_expense_evidence_actions_and_tab(): void
    {
        $portfolio = $this->createPortfolio();
        $portfolio->update([
            'module_settings' => [
                ...PortfolioModules::defaults(),
                'documents' => false,
            ],
        ]);
        $owner = $this->createUserWithRole('owner', $portfolio);
        $expense = $this->expense($portfolio->id, $owner->id);

        $this->actingAs($owner)
            ->get(route('expenses.show', $expense))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('detailPage.availableTabs', ['overview', 'financial', 'history'])
                ->where('detailPage.evidence.enabled', false)
                ->has('detailPage.evidence.documents', 0)
                ->where('detailPage.workflow.actions', fn ($actions): bool => ! collect($actions)
                    ->pluck('label')
                    ->contains('Upload receipt or invoice')));
    }

    /** @param array<string, mixed> $attributes */
    private function expense(int $portfolioId, int $creatorId, array $attributes = []): ExpenseEntry
    {
        return ExpenseEntry::query()->create([
            'portfolio_id' => $portfolioId,
            'created_by_user_id' => $creatorId,
            'category' => 'maintenance',
            'title' => 'Test expense',
            'incurred_on' => now()->toDateString(),
            'amount' => 250,
            'currency' => 'SAR',
            'status' => 'posted',
            ...$attributes,
        ]);
    }
}
