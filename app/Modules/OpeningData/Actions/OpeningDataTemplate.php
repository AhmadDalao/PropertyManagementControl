<?php

namespace App\Modules\OpeningData\Actions;

use App\Models\User;
use App\Modules\Exports\Support\XlsxWorkbook;
use App\Modules\OpeningData\Support\OpeningDataAccess;
use App\Modules\OpeningData\Support\OpeningDataWorkbookSchema;

final class OpeningDataTemplate
{
    public function __construct(
        private readonly OpeningDataAccess $access,
        private readonly OpeningDataWorkbookSchema $schema,
        private readonly XlsxWorkbook $workbooks,
    ) {}

    public function create(User $actor): string
    {
        $this->access->ensureOperator($actor);
        $sheets = [
            [
                'name' => 'Instructions',
                'rows' => $this->instructions(),
            ],
        ];

        foreach ($this->schema->sheetNames() as $sheet) {
            $sheets[] = [
                'name' => $sheet,
                'rows' => [$this->schema->headers($sheet)],
            ];
        }

        return $this->workbooks->createSheets($sheets);
    }

    /** @return array<int, array<int, mixed>> */
    private function instructions(): array
    {
        return [
            [
                'Topic',
                'English',
                'العربية',
            ],
            [
                'Start / البدء',
                'Complete the four data sheets, then upload this .xlsx file for validation.',
                'أكمل أوراق البيانات الأربع، ثم ارفع ملف .xlsx للتحقق منه.',
            ],
            [
                'Order / الترتيب',
                'Assets are created first, then tenants, leases, and payments.',
                'يتم إنشاء الأصول أولاً، ثم المستأجرين والعقود والمدفوعات.',
            ],
            [
                'Codes / الرموز',
                'Asset, lease, and payment reference values must be unique.',
                'يجب أن تكون رموز الأصول والعقود ومراجع المدفوعات فريدة.',
            ],
            [
                'References / المراجع',
                'Use asset_code, tenant_email, lease_code, and parent_code to connect rows.',
                'استخدم asset_code وtenant_email وlease_code وparent_code لربط الصفوف.',
            ],
            ['Dates / التواريخ', 'Use YYYY-MM-DD.', 'استخدم التنسيق YYYY-MM-DD.'],
            ['Booleans / القيم المنطقية', 'Use 1 or 0 for rentable.', 'استخدم 1 أو 0 لحقل rentable.'],
            ['Asset types / أنواع الأصول', 'property, building, floor, unit, space', 'property, building, floor, unit, space'],
            ['Usage types / أنواع الاستخدام', 'residential, commercial, mixed, personal', 'residential, commercial, mixed, personal'],
            ['Lease status / حالة العقد', 'active or draft', 'active أو draft'],
            ['Payment methods / طرق الدفع', 'bank_transfer, cash, card', 'bank_transfer, cash, card'],
            ['Payment types / أنواع المدفوعات', 'rent, deposit, fee', 'rent, deposit, fee'],
            [
                'Safety / الأمان',
                'Uploading only previews data. Nothing is written until you confirm the import.',
                'يعرض رفع الملف معاينة فقط. لن تُحفظ أي بيانات حتى تأكيد الاستيراد.',
            ],
        ];
    }
}
