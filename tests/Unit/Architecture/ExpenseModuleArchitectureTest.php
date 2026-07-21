<?php

namespace Tests\Unit\Architecture;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ExpenseModuleArchitectureTest extends TestCase
{
    #[Test]
    public function expense_controller_stays_a_thin_http_adapter(): void
    {
        $source = $this->source($this->path('app/Http/Controllers/ExpenseEntryController.php'));

        $this->assertLessThanOrEqual(120, substr_count($source, "\n") + 1);
        $this->assertStringContainsString('ExpenseIndexQuery', $source);
        $this->assertStringContainsString('ExpenseFormPresenter', $source);
        $this->assertStringContainsString('ExpenseDetailPresenter', $source);
        $this->assertStringContainsString('ManageExpenses', $source);
        $this->assertStringNotContainsString('ExpenseEntry::query()', $source);
        $this->assertStringNotContainsString('->validate([', $source);
        $this->assertStringNotContainsString('DB::', $source);
    }

    #[Test]
    public function expense_frontend_entry_only_composes_module_components(): void
    {
        $source = $this->source($this->path('resources/js/modules/expenses/index-page.tsx'));

        $this->assertLessThanOrEqual(70, substr_count($source, "\n") + 1);
        $this->assertStringContainsString("from './expense-metrics'", $source);
        $this->assertStringContainsString("from './expense-table'", $source);
        $this->assertStringContainsString("from './types'", $source);
        $this->assertStringNotContainsString("from '@/components/data-table'", $source);
    }

    #[Test]
    public function expense_module_owns_each_resource_responsibility(): void
    {
        foreach ([
            $this->path('app/Modules/Expenses/Actions/ManageExpenses.php'),
            $this->path('app/Modules/Expenses/Presenters/ExpenseDetailPresenter.php'),
            $this->path('app/Modules/Expenses/Presenters/ExpenseFormPresenter.php'),
            $this->path('app/Modules/Expenses/Queries/ExpenseIndexQuery.php'),
            $this->path('app/Modules/Expenses/Requests/HasExpenseValidationAttributes.php'),
            $this->path('app/Modules/Expenses/Requests/StoreExpenseRequest.php'),
            $this->path('app/Modules/Expenses/Requests/UpdateExpenseRequest.php'),
            $this->path('app/Modules/Expenses/Support/ExpenseAccess.php'),
            $this->path('app/Modules/Expenses/Support/ExpenseOptions.php'),
            $this->path('app/Modules/Expenses/Support/ExpenseReferenceOptions.php'),
            $this->path('resources/js/modules/expenses/expense-filters.ts'),
            $this->path('resources/js/modules/expenses/expense-metrics.tsx'),
            $this->path('resources/js/modules/expenses/expense-table.tsx'),
            $this->path('resources/js/modules/expenses/types.ts'),
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
