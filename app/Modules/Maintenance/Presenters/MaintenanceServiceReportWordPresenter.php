<?php

namespace App\Modules\Maintenance\Presenters;

use App\Models\MaintenanceWorkOrder;
use App\Modules\Maintenance\Data\MaintenanceDetailData;
use Carbon\CarbonInterface;

final class MaintenanceServiceReportWordPresenter
{
    /**
     * @return array<int, array{type:string,text?:string,style?:string,rows?:array<int,array<int,string>>}>
     */
    public function present(MaintenanceDetailData $data): array
    {
        $request = $data->request;

        return [
            $this->paragraph('Maintenance Service Report | تقرير خدمة الصيانة', 'Title'),
            $this->paragraph('Editable management working copy | نسخة عمل قابلة للتعديل للإدارة'),
            $this->paragraph("Request / الطلب: #{$request->id}"),
            $this->paragraph('Request context | بيانات الطلب', 'Heading1'),
            $this->table([
                ['Field / البيان', 'Value / القيمة'],
                ['Property / العقار', $this->bilingual(
                    $request->asset?->title_en,
                    $request->asset?->title_ar,
                )],
                ['Tenant / المستأجر', $this->value($request->tenantProfile?->user?->name)],
                ['Lease / العقد', $this->value($request->lease?->code)],
                ['Category / الفئة', $this->status($request->category)],
                ['Priority / الأولوية', $this->status($request->priority)],
                ['Status / الحالة', $this->status($request->status)],
                ['Requested / تاريخ الطلب', $this->date($request->requested_at)],
                ['Due / الاستحقاق', $this->date($request->due_at)],
                ['Resolved / تاريخ الحل', $this->date($request->resolved_at)],
            ]),
            $this->paragraph('Issue and resolution | المشكلة والحل', 'Heading1'),
            $this->table([
                ['Issue / المشكلة', $request->title],
                ['Description / الوصف', $request->description],
                ['Resolution / ملخص الحل', $this->value($request->resolution_summary)],
                ['Assigned to / المسؤول', $this->value($request->assignedTo?->name)],
                ['Resolved by / أغلقه', $this->value($request->resolvedBy?->name)],
                ['Internal notes / ملاحظات داخلية', $this->value($request->internal_notes)],
            ]),
            $this->paragraph('Service visits | زيارات الخدمة', 'Heading1'),
            $this->table($this->workOrderRows($data)),
            $this->paragraph('Recorded cost | التكلفة المسجلة', 'Heading1'),
            $this->table([
                ['Posted maintenance expense / مصروف الصيانة المرحل', number_format($data->postedExpenseTotal, 2).' SAR'],
            ]),
            $this->paragraph('Tenant sign-off | اعتماد المستأجر', 'Heading1'),
            $this->table([
                ['Confirmation / التأكيد', $request->tenant_confirmed_at
                    ? 'Confirmed / مؤكد'
                    : 'Pending / بانتظار التأكيد'],
                ['Confirmed at / تاريخ التأكيد', $this->date($request->tenant_confirmed_at)],
                ['Tenant note / ملاحظة المستأجر', $this->value($request->tenant_confirmation_note)],
            ]),
            $this->paragraph('Completion approval | اعتماد الإغلاق', 'Heading1'),
            $this->table([
                ['Management signature / توقيع الإدارة', '____________________________'],
                ['Tenant signature / توقيع المستأجر', '____________________________'],
                ['Date / التاريخ', '____________________________'],
            ]),
            $this->paragraph(
                'Use the PDF service report as the authoritative system record. | استخدم تقرير الخدمة بصيغة PDF كسجل النظام المعتمد.',
            ),
        ];
    }

    /** @return array<int, array<int, string>> */
    private function workOrderRows(MaintenanceDetailData $data): array
    {
        $rows = [[
            'Reference / المرجع',
            'Contractor / المقاول',
            'Schedule / الموعد',
            'Status / الحالة',
            'Final amount / المبلغ النهائي',
        ]];

        foreach ($data->workOrders as $workOrder) {
            /** @var MaintenanceWorkOrder $workOrder */
            $rows[] = [
                $workOrder->reference_code,
                $this->value($workOrder->vendor_name),
                $this->date($workOrder->scheduled_at),
                $this->status($workOrder->status),
                $workOrder->final_amount === null
                    ? '-'
                    : number_format($workOrder->final_amount, 2).' '.$workOrder->currency,
            ];
        }

        return count($rows) > 1
            ? $rows
            : [...$rows, ['-', 'No service visits / لا توجد زيارات خدمة', '-', '-', '-']];
    }

    /** @return array{type:string,text:string,style?:string} */
    private function paragraph(string $text, ?string $style = null): array
    {
        return array_filter(
            ['type' => 'paragraph', 'text' => $text, 'style' => $style],
            fn ($value): bool => $value !== null,
        );
    }

    /**
     * @param  array<int, array<int, string>>  $rows
     * @return array{type:string,rows:array<int,array<int,string>>}
     */
    private function table(array $rows): array
    {
        return ['type' => 'table', 'rows' => $rows];
    }

    private function bilingual(?string $english, ?string $arabic): string
    {
        return $this->value($english).' | '.$this->value($arabic);
    }

    private function status(string $status): string
    {
        return trans("app.status.{$status}", locale: 'en')
            .' / '.trans("app.status.{$status}", locale: 'ar');
    }

    private function date(?CarbonInterface $date): string
    {
        return $date?->format('Y-m-d H:i') ?? '-';
    }

    private function value(mixed $value): string
    {
        return filled($value) ? (string) $value : '-';
    }
}
