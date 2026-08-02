<?php

namespace Tests\Unit\Exports;

use App\Modules\Exports\Support\SimpleDocx;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use ZipArchive;

class SimpleDocxTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_builds_a_paginated_fixed_width_bilingual_report(): void
    {
        $activityRows = [[
            'Date / التاريخ',
            'Type / النوع',
            'Record / السجل',
            'By / بواسطة',
        ]];

        foreach (range(1, 13) as $index) {
            $activityRows[] = [
                "2026-08-{$index}",
                'Payment / دفعة',
                "PAY-{$index}",
                'Operations Manager',
            ];
        }

        $path = app(SimpleDocx::class)->create([
            [
                'type' => 'paragraph',
                'text' => 'Property Operating Report | تقرير تشغيل العقار',
                'style' => 'Title',
            ],
            [
                'type' => 'table',
                'rows' => [
                    [
                        'Currency / العملة',
                        'Collected / المحصل',
                        'Expenses / المصاريف',
                        'Net / الصافي',
                        'Due / المستحق',
                        'Paid / المسدد',
                        'Arrears / المتأخرات',
                        'Collection / التحصيل',
                    ],
                    ['SAR', '12,000.00', '2,000.00', '10,000.00', '14,000.00', '12,000.00', '2,000.00', '85.7%'],
                ],
            ],
            [
                'type' => 'paragraph',
                'text' => 'Operational activity | النشاط التشغيلي',
                'style' => 'Heading1',
            ],
            [
                'type' => 'table',
                'rows' => $activityRows,
            ],
        ]);

        $archive = new ZipArchive;
        $this->assertTrue($archive->open($path));

        foreach ([
            'docProps/core.xml',
            'docProps/app.xml',
            'word/document.xml',
            'word/header1.xml',
            'word/footer1.xml',
            'word/settings.xml',
        ] as $part) {
            $this->assertNotFalse($archive->getFromName($part), "Missing {$part}");
        }

        $document = (string) $archive->getFromName('word/document.xml');
        $footer = (string) $archive->getFromName('word/footer1.xml');
        $archive->close();
        @unlink($path);

        $this->assertStringContainsString('<w:tblGrid>', $document);
        $this->assertStringContainsString('<w:tblHeader/>', $document);
        $this->assertStringContainsString('<w:keepNext/>', $document);
        $this->assertStringContainsString('<w:br w:type="page"/>', $document);
        $this->assertStringContainsString('<w:tblLayout w:type="fixed"/>', $document);
        $this->assertStringContainsString('w:orient="landscape"', $document);
        $this->assertStringContainsString('<w:headerReference', $document);
        $this->assertStringContainsString('<w:footerReference', $document);
        $this->assertStringNotContainsString('w:w="0"', $document);
        $this->assertStringContainsString('Property Operating Report', $document);
        $this->assertStringContainsString('تقرير تشغيل العقار', $document);
        $this->assertStringContainsString('12,000.00', $document);
        $this->assertStringContainsString('w:instr="PAGE"', $footer);
        $this->assertStringContainsString('w:instr="NUMPAGES"', $footer);
    }
}
