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
            $this->path('app/Modules/Payments/Actions/ManagePayments.php'),
            $this->path('app/Modules/Payments/Actions/PaymentAllocator.php'),
            $this->path('app/Modules/Payments/Actions/PaymentReceipts.php'),
            $this->path('app/Modules/Payments/Presenters/PaymentDetailPresenter.php'),
            $this->path('app/Modules/Payments/Presenters/PaymentFormPresenter.php'),
            $this->path('app/Modules/Payments/Queries/PaymentIndexQuery.php'),
            $this->path('app/Modules/Payments/Requests/StorePaymentRequest.php'),
            $this->path('app/Modules/Payments/Requests/UpdatePaymentRequest.php'),
            $this->path('app/Modules/Payments/Support/PaymentAccess.php'),
            $this->path('app/Modules/Payments/Support/PaymentOptions.php'),
            $this->path('resources/js/modules/payments/payment-filters.ts'),
            $this->path('resources/js/modules/payments/payment-metrics.tsx'),
            $this->path('resources/js/modules/payments/payment-table.tsx'),
            $this->path('resources/js/modules/payments/types.ts'),
        ] as $path) {
            $this->assertFileExists($path);
        }

        $this->assertFileDoesNotExist($this->path('app/Services/LeaseFinancialService.php'));
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
