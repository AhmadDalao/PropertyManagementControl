<?php

namespace App\Modules\Reports\Actions;

use App\Modules\Exports\Support\XlsxWorkbook;
use App\Modules\Reports\Support\PropertyOperatingReportFilename;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final readonly class PropertyOperatingReportWorkbookExport
{
    public function __construct(
        private XlsxWorkbook $workbook,
        private PropertyOperatingReportFilename $filenames,
    ) {}

    /** @param array<string, mixed> $data */
    public function download(array $data): BinaryFileResponse
    {
        $path = $this->workbook->createSheets([
            ['name' => 'Overview', 'rows' => $this->overviewRows($data)],
            ['name' => 'Collections', 'rows' => $this->collectionRows($data)],
            ['name' => 'Costs', 'rows' => $this->costRows($data)],
            ['name' => 'Maintenance', 'rows' => $this->maintenanceRows($data)],
            ['name' => 'Activity', 'rows' => $this->activityRows($data)],
        ]);

        return response()->download(
            $path,
            $this->filenames->make($data, 'xlsx'),
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        )->deleteFileAfterSend(true);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<int, array<int, mixed>>
     */
    private function overviewRows(array $data): array
    {
        $property = $data['property'];
        $structure = $property['structure'];
        $summary = $data['summary'];
        $rows = [
            ['Property Operating Report | تقرير تشغيل العقار'],
            ['Date from / التاريخ من', $data['filters']['date_from']],
            ['Date to / التاريخ إلى', $data['filters']['date_to']],
            ['Generated / تاريخ الإصدار', now()->toIso8601String()],
            [],
            ['Property profile | ملف العقار'],
            ['Field / البيان', 'English / الإنجليزية', 'Arabic / العربية', 'Value / القيمة'],
            ['Property / العقار', $property['title_en'], $property['title_ar']],
            ['Code / الرمز', null, null, $property['code']],
            ['Portfolio / المحفظة', $property['portfolio']['name_en'], $property['portfolio']['name_ar'], $property['portfolio']['code']],
            ['Owner / المالك', null, null, $property['owner']['name'] ?? null],
            ['Manager / المدير', null, null, $property['manager']['name'] ?? null],
            ['Address / العنوان', $property['address_en'], $property['address_ar']],
            ['Status / الحالة', null, null, $this->option((string) $property['status'])],
            ['Usage / الاستخدام', null, null, $this->usage((string) $property['usage_type'])],
            ['Valuation / التقييم', null, null, $property['valuation_amount'], $property['currency']],
            [],
            ['Structure and occupancy | الهيكل والإشغال'],
            ['Metric / المؤشر', 'Value / القيمة'],
            ['Hierarchy records / سجلات الهيكل', $structure['records']],
            ['Floors / الطوابق', $structure['floors']],
            ['Units and spaces / الوحدات والمساحات', $structure['units']],
            ['Rentable spaces / المساحات القابلة للتأجير', $structure['rentable']],
            ['Occupied spaces / المساحات المشغولة', $structure['occupied']],
            ['Vacant spaces / المساحات الشاغرة', $structure['vacant']],
            ['Active tenants / المستأجرون النشطون', $structure['active_tenants']],
            ['Occupancy / الإشغال', $summary['occupancyRate']],
            ['Active leases / العقود النشطة', $summary['activeLeases']],
            ['Leases in arrears / العقود المتأخرة', $summary['leasesInArrears']],
            ['Open maintenance / الصيانة المفتوحة', $summary['openRequests']],
            ['Resolved maintenance / الصيانة المحلولة', $summary['resolvedRequests']],
            [],
            ['Currency positions | المراكز حسب العملة'],
            [
                'Currency / العملة',
                'Collected / المحصل',
                'Expenses / المصاريف',
                'Net / الصافي',
                'Scheduled due / المستحق',
                'Scheduled paid / المسدد المجدول',
                'Collection rate / نسبة التحصيل',
                'Arrears / المتأخرات',
                'Contract balance / رصيد العقود',
            ],
        ];

        foreach ($summary['currencyTotals'] as $position) {
            $rows[] = [
                $position['currency'],
                $position['revenue'],
                $position['expenses'],
                $position['net'],
                $position['scheduledDue'],
                $position['scheduledPaid'],
                $position['collectionRate'],
                $position['arrears'],
                $position['contractBalance'],
            ];
        }

        return $rows;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<int, array<int, mixed>>
     */
    private function collectionRows(array $data): array
    {
        $rows = [
            ['Property collections | تحصيل العقار'],
            ['Date from / التاريخ من', $data['filters']['date_from']],
            ['Date to / التاريخ إلى', $data['filters']['date_to']],
            [],
            ['Contracts in arrears | العقود المتأخرة'],
            ['Lease / العقد', 'Tenant / المستأجر', 'Space / الوحدة', 'End / النهاية', 'Balance / الرصيد', 'Currency / العملة'],
        ];

        foreach ($data['arrearsLeases'] as $record) {
            $rows[] = [
                $record['code'],
                $record['tenant'],
                $record['asset'],
                $record['ends_at'],
                $record['arrears_amount'],
                $record['currency'],
            ];
        }

        $rows[] = [];
        $rows[] = ['Posted payments | الدفعات المرحلة'];
        $rows[] = ['Reference / المرجع', 'Tenant / المستأجر', 'Lease / العقد', 'Date / التاريخ', 'Amount / المبلغ', 'Currency / العملة'];

        foreach ($data['recentPayments'] as $record) {
            $rows[] = [
                $record['reference'],
                $record['tenant'],
                $record['lease'],
                $record['received_on'],
                $record['amount'],
                $record['currency'],
            ];
        }

        return $rows;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<int, array<int, mixed>>
     */
    private function costRows(array $data): array
    {
        $rows = [
            ['Property costs | تكاليف العقار'],
            ['Date from / التاريخ من', $data['filters']['date_from']],
            ['Date to / التاريخ إلى', $data['filters']['date_to']],
            [],
            ['Expense / المصروف', 'Category / التصنيف', 'Space / الوحدة', 'Date / التاريخ', 'Amount / المبلغ', 'Currency / العملة'],
        ];

        foreach ($data['recentExpenses'] as $record) {
            $rows[] = [
                $record['title'],
                $this->option((string) $record['category']),
                $record['asset'],
                $record['incurred_on'],
                $record['amount'],
                $record['currency'],
            ];
        }

        return $rows;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<int, array<int, mixed>>
     */
    private function maintenanceRows(array $data): array
    {
        $rows = [
            ['Maintenance backlog | طلبات الصيانة المتراكمة'],
            ['Date from / التاريخ من', $data['filters']['date_from']],
            ['Date to / التاريخ إلى', $data['filters']['date_to']],
            [],
            ['ID', 'Request / الطلب', 'Space / الوحدة', 'Tenant / المستأجر', 'Status / الحالة', 'Priority / الأولوية', 'Opened / تاريخ الفتح'],
        ];

        foreach ($data['maintenanceBacklog'] as $record) {
            $rows[] = [
                $record['id'],
                $record['title'],
                $record['asset'],
                $record['tenant'],
                $this->option((string) $record['status']),
                $this->option((string) $record['priority']),
                $record['created_at'],
            ];
        }

        return $rows;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<int, array<int, mixed>>
     */
    private function activityRows(array $data): array
    {
        $rows = [
            ['Operational activity | النشاط التشغيلي'],
            ['Date from / التاريخ من', $data['filters']['date_from']],
            ['Date to / التاريخ إلى', $data['filters']['date_to']],
            [],
            ['Date / التاريخ', 'Type / النوع', 'Record / السجل', 'Context / السياق', 'Performed by / بواسطة', 'Amount / المبلغ', 'Currency / العملة'],
        ];

        foreach ($data['operationalJournal'] as $record) {
            $rows[] = [
                $record['occurred_at'],
                $record['type_label'],
                $record['title'],
                $record['subtitle'],
                $record['actor'],
                $record['amount'],
                $record['currency'],
            ];
        }

        return $rows;
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
}
