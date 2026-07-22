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
            $this->path('app/Modules/Expenses/Actions/CreateExpense.php'),
            $this->path('app/Modules/Expenses/Actions/ManageExpenses.php'),
            $this->path('app/Modules/Expenses/Actions/UpdateExpense.php'),
            $this->path('app/Modules/Expenses/Actions/VoidExpense.php'),
            $this->path('app/Modules/Expenses/Data/ExpenseDetailData.php'),
            $this->path('app/Modules/Expenses/Data/ExpenseFormData.php'),
            $this->path('app/Modules/Expenses/Presenters/ExpenseCreateFormPresenter.php'),
            $this->path('app/Modules/Expenses/Presenters/ExpenseDetailHeaderPresenter.php'),
            $this->path('app/Modules/Expenses/Presenters/ExpenseDetailOverviewPresenter.php'),
            $this->path('app/Modules/Expenses/Presenters/ExpenseDetailPresenter.php'),
            $this->path('app/Modules/Expenses/Presenters/ExpenseEditFormPresenter.php'),
            $this->path('app/Modules/Expenses/Presenters/ExpenseFormFieldsPresenter.php'),
            $this->path('app/Modules/Expenses/Presenters/ExpenseFormPresenter.php'),
            $this->path('app/Modules/Expenses/Presenters/ExpenseTableRowPresenter.php'),
            $this->path('app/Modules/Expenses/Queries/ExpenseDetailQuery.php'),
            $this->path('app/Modules/Expenses/Queries/ExpenseDirectoryQuery.php'),
            $this->path('app/Modules/Expenses/Queries/ExpenseFormOptionsQuery.php'),
            $this->path('app/Modules/Expenses/Queries/ExpenseIndexQuery.php'),
            $this->path('app/Modules/Expenses/Queries/ExpenseInsightsQuery.php'),
            $this->path('app/Modules/Expenses/Requests/HasExpenseValidationAttributes.php'),
            $this->path('app/Modules/Expenses/Requests/StoreExpenseRequest.php'),
            $this->path('app/Modules/Expenses/Requests/UpdateExpenseRequest.php'),
            $this->path('app/Modules/Expenses/Support/ExpenseAccess.php'),
            $this->path('app/Modules/Expenses/Support/ExpenseAttributes.php'),
            $this->path('app/Modules/Expenses/Support/ExpenseInputGuard.php'),
            $this->path('app/Modules/Expenses/Support/ExpenseOptions.php'),
            $this->path('app/Modules/Expenses/Support/ExpensePortfolioGuard.php'),
            $this->path('app/Modules/Expenses/Support/ExpenseReferenceGuard.php'),
            $this->path('resources/js/modules/expenses/expense-filters.ts'),
            $this->path('resources/js/modules/expenses/expense-metrics.tsx'),
            $this->path('resources/js/modules/expenses/expense-table.tsx'),
            $this->path('resources/js/modules/expenses/expense-table-config.tsx'),
            $this->path('resources/js/modules/expenses/types.ts'),
        ] as $path) {
            $this->assertFileExists($path);
        }

        $this->assertFileDoesNotExist($this->path('app/Modules/Expenses/Support/ExpenseReferenceOptions.php'));
    }

    #[Test]
    public function expense_facades_and_entry_components_stay_small(): void
    {
        foreach ([
            'app/Modules/Expenses/Actions/ManageExpenses.php' => 45,
            'app/Modules/Expenses/Presenters/ExpenseDetailPresenter.php' => 50,
            'app/Modules/Expenses/Presenters/ExpenseFormPresenter.php' => 50,
            'app/Modules/Expenses/Queries/ExpenseIndexQuery.php' => 90,
            'resources/js/modules/expenses/expense-table.tsx' => 65,
        ] as $path => $maximum) {
            $source = $this->source($this->path($path));

            $this->assertLessThanOrEqual($maximum, substr_count($source, "\n") + 1, $path);
        }
    }

    #[Test]
    public function expense_requests_share_access_and_validation_contracts(): void
    {
        foreach ([
            'app/Modules/Expenses/Requests/StoreExpenseRequest.php',
            'app/Modules/Expenses/Requests/UpdateExpenseRequest.php',
        ] as $path) {
            $source = $this->source($this->path($path));

            $this->assertStringContainsString('ExpenseAccess', $source);
            $this->assertStringContainsString('HasExpenseValidationAttributes', $source);
            $this->assertStringNotContainsString("hasAnyRole(['superadmin'", $source);
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
