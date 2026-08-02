<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Lease expiry and renewal schedule</title>
    @include('pdf.partials.document-styles')
    <style>
        .renewal-table { font-size: 7.2px; }
        .renewal-table th, .renewal-table td { padding: 5px 4px; vertical-align: top; }
        .renewal-muted { color: #6d6559; font-size: 6.7px; }
        .renewal-risk { color: #9d2a20; font-weight: 700; }
        .renewal-summary td { width: 25%; }
        .renewal-limit { margin: 0 0 10px; padding: 8px 10px; border: 1px solid #e3c986; background: #fff8e6; color: #5f4b18; font-size: 8px; }
        .renewal-schedule { page-break-before: always; }
    </style>
</head>
<body>
    @php
        $money = static fn ($value, $currency) => number_format((float) $value, 2).' '.$currency;
        $localized = static fn ($record) => implode(' / ', array_filter([
            $record['title_en'] ?? null,
            $record['title_ar'] ?? null,
        ]));
    @endphp

    <table class="document-header">
        <tr>
            <td>
                <span class="brand-mark">PMC</span>
                <h1 class="document-title">Lease Expiry & Renewal Schedule</h1>
                <div class="document-title-ar">جدول انتهاء وتجديد العقود</div>
            </td>
            <td class="document-meta">
                <div><span>Snapshot / الحالة الحالية</span><strong>{{ today()->toDateString() }}</strong></div>
                <div><span>Leases / العقود</span><strong>{{ $data['recordTotal'] ?? count($data['records']) }}</strong></div>
                <div><span>Generated / تاريخ الإصدار</span><strong>{{ now()->format('Y-m-d H:i') }}</strong></div>
            </td>
        </tr>
    </table>

    <section class="section">
        <h2 class="section-title clearfix">Applied scope <span>النطاق المطبق</span></h2>
        <table class="two-column renewal-summary">
            @foreach(array_chunk($data['scope'], 4) as $scopeRow)
                <tr>
                    @foreach($scopeRow as $item)
                        <td><div class="card"><div class="pair"><span>{{ $item['label'] }}</span><strong>{{ $item['value'] }}</strong></div></div></td>
                    @endforeach
                </tr>
            @endforeach
        </table>
    </section>

    <section class="section">
        <h2 class="section-title clearfix">Renewal position <span>موقف التجديد</span></h2>
        <table class="two-column renewal-summary"><tr>
            <td><div class="card"><div class="pair"><span>All / الكل</span><strong>{{ $data['summary']['total'] }}</strong></div></div></td>
            <td><div class="card"><div class="pair"><span>Action required / يتطلب إجراء</span><strong class="renewal-risk">{{ $data['summary']['attention'] }}</strong></div></div></td>
            <td><div class="card"><div class="pair"><span>Prepared / جاهز</span><strong>{{ $data['summary']['prepared'] }}</strong></div></div></td>
            <td><div class="card"><div class="pair"><span>Expired / منتهي</span><strong class="renewal-risk">{{ $data['summary']['expired'] }}</strong></div></div></td>
        </tr></table>
    </section>

    <section class="section">
        <h2 class="section-title clearfix">Position by currency <span>الموقف حسب العملة</span></h2>
        <table class="data">
            <thead><tr>
                <th>Currency / العملة</th>
                <th>Leases / العقود</th>
                <th>Action required / يتطلب إجراء</th>
                <th>Prepared / جاهز</th>
                <th>Expired / منتهي</th>
                <th class="amount">Outstanding / المتبقي</th>
            </tr></thead>
            <tbody>
                @forelse($data['currencyPositions'] as $position)
                    <tr>
                        <td>{{ $position['currency'] }}</td>
                        <td>{{ $position['leases'] }}</td>
                        <td>{{ $position['attention'] }}</td>
                        <td>{{ $position['prepared'] }}</td>
                        <td>{{ $position['expired'] }}</td>
                        <td class="amount">{{ $money($position['outstanding'], $position['currency']) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6">No contracts match this scope / لا توجد عقود تطابق هذا النطاق</td></tr>
                @endforelse
            </tbody>
        </table>
    </section>

    <section class="section allow-break renewal-schedule">
        <h2 class="section-title clearfix">Renewal schedule <span>جدول التجديد</span></h2>
        @if(($data['recordTotal'] ?? count($data['records'])) > ($data['recordLimit'] ?? count($data['records'])))
            <div class="renewal-limit">
                PDF shows the first {{ $data['recordLimit'] }} of {{ $data['recordTotal'] }} contracts. Download XLSX or DOCX for the complete schedule.
                <span class="rtl">يعرض PDF أول {{ $data['recordLimit'] }} عقداً من أصل {{ $data['recordTotal'] }}. نزّل XLSX أو DOCX للحصول على الجدول الكامل.</span>
            </div>
        @endif
        <table class="data renewal-table">
            <thead><tr>
                <th>Property / العقار</th>
                <th>Unit / الوحدة</th>
                <th>Tenant / المستأجر</th>
                <th>Lease / العقد</th>
                <th>End date / الانتهاء</th>
                <th>Contact due / التواصل</th>
                <th>Renewal state / حالة التجديد</th>
                <th>Renewal / التجديد</th>
                <th class="amount">Outstanding / المتبقي</th>
            </tr></thead>
            <tbody>
                @forelse($data['records'] as $record)
                    <tr>
                        <td><strong>{{ $localized($record['property'] ?? []) ?: '-' }}</strong><div class="renewal-muted">{{ $record['property']['code'] ?? '-' }}</div></td>
                        <td><strong>{{ $localized($record['asset'] ?? []) ?: '-' }}</strong><div class="renewal-muted">{{ $record['asset']['code'] ?? '-' }}</div></td>
                        <td>{{ $record['tenant']['name'] ?? '-' }}</td>
                        <td>{{ $record['code'] }}</td>
                        <td>{{ $record['ends_at'] ?? '-' }}<div class="renewal-muted">{{ $record['days_remaining'] ?? '-' }} days / يوم</div></td>
                        <td>{{ $record['contact_due_on'] ?? '-' }}</td>
                        <td>
                            {{ trans("app.lease_renewals.state_{$record['renewal_state']}", locale: 'en') }}
                            <div class="rtl">{{ trans("app.lease_renewals.state_{$record['renewal_state']}", locale: 'ar') }}</div>
                        </td>
                        <td>{{ $record['renewal']['code'] ?? '-' }}</td>
                        <td class="amount">{{ $money($record['outstanding_amount'], $record['currency']) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="9">No contracts match this scope / لا توجد عقود تطابق هذا النطاق</td></tr>
                @endforelse
            </tbody>
        </table>
    </section>

    <div class="document-footer">Property Management Control | Lease Expiry & Renewal | انتهاء وتجديد العقود</div>
</body>
</html>
