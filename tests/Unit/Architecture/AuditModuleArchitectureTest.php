<?php

namespace Tests\Unit\Architecture;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class AuditModuleArchitectureTest extends TestCase
{
    #[Test]
    public function audit_controller_stays_a_thin_http_adapter(): void
    {
        $source = $this->source($this->path('app/Http/Controllers/AuditLogController.php'));

        $this->assertLessThanOrEqual(45, substr_count($source, "\n") + 1);
        $this->assertStringContainsString('AuditLogQuery', $source);
        $this->assertStringContainsString('AuditWorkbookExport', $source);
        $this->assertStringNotContainsString('Activity::query()', $source);
        $this->assertStringNotContainsString('XlsxWorkbook', $source);
        $this->assertStringNotContainsString('->validate([', $source);
    }

    #[Test]
    public function audit_frontend_entry_only_composes_module_components(): void
    {
        $source = $this->source($this->path('resources/js/modules/audit/index-page.tsx'));

        $this->assertLessThanOrEqual(50, substr_count($source, "\n") + 1);
        $this->assertStringContainsString("from './audit-metrics'", $source);
        $this->assertStringContainsString("from './audit-table'", $source);
        $this->assertStringContainsString("from './types'", $source);
        $this->assertStringNotContainsString("from '@/components/data-table'", $source);
    }

    #[Test]
    public function audit_module_owns_each_operational_responsibility(): void
    {
        foreach ([
            'app/Modules/Audit/Actions/AuditWorkbookExport.php',
            'app/Modules/Audit/Presenters/AuditActivityPresenter.php',
            'app/Modules/Audit/Queries/AuditLogQuery.php',
            'app/Modules/Audit/Requests/AuditIndexRequest.php',
            'app/Modules/Audit/Support/AuditAccess.php',
            'app/Modules/Audit/Support/AuditPortfolioScope.php',
            'app/Modules/Audit/Support/AuditSubjectRegistry.php',
            'resources/js/modules/audit/audit-filters.ts',
            'resources/js/modules/audit/audit-format.ts',
            'resources/js/modules/audit/audit-metrics.tsx',
            'resources/js/modules/audit/audit-table.tsx',
            'resources/js/modules/audit/types.ts',
        ] as $relativePath) {
            $this->assertFileExists($this->path($relativePath));
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
