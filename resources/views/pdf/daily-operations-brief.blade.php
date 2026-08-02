<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Daily operations brief</title>
    @include('pdf.partials.document-styles')
    <style>
        .brief-table { font-size: 7.1px; }
        .brief-table th, .brief-table td { padding: 5px 4px; vertical-align: top; }
        .brief-muted { color: #6d6559; font-size: 6.7px; }
        .brief-risk { color: #9d2a20; font-weight: 700; }
        .brief-summary td { width: 20%; }
        .brief-type td { width: 20%; }
        .brief-page-start { page-break-before: always; }
        .brief-limit { margin: 0 0 10px; padding: 8px 10px; border: 1px solid #e3c986; background: #fff8e6; color: #5f4b18; font-size: 8px; }
    </style>
</head>
<body>
    @php
        $money = static fn ($value, $currency) => number_format((float) $value, 2).' '.$currency;
        $localized = static fn ($record, $prefix) => implode(' / ', array_filter([
            $record[$prefix.'_en'] ?? null,
            $record[$prefix.'_ar'] ?? null,
        ]));
    @endphp

    <table class="document-header">
        <tr>
            <td>
                <span class="brand-mark">PMC</span>
                <h1 class="document-title">Daily Operations Brief</h1>
                <div class="document-title-ar">موجز العمليات اليومية</div>
            </td>
            <td class="document-meta">
                <div><span>Snapshot / الحالة الحالية</span><strong>{{ today()->toDateString() }}</strong></div>
                <div><span>Open actions / الإجراءات</span><strong>{{ $data['recordTotal'] ?? count($data['records']) }}</strong></div>
                <div><span>Generated / تاريخ الإصدار</span><strong>{{ now()->format('Y-m-d H:i') }}</strong></div>
            </td>
        </tr>
    </table>

    <section class="section">
        <h2 class="section-title clearfix">Applied scope <span>النطاق المطبق</span></h2>
        <table class="two-column">
            @foreach(array_chunk($data['scope'], 3) as $scopeRow)
                <tr>
                    @foreach($scopeRow as $item)
                        <td><div class="card"><div class="pair"><span>{{ $item['label'] }}</span><strong>{{ $item['value'] }}</strong></div></div></td>
                    @endforeach
                </tr>
            @endforeach
        </table>
    </section>

    <section class="section">
        <h2 class="section-title clearfix">Priority position <span>موقف الأولويات</span></h2>
        <table class="two-column brief-summary"><tr>
            <td><div class="card"><div class="pair"><span>All / الكل</span><strong>{{ $data['summary']['total'] }}</strong></div></div></td>
            <td><div class="card"><div class="pair"><span>Critical / حرج</span><strong class="brief-risk">{{ $data['summary']['critical'] }}</strong></div></div></td>
            <td><div class="card"><div class="pair"><span>High / عالٍ</span><strong>{{ $data['summary']['high'] }}</strong></div></div></td>
            <td><div class="card"><div class="pair"><span>Normal / عادي</span><strong>{{ $data['summary']['normal'] }}</strong></div></div></td>
            <td><div class="card"><div class="pair"><span>Unassigned / غير مسند</span><strong class="brief-risk">{{ $data['summary']['unassigned'] }}</strong></div></div></td>
        </tr></table>
    </section>

    <section class="section">
        <h2 class="section-title clearfix">Work by type <span>الأعمال حسب النوع</span></h2>
        <table class="two-column brief-type"><tr>
            @foreach($data['typePositions'] as $position)
                <td><div class="card"><div class="pair">
                    <span>
                        {{ trans("app.action_center.type_{$position['type']}", locale: 'en') }}
                        / {{ trans("app.action_center.type_{$position['type']}", locale: 'ar') }}
                    </span>
                    <strong>{{ $position['count'] }}</strong>
                </div></div></td>
            @endforeach
        </tr></table>
    </section>

    @if($data['currencyPositions'] !== [])
        <section class="section brief-page-start">
            <h2 class="section-title clearfix">Financial exposure <span>المبالغ المرتبطة</span></h2>
            <table class="data">
                <thead><tr>
                    <th>Currency / العملة</th>
                    <th>Actions / الإجراءات</th>
                    <th class="amount">Amount / المبلغ</th>
                </tr></thead>
                <tbody>
                    @foreach($data['currencyPositions'] as $position)
                        <tr>
                            <td>{{ $position['currency'] }}</td>
                            <td>{{ $position['count'] }}</td>
                            <td class="amount">{{ $money($position['amount'], $position['currency']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </section>
    @endif

    <section class="section allow-break {{ $data['currencyPositions'] === [] ? 'brief-page-start' : '' }}">
        <h2 class="section-title clearfix">Priority queue <span>قائمة الأولويات</span></h2>
        @if(($data['recordTotal'] ?? count($data['records'])) > ($data['recordLimit'] ?? count($data['records'])))
            <div class="brief-limit">
                PDF shows the first {{ $data['recordLimit'] }} of {{ $data['recordTotal'] }} actions. Download XLSX or DOCX for the complete queue.
                <span class="rtl">يعرض PDF أول {{ $data['recordLimit'] }} إجراء من أصل {{ $data['recordTotal'] }}. نزّل XLSX أو DOCX للحصول على القائمة الكاملة.</span>
            </div>
        @endif
        <table class="data brief-table">
            <thead><tr>
                <th>Priority / الأولوية</th>
                <th>Type / النوع</th>
                <th>Record / السجل</th>
                <th>Tenant / المستأجر</th>
                <th>Property / العقار</th>
                <th>Status / الحالة</th>
                <th>Due / الاستحقاق</th>
                <th>Responsible / المسؤول</th>
                <th class="amount">Amount / المبلغ</th>
            </tr></thead>
            <tbody>
                @forelse($data['records'] as $record)
                    <tr>
                        <td>
                            {{ trans("app.action_center.priority_{$record['priority']}", locale: 'en') }}
                            <div class="rtl">{{ trans("app.action_center.priority_{$record['priority']}", locale: 'ar') }}</div>
                        </td>
                        <td>
                            {{ trans("app.action_center.type_{$record['type']}", locale: 'en') }}
                            <div class="rtl">{{ trans("app.action_center.type_{$record['type']}", locale: 'ar') }}</div>
                        </td>
                        <td><strong>{{ $record['title'] }}</strong><div class="brief-muted">{{ $record['subtitle'] ?? '-' }}</div></td>
                        <td>{{ $record['tenant'] ?? '-' }}</td>
                        <td>
                            <strong>{{ $localized($record['asset'] ?? [], 'title') ?: '-' }}</strong>
                            <div class="brief-muted">{{ $localized($record['portfolio'] ?? [], 'name') ?: '-' }}</div>
                        </td>
                        <td>
                            {{ trans("app.action_center.status_{$record['status']}", locale: 'en') }}
                            <div class="rtl">{{ trans("app.action_center.status_{$record['status']}", locale: 'ar') }}</div>
                        </td>
                        <td>{{ $record['due_on'] ?? '-' }}</td>
                        <td>{{ $record['assigned_to']['name'] ?? '-' }}</td>
                        <td class="amount">
                            {{ isset($record['amount'], $record['currency']) ? $money($record['amount'], $record['currency']) : '-' }}
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9">No work matches this scope / لا توجد أعمال تطابق هذا النطاق</td></tr>
                @endforelse
            </tbody>
        </table>
    </section>

    <div class="document-footer">Property Management Control | Daily Operations Brief | موجز العمليات اليومية</div>
</body>
</html>
