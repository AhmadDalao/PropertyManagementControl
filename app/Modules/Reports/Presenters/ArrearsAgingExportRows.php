<?php

namespace App\Modules\Reports\Presenters;

final class ArrearsAgingExportRows
{
    /**
     * @param  array<int, array{label:string,value:string}>  $scope
     * @return array<int, array<int, mixed>>
     */
    public function scope(array $scope): array
    {
        $rows = [['Scope / النطاق', 'Value / القيمة']];

        foreach ($scope as $item) {
            $rows[] = [(string) $item['label'], (string) $item['value']];
        }

        return $rows;
    }

    /**
     * @param  array<int, array<string, mixed>>  $positions
     * @return array<int, array<int, mixed>>
     */
    public function currencies(array $positions): array
    {
        $rows = [[
            'Currency / العملة',
            'Installments / الأقساط',
            'Leases / العقود',
            '1-30 days / 1-30 يوماً',
            '31-60 days / 31-60 يوماً',
            '61-90 days / 61-90 يوماً',
            'Over 90 / أكثر من 90',
            'Total / الإجمالي',
        ]];

        foreach ($positions as $position) {
            $rows[] = [
                $position['currency'],
                $position['installment_count'],
                $position['lease_count'],
                $position['days_1_30'],
                $position['days_31_60'],
                $position['days_61_90'],
                $position['over_90'],
                $position['total'],
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
            'Portfolio EN',
            'Portfolio AR',
            'Property EN',
            'Property AR',
            'Property code / رمز العقار',
            'Space EN',
            'Space AR',
            'Space code / رمز الوحدة',
            'Tenant / المستأجر',
            'Lease / العقد',
            'Installment / القسط',
            'Due date / الاستحقاق',
            'Days overdue / أيام التأخير',
            'Aging bucket / فئة التأخير',
            'Amount due / المستحق',
            'Amount paid / المسدد',
            'Outstanding / المتبقي',
            'Currency / العملة',
            'Follow-up / المتابعة',
            'Assignee / المسؤول',
            'Next follow-up / المتابعة القادمة',
        ]];

        foreach ($records as $record) {
            $followUp = $record['follow_up'];
            $rows[] = [
                $record['portfolio']['name_en'] ?? null,
                $record['portfolio']['name_ar'] ?? null,
                $record['property']['title_en'] ?? null,
                $record['property']['title_ar'] ?? null,
                $record['property']['code'] ?? null,
                $record['asset']['title_en'] ?? null,
                $record['asset']['title_ar'] ?? null,
                $record['asset']['code'] ?? null,
                $record['tenant']['name'] ?? null,
                $record['lease']['code'] ?? null,
                $record['label'],
                $record['due_date'],
                $record['days_overdue'],
                $this->bucket((string) $record['bucket']),
                $record['amount_due'],
                $record['amount_paid'],
                $record['outstanding_amount'],
                $record['currency'],
                $this->followUp((string) $followUp['state']),
                $followUp['assigned_to']['name'] ?? null,
                $followUp['next_follow_up_on'] ?? null,
            ];
        }

        return $rows;
    }

    private function bucket(string $bucket): string
    {
        return trans("app.reports.aging_bucket_{$bucket}", locale: 'en')
            .' / '
            .trans("app.reports.aging_bucket_{$bucket}", locale: 'ar');
    }

    private function followUp(string $state): string
    {
        return trans("app.rent_collection.follow_up_state_{$state}", locale: 'en')
            .' / '
            .trans("app.rent_collection.follow_up_state_{$state}", locale: 'ar');
    }
}
