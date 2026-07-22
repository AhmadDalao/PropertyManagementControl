<?php

namespace Tests\Unit\Architecture;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class DocumentModuleArchitectureTest extends TestCase
{
    #[Test]
    public function document_controller_stays_a_thin_http_adapter(): void
    {
        $source = $this->source($this->path('app/Http/Controllers/DocumentController.php'));

        $this->assertLessThanOrEqual(130, substr_count($source, "\n") + 1);
        $this->assertStringContainsString('DocumentIndexQuery', $source);
        $this->assertStringContainsString('DocumentFormPresenter', $source);
        $this->assertStringContainsString('DocumentDetailPresenter', $source);
        $this->assertStringContainsString('ManageDocuments', $source);
        $this->assertStringContainsString('DocumentDownloads', $source);
        $this->assertStringNotContainsString('Document::query()', $source);
        $this->assertStringNotContainsString('->validate([', $source);
        $this->assertStringNotContainsString('DB::', $source);
        $this->assertStringNotContainsString('Storage::', $source);
    }

    #[Test]
    public function document_frontend_entry_only_composes_module_components(): void
    {
        $source = $this->source($this->path('resources/js/modules/documents/index-page.tsx'));

        $this->assertLessThanOrEqual(70, substr_count($source, "\n") + 1);
        $this->assertStringContainsString("from './document-metrics'", $source);
        $this->assertStringContainsString("from './document-table'", $source);
        $this->assertStringContainsString("from './types'", $source);
        $this->assertStringNotContainsString("from '@/components/data-table'", $source);
    }

    #[Test]
    public function document_facades_stay_small_and_delegate_to_module_boundaries(): void
    {
        foreach ([
            'app/Modules/Documents/Actions/ManageDocuments.php' => 45,
            'app/Modules/Documents/Queries/DocumentIndexQuery.php' => 90,
            'app/Modules/Documents/Presenters/DocumentFormPresenter.php' => 45,
            'app/Modules/Documents/Presenters/DocumentDetailPresenter.php' => 50,
            'resources/js/modules/documents/document-table.tsx' => 70,
        ] as $path => $maximumLines) {
            $source = $this->source($this->path($path));

            $this->assertLessThanOrEqual($maximumLines, substr_count($source, "\n") + 1, $path);
        }

        $manager = $this->source($this->path('app/Modules/Documents/Actions/ManageDocuments.php'));
        $this->assertStringContainsString('CreateDocument', $manager);
        $this->assertStringContainsString('UpdateDocument', $manager);
        $this->assertStringContainsString('DeleteDocument', $manager);
        $this->assertStringNotContainsString('Document::query()', $manager);
        $this->assertStringNotContainsString('Storage::', $manager);

        $table = $this->source($this->path('resources/js/modules/documents/document-table.tsx'));
        $this->assertStringContainsString("from './document-table-config'", $table);
        $this->assertStringContainsString('mobileCard={table.mobileCard}', $table);
    }

    #[Test]
    public function document_requests_and_actions_share_pdf_and_access_rules(): void
    {
        foreach ([
            'app/Modules/Documents/Requests/StoreDocumentRequest.php',
            'app/Modules/Documents/Requests/UpdateDocumentRequest.php',
        ] as $path) {
            $source = $this->source($this->path($path));

            $this->assertStringContainsString('final class', $source);
            $this->assertStringContainsString('DocumentRules', $source);
            $this->assertStringContainsString('DocumentAccess', $source);
        }

        $create = $this->source($this->path('app/Modules/Documents/Actions/CreateDocument.php'));
        $this->assertStringContainsString('DocumentInputGuard', $create);
        $this->assertStringContainsString('DocumentAttachmentResolver', $create);
        $this->assertStringContainsString('DocumentFileStorage', $create);
        $this->assertStringContainsString('lock: true', $create);

        $attachments = $this->source($this->path('app/Modules/Documents/Support/DocumentAttachments.php'));
        $this->assertStringNotContainsString('public function resolve(', $attachments);
    }

    #[Test]
    public function document_module_owns_each_resource_responsibility(): void
    {
        foreach ([
            $this->path('app/Modules/Documents/Actions/CreateDocument.php'),
            $this->path('app/Modules/Documents/Actions/UpdateDocument.php'),
            $this->path('app/Modules/Documents/Actions/DeleteDocument.php'),
            $this->path('app/Modules/Documents/Actions/ManageDocuments.php'),
            $this->path('app/Modules/Documents/Actions/DocumentDownloads.php'),
            $this->path('app/Modules/Documents/Data/DocumentDetailData.php'),
            $this->path('app/Modules/Documents/Data/DocumentFormData.php'),
            $this->path('app/Modules/Documents/Data/StoredDocumentFile.php'),
            $this->path('app/Modules/Documents/Presenters/DocumentCreateFormPresenter.php'),
            $this->path('app/Modules/Documents/Presenters/DocumentDetailHeaderPresenter.php'),
            $this->path('app/Modules/Documents/Presenters/DocumentDetailOverviewPresenter.php'),
            $this->path('app/Modules/Documents/Presenters/DocumentEditFormPresenter.php'),
            $this->path('app/Modules/Documents/Presenters/DocumentFormFieldsPresenter.php'),
            $this->path('app/Modules/Documents/Presenters/DocumentTableRowPresenter.php'),
            $this->path('app/Modules/Documents/Presenters/DocumentDetailPresenter.php'),
            $this->path('app/Modules/Documents/Presenters/DocumentFormPresenter.php'),
            $this->path('app/Modules/Documents/Queries/DocumentDetailQuery.php'),
            $this->path('app/Modules/Documents/Queries/DocumentDirectoryQuery.php'),
            $this->path('app/Modules/Documents/Queries/DocumentFilters.php'),
            $this->path('app/Modules/Documents/Queries/DocumentFormDataQuery.php'),
            $this->path('app/Modules/Documents/Queries/DocumentIndexQuery.php'),
            $this->path('app/Modules/Documents/Queries/DocumentInsightsQuery.php'),
            $this->path('app/Modules/Documents/Requests/StoreDocumentRequest.php'),
            $this->path('app/Modules/Documents/Requests/UpdateDocumentRequest.php'),
            $this->path('app/Modules/Documents/Support/DocumentAccess.php'),
            $this->path('app/Modules/Documents/Support/DocumentAttachmentResolver.php'),
            $this->path('app/Modules/Documents/Support/DocumentAttachments.php'),
            $this->path('app/Modules/Documents/Support/DocumentAttributes.php'),
            $this->path('app/Modules/Documents/Support/DocumentFileStorage.php'),
            $this->path('app/Modules/Documents/Support/DocumentInputGuard.php'),
            $this->path('app/Modules/Documents/Support/DocumentOptions.php'),
            $this->path('app/Modules/Documents/Support/DocumentRules.php'),
            $this->path('resources/js/modules/documents/document-filters.ts'),
            $this->path('resources/js/modules/documents/document-metrics.tsx'),
            $this->path('resources/js/modules/documents/document-table-config.tsx'),
            $this->path('resources/js/modules/documents/document-table.tsx'),
            $this->path('resources/js/modules/documents/types.ts'),
        ] as $path) {
            $this->assertFileExists($path);
        }
    }

    private function source(string $path): string
    {
        $source = file_get_contents($path);

        $this->assertNotFalse($source);

        return $source;
    }

    private function path(string $relativePath): string
    {
        return dirname(__DIR__, 3).'/'.$relativePath;
    }
}
