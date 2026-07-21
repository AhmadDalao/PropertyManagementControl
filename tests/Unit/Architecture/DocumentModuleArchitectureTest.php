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
    public function document_module_owns_each_resource_responsibility(): void
    {
        foreach ([
            $this->path('app/Modules/Documents/Actions/ManageDocuments.php'),
            $this->path('app/Modules/Documents/Actions/DocumentDownloads.php'),
            $this->path('app/Modules/Documents/Presenters/DocumentDetailPresenter.php'),
            $this->path('app/Modules/Documents/Presenters/DocumentFormPresenter.php'),
            $this->path('app/Modules/Documents/Queries/DocumentIndexQuery.php'),
            $this->path('app/Modules/Documents/Requests/StoreDocumentRequest.php'),
            $this->path('app/Modules/Documents/Requests/UpdateDocumentRequest.php'),
            $this->path('app/Modules/Documents/Support/DocumentAccess.php'),
            $this->path('app/Modules/Documents/Support/DocumentAttachments.php'),
            $this->path('app/Modules/Documents/Support/DocumentOptions.php'),
            $this->path('resources/js/modules/documents/document-filters.ts'),
            $this->path('resources/js/modules/documents/document-metrics.tsx'),
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
