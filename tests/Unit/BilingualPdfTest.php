<?php

namespace Tests\Unit;

use App\Support\BilingualPdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BilingualPdfTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_shapes_only_arabic_text_nodes_for_dompdf(): void
    {
        $html = '<p>Lease / عقد إيجار</p><p data-label="المحفظة">Portfolio 101</p>';

        $shaped = app(BilingualPdf::class)->shapeArabicTextNodes($html);

        $this->assertStringContainsString('Lease / ', $shaped);
        $this->assertStringContainsString('Portfolio 101', $shaped);
        $this->assertStringContainsString('data-label="المحفظة"', $shaped);
        $this->assertDoesNotMatchRegularExpression('/>[^<]*عقد إيجار[^<]*</u', $shaped);
        $this->assertMatchesRegularExpression('/[\x{FB50}-\x{FEFF}]/u', $shaped);
    }
}
