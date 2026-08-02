<?php

namespace App\Modules\LeaseRenewals\Presenters;

final class LeaseRenewalExportRows
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
     * @param  array<int, array<string, mixed>>  $positions
     * @return array<int, array<int, mixed>>
     */
    public function currencies(array $positions): array
    {
        $rows = [[
            'Currency / العملة',
            'Leases / العقود',
            'Action required / يتطلب إجراء',
            'Prepared / جاهز',
            'Expired / منتهي',
            'Outstanding / المتبقي',
        ]];

        foreach ($positions as $position) {
            $rows[] = [
                $position['currency'],
                $position['leases'],
                $position['attention'],
                $position['prepared'],
                $position['expired'],
                $position['outstanding'],
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
            'Property / العقار',
            'Unit / الوحدة',
            'Tenant / المستأجر',
            'Lease / العقد',
            'Status / الحالة',
            'End date / تاريخ الانتهاء',
            'Days remaining / الأيام المتبقية',
            'Contact due / موعد التواصل',
            'Renewal state / حالة التجديد',
            'Renewal contract / عقد التجديد',
            'Outstanding / المتبقي',
            'Currency / العملة',
        ]];

        foreach ($records as $record) {
            $rows[] = [
                $this->localized($record['property'] ?? null),
                $this->localized($record['asset'] ?? null),
                $record['tenant']['name'] ?? null,
                $record['code'],
                $this->label('app.status.', (string) $record['status']),
                $record['ends_at'],
                $record['days_remaining'],
                $record['contact_due_on'],
                $this->label('app.lease_renewals.state_', (string) $record['renewal_state']),
                $record['renewal']['code'] ?? null,
                $record['outstanding_amount'],
                $record['currency'],
            ];
        }

        return $rows;
    }

    /** @param array<string, mixed>|null $record */
    private function localized(?array $record): ?string
    {
        if ($record === null) {
            return null;
        }

        return implode(' / ', array_filter([
            $record['title_en'] ?? null,
            $record['title_ar'] ?? null,
        ]));
    }

    private function label(string $prefix, string $value): string
    {
        return trans($prefix.$value, locale: 'en')
            .' / '
            .trans($prefix.$value, locale: 'ar');
    }
}
