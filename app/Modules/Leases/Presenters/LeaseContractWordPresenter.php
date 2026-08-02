<?php

namespace App\Modules\Leases\Presenters;

use App\Models\Asset;
use App\Models\Lease;
use DateTimeInterface;

final class LeaseContractWordPresenter
{
    /**
     * @return array<int, array{
     *     type:string,
     *     text?:string,
     *     style?:string,
     *     rows?:array<int, array<int, string>>
     * }>
     */
    public function present(Lease $lease): array
    {
        $asset = $lease->leaseable instanceof Asset ? $lease->leaseable : null;

        return [
            $this->paragraph('Lease Contract | عقد إيجار', 'Title'),
            $this->paragraph(
                'Editable draft generated from Property Management Control. '
                .'The portfolio-approved legal clauses and signed PDF govern the final agreement.'
                .' | مسودة قابلة للتعديل تم إنشاؤها من نظام إدارة العقارات. '
                .'تسري البنود القانونية المعتمدة ونسخة PDF الموقعة على الاتفاق النهائي.',
            ),
            $this->paragraph('Contract details | بيانات العقد', 'Heading1'),
            $this->table([
                ['Field / البيان', 'Value / القيمة'],
                ['Contract / العقد', $lease->code],
                [
                    'Portfolio / المحفظة',
                    $this->bilingual(
                        $lease->portfolio?->name_en,
                        $lease->portfolio?->name_ar,
                    ),
                ],
                ['Generated / تاريخ الإنشاء', now()->format('Y-m-d H:i')],
            ]),
            $this->paragraph('Parties | أطراف العقد', 'Heading1'),
            $this->table([
                ['Party / الطرف', 'Name / الاسم', 'Identity or contact / الهوية أو التواصل'],
                [
                    'Landlord / المؤجر',
                    $lease->portfolio?->owner?->name
                        ?: $lease->portfolio?->name_en
                        ?: '-',
                    $this->contact(
                        $lease->portfolio?->contact_phone,
                        $lease->portfolio?->contact_email,
                    ),
                ],
                [
                    'Tenant / المستأجر',
                    $lease->tenantProfile?->user?->name ?: '-',
                    $this->contact(
                        $lease->tenantProfile?->national_id,
                        $lease->tenantProfile?->user?->phone,
                        $lease->tenantProfile?->user?->email,
                    ),
                ],
            ]),
            $this->paragraph('Property and period | العقار والمدة', 'Heading1'),
            $this->table([
                ['Field / البيان', 'Value / القيمة'],
                [
                    'Property / العقار',
                    $this->bilingual(
                        $asset?->title_en,
                        $asset?->title_ar,
                    ),
                ],
                ['Asset code / رمز الأصل', $asset?->code ?: '-'],
                [
                    'Address / العنوان',
                    $this->bilingual(
                        $asset?->address,
                        $asset?->address_ar,
                    ),
                ],
                ['Start date / البداية', $this->date($lease->started_at)],
                ['End date / النهاية', $this->date($lease->ends_at)],
                ['Signed date / التوقيع', $this->date($lease->signed_at)],
                ['Managed by / المسؤول', $lease->managedBy?->name ?: '-'],
            ]),
            $this->paragraph('Financial terms | الشروط المالية', 'Heading1'),
            $this->table([
                [
                    'Rent / الإيجار',
                    'Deposit / التأمين',
                    'Tax / الضريبة',
                    'Discount / الخصم',
                    'Frequency / التكرار',
                    'Billing day / يوم الفوترة',
                ],
                [
                    $this->money($lease->rent_amount, $lease->currency),
                    $this->money($lease->deposit_amount, $lease->currency),
                    $this->money($lease->tax_amount, $lease->currency),
                    $this->money($lease->discount_amount, $lease->currency),
                    ucfirst((string) $lease->payment_frequency),
                    $lease->billing_day ? (string) $lease->billing_day : '-',
                ],
            ]),
            $this->paragraph('Installment schedule | جدول الأقساط', 'Heading1'),
            $this->table($this->installmentRows($lease)),
            $this->paragraph('Approved terms | البنود المعتمدة', 'Heading1'),
            $this->paragraph(
                'English terms: '
                .($this->terms($lease, 'en')
                    ?: 'No portfolio-specific legal terms were entered in the system.'),
            ),
            $this->paragraph(
                'البنود العربية: '
                .($this->terms($lease, 'ar')
                    ?: 'لم تتم إضافة بنود قانونية خاصة بالمحفظة في النظام.'),
            ),
            $this->paragraph('Signatures | التوقيعات', 'Heading1'),
            $this->table([
                ['Party / الطرف', 'Name / الاسم', 'Signature / التوقيع', 'Date / التاريخ'],
                ['Landlord / المؤجر', '', '', ''],
                ['Tenant / المستأجر', '', '', ''],
                ['Manager / المدير', '', '', ''],
            ]),
        ];
    }

    /**
     * @return array<int, array<int, string>>
     */
    private function installmentRows(Lease $lease): array
    {
        $rows = [[
            '#',
            'Installment / القسط',
            'Period / الفترة',
            'Due date / الاستحقاق',
            'Amount / المبلغ',
        ]];

        foreach ($lease->installments as $installment) {
            $rows[] = [
                (string) $installment->sequence,
                (string) $installment->label,
                $this->date($installment->period_start)
                    .' - '
                    .$this->date($installment->period_end),
                $this->date($installment->due_date),
                $this->money($installment->amount_due, $lease->currency),
            ];
        }

        return $rows;
    }

    /** @return array{type:string,text:string,style?:string} */
    private function paragraph(string $text, ?string $style = null): array
    {
        $paragraph = ['type' => 'paragraph', 'text' => $text];

        if ($style !== null) {
            $paragraph['style'] = $style;
        }

        return $paragraph;
    }

    /**
     * @param  array<int, array<int, string>>  $rows
     * @return array{type:string,rows:array<int, array<int, string>>}
     */
    private function table(array $rows): array
    {
        return ['type' => 'table', 'rows' => $rows];
    }

    private function bilingual(?string $english, ?string $arabic): string
    {
        $values = array_values(array_filter(
            [trim((string) $english), trim((string) $arabic)],
            static fn (string $value): bool => $value !== '',
        ));

        return $values === [] ? '-' : implode(' | ', $values);
    }

    private function contact(?string ...$values): string
    {
        $values = array_values(array_filter(
            array_map(static fn (?string $value): string => trim((string) $value), $values),
            static fn (string $value): bool => $value !== '',
        ));

        return $values === [] ? '-' : implode(' | ', $values);
    }

    private function date(mixed $date): string
    {
        return $date instanceof DateTimeInterface ? $date->format('Y-m-d') : '-';
    }

    private function money(mixed $amount, ?string $currency): string
    {
        return number_format((float) $amount, 2).' '.($currency ?: 'SAR');
    }

    private function terms(Lease $lease, string $locale): string
    {
        return trim((string) data_get($lease->terms_json, $locale));
    }
}
