<?php

namespace App\Modules\ActionCenter\Presenters;

final class ActionCenterExportRows
{
    /**
     * @param  array<int, array{label:string,value:string}>  $scope
     * @return array<int, array<int, mixed>>
     */
    public function scope(array $scope): array
    {
        $rows = [['Scope / النطاق', 'Value / القيمة']];

        foreach ($scope as $item) {
            $rows[] = [$item['label'], $item['value']];
        }

        return $rows;
    }

    /**
     * @param  array<string, int>  $summary
     * @return array<int, array<int, mixed>>
     */
    public function summary(array $summary): array
    {
        return [
            [
                'All / الكل',
                'Critical / حرج',
                'High / عالٍ',
                'Normal / عادي',
                'Unassigned / غير مسند',
            ],
            [
                $summary['total'],
                $summary['critical'],
                $summary['high'],
                $summary['normal'],
                $summary['unassigned'],
            ],
        ];
    }

    /**
     * @param  array<int, array{type:string,count:int}>  $positions
     * @return array<int, array<int, mixed>>
     */
    public function types(array $positions): array
    {
        $rows = [['Work type / نوع العمل', 'Items / الإجراءات']];

        foreach ($positions as $position) {
            $rows[] = [
                $this->label('app.action_center.type_', $position['type']),
                $position['count'],
            ];
        }

        return $rows;
    }

    /**
     * @param  array<int, array{currency:string,count:int,amount:float}>  $positions
     * @return array<int, array<int, mixed>>
     */
    public function currencies(array $positions): array
    {
        $rows = [[
            'Currency / العملة',
            'Items / الإجراءات',
            'Amount / المبلغ',
        ]];

        foreach ($positions as $position) {
            $rows[] = [
                $position['currency'],
                $position['count'],
                $position['amount'],
            ];
        }

        return $rows;
    }

    /**
     * @param  array<int, array<string, mixed>>  $records
     * @return array<int, array<int, mixed>>
     */
    public function records(array $records): array
    {
        $rows = [[
            'Priority / الأولوية',
            'Type / النوع',
            'Record / السجل',
            'Tenant / المستأجر',
            'Property / العقار',
            'Status / الحالة',
            'Due / الاستحقاق',
            'Responsible / المسؤول',
            'Amount / المبلغ',
            'Currency / العملة',
        ]];

        foreach ($records as $record) {
            $rows[] = [
                $this->label('app.action_center.priority_', (string) $record['priority']),
                $this->label('app.action_center.type_', (string) $record['type']),
                $record['title'],
                $record['tenant'] ?? null,
                $this->localized($record['asset'] ?? null, 'title'),
                $this->label('app.action_center.status_', (string) $record['status']),
                $record['due_on'] ?? null,
                $record['assigned_to']['name'] ?? null,
                $record['amount'] ?? null,
                $record['currency'] ?? null,
            ];
        }

        return $rows;
    }

    /** @param array<string, mixed>|null $record */
    private function localized(?array $record, string $prefix): ?string
    {
        if ($record === null) {
            return null;
        }

        return implode(' / ', array_filter([
            $record[$prefix.'_en'] ?? null,
            $record[$prefix.'_ar'] ?? null,
        ]));
    }

    private function label(string $prefix, string $value): string
    {
        return trans($prefix.$value, locale: 'en')
            .' / '
            .trans($prefix.$value, locale: 'ar');
    }
}
