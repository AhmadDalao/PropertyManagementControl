<?php

namespace App\Modules\Reports\Actions;

use App\Modules\Exports\Support\SimpleDocx;
use App\Modules\Reports\Support\PropertyOperatingReportFilename;
use Symfony\Component\HttpFoundation\StreamedResponse;

final readonly class PropertyOperatingReportWordExport
{
    public function __construct(
        private SimpleDocx $documents,
        private PropertyOperatingReportFilename $filenames,
    ) {}

    /** @param array<string, mixed> $data */
    public function download(array $data): StreamedResponse
    {
        $path = $this->documents->create($this->blocks($data));
        $content = (string) file_get_contents($path);
        @unlink($path);

        return response()->streamDownload(
            static fn () => print ($content),
            $this->filenames->make($data, 'docx'),
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
        );
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<int, array{type:string,text?:string,style?:string,rows?:array<int,array<int,string>>}>
     */
    private function blocks(array $data): array
    {
        $property = $data['property'];
        $filters = $data['filters'];

        return [
            $this->paragraph('Property Operating Report | تقرير تشغيل العقار', 'Title'),
            $this->paragraph(
                $this->localized($property)
                .' · '.(string) $property['code'],
            ),
            $this->paragraph("Period / الفترة: {$filters['date_from']} - {$filters['date_to']}"),
            $this->paragraph('Property profile | ملف العقار', 'Heading1'),
            $this->table($this->profileRows($property)),
            $this->paragraph('Structure and occupancy | الهيكل والإشغال', 'Heading1'),
            $this->table($this->structureRows($property, $data['summary'])),
            $this->paragraph('Financial position | المركز المالي', 'Heading1'),
            $this->table($this->currencyRows($data['summary']['currencyTotals'])),
            $this->paragraph('Collection risks | مخاطر التحصيل', 'Heading1'),
            $this->table($this->arrearsRows($data['arrearsLeases'])),
            $this->paragraph('Posted payments | الدفعات المرحلة', 'Heading1'),
            $this->table($this->paymentRows($data['recentPayments'])),
            $this->paragraph('Posted expenses | المصاريف المرحلة', 'Heading1'),
            $this->table($this->expenseRows($data['recentExpenses'])),
            $this->paragraph('Maintenance backlog | طلبات الصيانة المتراكمة', 'Heading1'),
            $this->table($this->maintenanceRows($data['maintenanceBacklog'])),
            $this->paragraph('Operational activity | النشاط التشغيلي', 'Heading1'),
            $this->table($this->activityRows($data['operationalJournal'])),
        ];
    }

    /** @return array{type:string,text:string,style?:string} */
    private function paragraph(string $text, ?string $style = null): array
    {
        return array_filter([
            'type' => 'paragraph',
            'text' => $text,
            'style' => $style,
        ], static fn (mixed $value): bool => $value !== null);
    }

    /**
     * @param  array<int, array<int, string>>  $rows
     * @return array{type:string,rows:array<int,array<int,string>>}
     */
    private function table(array $rows): array
    {
        return ['type' => 'table', 'rows' => $rows];
    }

    /**
     * @param  array<string, mixed>  $property
     * @return array<int, array<int, string>>
     */
    private function profileRows(array $property): array
    {
        return [
            ['Field / البيان', 'Value / القيمة'],
            ['Property / العقار', $this->localized($property)],
            ['Code / الرمز', (string) $property['code']],
            ['Portfolio / المحفظة', $this->localized($property['portfolio'])],
            ['Owner / المالك', (string) ($property['owner']['name'] ?? '-')],
            ['Manager / المدير', (string) ($property['manager']['name'] ?? '-')],
            ['Address / العنوان', $this->localized([
                'title_en' => $property['address_en'] ?? null,
                'title_ar' => $property['address_ar'] ?? null,
            ])],
            ['Status / الحالة', $this->option((string) $property['status'])],
            ['Usage / الاستخدام', $this->usage((string) $property['usage_type'])],
            ['Valuation / التقييم', $this->money(
                $property['valuation_amount'],
                (string) $property['currency'],
            )],
        ];
    }

    /**
     * @param  array<string, mixed>  $property
     * @param  array<string, mixed>  $summary
     * @return array<int, array<int, string>>
     */
    private function structureRows(array $property, array $summary): array
    {
        $structure = $property['structure'];

        return [
            ['Metric / المؤشر', 'Value / القيمة'],
            ['Hierarchy records / سجلات الهيكل', (string) $structure['records']],
            ['Floors / الطوابق', (string) $structure['floors']],
            ['Units and spaces / الوحدات والمساحات', (string) $structure['units']],
            ['Rentable spaces / المساحات القابلة للتأجير', (string) $structure['rentable']],
            ['Occupied spaces / المساحات المشغولة', (string) $structure['occupied']],
            ['Vacant spaces / المساحات الشاغرة', (string) $structure['vacant']],
            ['Active tenants / المستأجرون النشطون', (string) $structure['active_tenants']],
            ['Occupancy / الإشغال', $this->percentage($summary['occupancyRate'])],
            ['Active leases / العقود النشطة', (string) $summary['activeLeases']],
            ['Open maintenance / الصيانة المفتوحة', (string) $summary['openRequests']],
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $positions
     * @return array<int, array<int, string>>
     */
    private function currencyRows(array $positions): array
    {
        $rows = [[
            'Currency / العملة',
            'Collected / المحصل',
            'Expenses / المصاريف',
            'Net / الصافي',
            'Scheduled due / المستحق',
            'Arrears / المتأخرات',
            'Contract balance / رصيد العقود',
            'Collection / التحصيل',
        ]];

        foreach ($positions as $position) {
            $currency = (string) $position['currency'];
            $rows[] = [
                $currency,
                $this->money($position['revenue'], $currency),
                $this->money($position['expenses'], $currency),
                $this->money($position['net'], $currency),
                $this->money($position['scheduledDue'], $currency),
                $this->money($position['arrears'], $currency),
                $this->money($position['contractBalance'], $currency),
                $this->percentage($position['collectionRate']),
            ];
        }

        return $rows;
    }

    /**
     * @param  array<int, array<string, mixed>>  $records
     * @return array<int, array<int, string>>
     */
    private function arrearsRows(array $records): array
    {
        $rows = [['Lease / العقد', 'Tenant / المستأجر', 'Space / الوحدة', 'Balance / الرصيد']];

        foreach ($records as $record) {
            $rows[] = [
                (string) $record['code'],
                (string) ($record['tenant'] ?? '-'),
                (string) ($record['asset'] ?? '-'),
                $this->money($record['arrears_amount'], (string) $record['currency']),
            ];
        }

        return $rows;
    }

    /**
     * @param  array<int, array<string, mixed>>  $records
     * @return array<int, array<int, string>>
     */
    private function paymentRows(array $records): array
    {
        $rows = [['Reference / المرجع', 'Tenant / المستأجر', 'Lease / العقد', 'Date / التاريخ', 'Amount / المبلغ']];

        foreach ($records as $record) {
            $rows[] = [
                (string) $record['reference'],
                (string) ($record['tenant'] ?? '-'),
                (string) ($record['lease'] ?? '-'),
                (string) ($record['received_on'] ?? '-'),
                $this->money($record['amount'], (string) $record['currency']),
            ];
        }

        return $rows;
    }

    /**
     * @param  array<int, array<string, mixed>>  $records
     * @return array<int, array<int, string>>
     */
    private function expenseRows(array $records): array
    {
        $rows = [['Expense / المصروف', 'Category / التصنيف', 'Space / الوحدة', 'Date / التاريخ', 'Amount / المبلغ']];

        foreach ($records as $record) {
            $rows[] = [
                (string) $record['title'],
                $this->option((string) $record['category']),
                (string) ($record['asset'] ?? '-'),
                (string) ($record['incurred_on'] ?? '-'),
                $this->money($record['amount'], (string) $record['currency']),
            ];
        }

        return $rows;
    }

    /**
     * @param  array<int, array<string, mixed>>  $records
     * @return array<int, array<int, string>>
     */
    private function maintenanceRows(array $records): array
    {
        $rows = [['Request / الطلب', 'Space / الوحدة', 'Tenant / المستأجر', 'Status / الحالة', 'Priority / الأولوية']];

        foreach ($records as $record) {
            $rows[] = [
                (string) $record['title'],
                (string) ($record['asset'] ?? '-'),
                (string) ($record['tenant'] ?? '-'),
                $this->option((string) $record['status']),
                $this->option((string) $record['priority']),
            ];
        }

        return $rows;
    }

    /**
     * @param  array<int, array<string, mixed>>  $records
     * @return array<int, array<int, string>>
     */
    private function activityRows(array $records): array
    {
        $rows = [['Date / التاريخ', 'Type / النوع', 'Record / السجل', 'Context / السياق', 'By / بواسطة']];

        foreach ($records as $record) {
            $rows[] = [
                (string) ($record['occurred_at'] ?? '-'),
                (string) $record['type_label'],
                (string) $record['title'],
                (string) $record['subtitle'],
                (string) $record['actor'],
            ];
        }

        return $rows;
    }

    /** @param array<string, mixed> $record */
    private function localized(array $record): string
    {
        return collect([
            $record['title_en'] ?? $record['name_en'] ?? null,
            $record['title_ar'] ?? $record['name_ar'] ?? null,
        ])->filter()->join(' / ');
    }

    private function option(string $value): string
    {
        $key = "app.status.{$value}";

        return trans()->has($key)
            ? trans($key, locale: 'en').' / '.trans($key, locale: 'ar')
            : str($value)->replace('_', ' ')->headline()->toString();
    }

    private function usage(string $value): string
    {
        $key = "app.assets.usages.{$value}";

        return trans($key, locale: 'en').' / '.trans($key, locale: 'ar');
    }

    private function money(float|int|string $amount, string $currency): string
    {
        return number_format((float) $amount, 2).' '.$currency;
    }

    private function percentage(float|int|string $value): string
    {
        return number_format((float) $value, 1).'%';
    }
}
