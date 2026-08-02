<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Arrears aging</title>
    @include('pdf.partials.document-styles')
    <style>
        .aging-table { font-size: 7.2px; }
        .aging-table th, .aging-table td { padding: 5px 4px; vertical-align: top; }
        .aging-muted { color: #6d6559; font-size: 6.7px; }
        .aging-risk { color: #9d2a20; font-weight: 700; }
        .aging-summary td { width: 25%; }
        .aging-limit { margin: 0 0 10px; padding: 8px 10px; border: 1px solid #e3c986; background: #fff8e6; color: #5f4b18; font-size: 8px; }
    </style>
</head>
<body>
    @php
        $money = static fn ($value, $currency) => number_format((float) $value, 2).' '.$currency;
        $localized = static fn ($record, $prefix = 'title') => implode(' / ', array_filter([
            $record[$prefix.'_en'] ?? null,
            $record[$prefix.'_ar'] ?? null,
        ]));
    @endphp

    <table class="document-header">
        <tr>
            <td>
                <span class="brand-mark">PMC</span>
                <h1 class="document-title">Arrears Aging</h1>
                <div class="document-title-ar">تحليل أعمار المتأخرات</div>
            </td>
            <td class="document-meta">
                <div><span>Snapshot / الحالة الحالية</span><strong>{{ today()->toDateString() }}</strong></div>
                <div><span>Overdue lines / الأقساط المتأخرة</span><strong>{{ $data['recordTotal'] ?? count($data['records']) }}</strong></div>
                <div><span>Generated / تاريخ الإصدار</span><strong>{{ now()->format('Y-m-d H:i') }}</strong></div>
            </td>
        </tr>
    </table>

    <section class="section">
        <h2 class="section-title clearfix">Applied scope <span>النطاق المطبق</span></h2>
        <table class="two-column aging-summary"><tr>
            @foreach($data['scope'] as $item)
                <td><div class="card"><div class="pair"><span>{{ $item['label'] }}</span><strong>{{ $item['value'] }}</strong></div></div></td>
            @endforeach
        </tr></table>
    </section>

    <section class="section">
        <h2 class="section-title clearfix">Aging by currency <span>أعمار المتأخرات حسب العملة</span></h2>
        <table class="data">
            <thead><tr>
                <th>Currency / العملة</th>
                <th>Lines / الأقساط</th>
                <th>Leases / العقود</th>
                <th class="amount">1-30</th>
                <th class="amount">31-60</th>
                <th class="amount">61-90</th>
                <th class="amount">&gt; 90</th>
                <th class="amount">Total / الإجمالي</th>
            </tr></thead>
            <tbody>
                @forelse($data['currencyPositions'] as $position)
                    <tr>
                        <td>{{ $position['currency'] }}</td>
                        <td>{{ $position['installment_count'] }}</td>
                        <td>{{ $position['lease_count'] }}</td>
                        <td class="amount">{{ $money($position['days_1_30'], $position['currency']) }}</td>
                        <td class="amount">{{ $money($position['days_31_60'], $position['currency']) }}</td>
                        <td class="amount">{{ $money($position['days_61_90'], $position['currency']) }}</td>
                        <td class="amount">{{ $money($position['over_90'], $position['currency']) }}</td>
                        <td class="amount aging-risk">{{ $money($position['total'], $position['currency']) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="8">No overdue positions / لا توجد مبالغ متأخرة</td></tr>
                @endforelse
            </tbody>
        </table>
    </section>

    <section class="section allow-break">
        <h2 class="section-title clearfix">Overdue schedule <span>جدول المتأخرات</span></h2>
        @if(($data['recordTotal'] ?? count($data['records'])) > ($data['recordLimit'] ?? count($data['records'])))
            <div class="aging-limit">
                PDF shows the first {{ $data['recordLimit'] }} of {{ $data['recordTotal'] }} overdue lines. Download XLSX or DOCX for the complete schedule.
                <span class="rtl">يعرض PDF أول {{ $data['recordLimit'] }} قسطاً من أصل {{ $data['recordTotal'] }}. نزّل XLSX أو DOCX للحصول على الجدول الكامل.</span>
            </div>
        @endif
        <table class="data aging-table">
            <thead><tr>
                <th>Property / العقار</th>
                <th>Space / الوحدة</th>
                <th>Tenant / المستأجر</th>
                <th>Lease / العقد</th>
                <th>Installment / القسط</th>
                <th>Due / الاستحقاق</th>
                <th>Age / العمر</th>
                <th class="amount">Outstanding / المتبقي</th>
                <th>Follow-up / المتابعة</th>
            </tr></thead>
            <tbody>
                @forelse($data['records'] as $record)
                    <tr>
                        <td><strong>{{ $localized($record['property'] ?? []) ?: '-' }}</strong><div class="aging-muted">{{ $record['property']['code'] ?? '-' }}</div></td>
                        <td><strong>{{ $localized($record['asset'] ?? []) ?: '-' }}</strong><div class="aging-muted">{{ $record['asset']['code'] ?? '-' }}</div></td>
                        <td>{{ $record['tenant']['name'] ?? '-' }}</td>
                        <td>{{ $record['lease']['code'] ?? '-' }}</td>
                        <td>{{ $record['label'] }}</td>
                        <td>{{ $record['due_date'] }}</td>
                        <td class="aging-risk">{{ $record['days_overdue'] }} days / يوم</td>
                        <td class="amount aging-risk">{{ $money($record['outstanding_amount'], $record['currency']) }}</td>
                        <td>
                            {{ trans("app.rent_collection.follow_up_state_{$record['follow_up']['state']}", locale: 'en') }}
                            <div class="rtl">{{ trans("app.rent_collection.follow_up_state_{$record['follow_up']['state']}", locale: 'ar') }}</div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9">No overdue records match this scope / لا توجد متأخرات تطابق هذا النطاق</td></tr>
                @endforelse
            </tbody>
        </table>
    </section>

    <div class="document-footer">Property Management Control | Arrears Aging | تحليل أعمار المتأخرات</div>
</body>
</html>
