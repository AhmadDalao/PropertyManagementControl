<?php

namespace Tests\Unit\Architecture;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class PaymentModuleArchitectureTest extends TestCase
{
    #[Test]
    public function payment_controller_stays_a_thin_http_adapter(): void
    {
        $source = $this->source($this->path('app/Http/Controllers/PaymentController.php'));

        $this->assertLessThanOrEqual(130, substr_count($source, "\n") + 1);
        $this->assertStringContainsString('PaymentIndexQuery', $source);
        $this->assertStringContainsString('PaymentFormPresenter', $source);
        $this->assertStringContainsString('PaymentDetailPresenter', $source);
        $this->assertStringContainsString('ManagePayments', $source);
        $this->assertStringContainsString('PaymentReceipts', $source);
        $this->assertStringNotContainsString('Payment::query()', $source);
        $this->assertStringNotContainsString('->validate([', $source);
        $this->assertStringNotContainsString('DB::', $source);
        $this->assertStringNotContainsString('Storage::', $source);
    }

    #[Test]
    public function payment_frontend_entry_only_composes_module_components(): void
    {
        $source = $this->source($this->path('resources/js/modules/payments/index-page.tsx'));

        $this->assertLessThanOrEqual(70, substr_count($source, "\n") + 1);
        $this->assertStringContainsString("from './payment-metrics'", $source);
        $this->assertStringContainsString("from './payment-table'", $source);
        $this->assertStringContainsString("from './types'", $source);
        $this->assertStringNotContainsString("from '@/components/data-table'", $source);
    }

    #[Test]
    public function payment_module_owns_each_resource_responsibility(): void
    {
        foreach ([
            $this->path('app/Modules/Payments/Actions/CreatePayment.php'),
            $this->path('app/Modules/Payments/Actions/ManagePayments.php'),
            $this->path('app/Modules/Payments/Actions/PaymentAllocator.php'),
            $this->path('app/Modules/Payments/Actions/PaymentReceipts.php'),
            $this->path('app/Modules/Payments/Actions/UpdatePayment.php'),
            $this->path('app/Modules/Payments/Actions/VoidPayment.php'),
            $this->path('app/Modules/Payments/Data/PaymentDetailData.php'),
            $this->path('app/Modules/Payments/Data/PaymentFormData.php'),
            $this->path('app/Modules/Payments/Presenters/PaymentCreateFormPresenter.php'),
            $this->path('app/Modules/Payments/Presenters/PaymentDetailPresenter.php'),
            $this->path('app/Modules/Payments/Presenters/PaymentDetailHeaderPresenter.php'),
            $this->path('app/Modules/Payments/Presenters/PaymentDetailOverviewPresenter.php'),
            $this->path('app/Modules/Payments/Presenters/PaymentEditFormPresenter.php'),
            $this->path('app/Modules/Payments/Presenters/PaymentFormPresenter.php'),
            $this->path('app/Modules/Payments/Presenters/PaymentFormFieldsPresenter.php'),
            $this->path('app/Modules/Payments/Presenters/PaymentRelatedPresenter.php'),
            $this->path('app/Modules/Payments/Presenters/PaymentTableRowPresenter.php'),
            $this->path('app/Modules/Payments/Presenters/PaymentWorkflowPresenter.php'),
            $this->path('app/Modules/Payments/Queries/PaymentDetailQuery.php'),
            $this->path('app/Modules/Payments/Queries/PaymentDirectoryQuery.php'),
            $this->path('app/Modules/Payments/Queries/PaymentFormOptionsQuery.php'),
            $this->path('app/Modules/Payments/Queries/PaymentIndexQuery.php'),
            $this->path('app/Modules/Payments/Queries/PaymentInsightsQuery.php'),
            $this->path('app/Modules/Payments/Requests/HasPaymentValidationAttributes.php'),
            $this->path('app/Modules/Payments/Requests/StorePaymentRequest.php'),
            $this->path('app/Modules/Payments/Requests/UpdatePaymentRequest.php'),
            $this->path('app/Modules/Payments/Support/PaymentAccess.php'),
            $this->path('app/Modules/Payments/Support/PaymentAttributes.php'),
            $this->path('app/Modules/Payments/Support/PaymentInputGuard.php'),
            $this->path('app/Modules/Payments/Support/PaymentLeaseGuard.php'),
            $this->path('app/Modules/Payments/Support/PaymentOptions.php'),
            $this->path('app/Modules/Payments/Support/PaymentTransitionGuard.php'),
            $this->path('resources/js/modules/payments/payment-filters.ts'),
            $this->path('resources/js/modules/payments/payment-metrics.tsx'),
            $this->path('resources/js/modules/payments/payment-table.tsx'),
            $this->path('resources/js/modules/payments/payment-table-config.tsx'),
            $this->path('resources/js/modules/payments/types.ts'),
        ] as $path) {
            $this->assertFileExists($path);
        }

        $this->assertFileDoesNotExist($this->path('app/Services/LeaseFinancialService.php'));
    }

    #[Test]
    public function payment_facades_and_entry_components_stay_small(): void
    {
        foreach ([
            'app/Modules/Payments/Actions/ManagePayments.php' => 45,
            'app/Modules/Payments/Presenters/PaymentDetailPresenter.php' => 55,
            'app/Modules/Payments/Presenters/PaymentFormPresenter.php' => 50,
            'app/Modules/Payments/Queries/PaymentIndexQuery.php' => 90,
            'resources/js/modules/payments/payment-table.tsx' => 65,
        ] as $path => $maximum) {
            $source = $this->source($this->path($path));

            $this->assertLessThanOrEqual($maximum, substr_count($source, "\n") + 1, $path);
        }
    }

    #[Test]
    public function payment_requests_share_access_and_validation_contracts(): void
    {
        foreach ([
            'app/Modules/Payments/Requests/StorePaymentRequest.php',
            'app/Modules/Payments/Requests/UpdatePaymentRequest.php',
        ] as $path) {
            $source = $this->source($this->path($path));

            $this->assertStringContainsString('PaymentAccess', $source);
            $this->assertStringContainsString('HasPaymentValidationAttributes', $source);
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
