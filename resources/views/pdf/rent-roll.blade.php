<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Rent roll</title>
    @include('pdf.partials.document-styles')
    <style>
        .rent-roll-table { font-size: 7.4px; }
        .rent-roll-table th,
        .rent-roll-table td { padding: 5px 4px; vertical-align: top; }
        .rent-roll-space { min-width: 92px; }
        .rent-roll-tenant { min-width: 82px; }
        .rent-roll-muted { color: #6d6559; font-size: 6.7px; }
        .rent-roll-state { font-weight: 700; }
        .rent-roll-summary td { width: 25%; }
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
                <h1 class="document-title">Rent Roll</h1>
                <div class="document-title-ar">سجل الإيجارات</div>
            </td>
            <td class="document-meta">
                <div><span>Snapshot / الحالة الحالية</span><strong>{{ today()->toDateString() }}</strong></div>
                <div><span>Records / السجلات</span><strong>{{ count($data['records']) }}</strong></div>
                <div><span>Generated / تاريخ الإصدار</span><strong>{{ now()->format('Y-m-d H:i') }}</strong></div>
            </td>
        </tr>
    </table>

    <section class="section">
        <h2 class="section-title clearfix">Applied scope <span>النطاق المطبق</span></h2>
        <table class="two-column rent-roll-summary">
            <tr>
                @foreach($data['scope'] as $item)
                    <td>
                        <div class="card">
                            <div class="pair">
                                <span>{{ $item['label'] }}</span>
                                <strong>{{ $item['value'] }}</strong>
                            </div>
                        </div>
                    </td>
                @endforeach
            </tr>
        </table>
    </section>

    <section class="section">
        <h2 class="section-title clearfix">Currency positions <span>المراكز حسب العملة</span></h2>
        <table class="data">
            <thead>
                <tr>
                    <th>Currency / العملة</th>
                    <th>Active / النشطة</th>
                    <th class="amount">Contracted / المتعاقد</th>
                    <th class="amount">Paid / المسدد</th>
                    <th class="amount">Outstanding / المتبقي</th>
                    <th class="amount">Overdue / المتأخر</th>
                    <th class="amount">Deposits / التأمينات</th>
                </tr>
            </thead>
            <tbody>
                @forelse($data['currencyPositions'] as $position)
                    <tr>
                        <td>{{ $position['currency'] }}</td>
                        <td>{{ $position['active_leases'] }}</td>
                        <td class="amount">{{ $money($position['contracted'], $position['currency']) }}</td>
                        <td class="amount">{{ $money($position['paid'], $position['currency']) }}</td>
                        <td class="amount">{{ $money($position['outstanding'], $position['currency']) }}</td>
                        <td class="amount">{{ $money($position['overdue'], $position['currency']) }}</td>
                        <td class="amount">{{ $money($position['deposits'], $position['currency']) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7">No active lease positions / لا توجد مراكز لعقود نشطة</td></tr>
                @endforelse
            </tbody>
        </table>
    </section>

    <section class="section allow-break">
        <h2 class="section-title clearfix">Rentable records <span>السجلات القابلة للتأجير</span></h2>
        <table class="data rent-roll-table">
            <thead>
                <tr>
                    <th>Property / العقار</th>
                    <th>Space / الوحدة</th>
                    <th>Tenant / المستأجر</th>
                    <th>Lease / العقد</th>
                    <th>Period / المدة</th>
                    <th class="amount">Rent / الإيجار</th>
                    <th class="amount">Paid / المسدد</th>
                    <th class="amount">Outstanding / المتبقي</th>
                    <th class="amount">Overdue / المتأخر</th>
                    <th>State / الحالة</th>
                </tr>
            </thead>
            <tbody>
                @forelse($data['records'] as $record)
                    @php($lease = $record['lease'])
                    <tr>
                        <td>
                            <strong>{{ $localized($record['property'] ?? []) ?: '-' }}</strong>
                            <div class="rent-roll-muted">{{ $record['property']['code'] ?? '-' }}</div>
                        </td>
                        <td class="rent-roll-space">
                            <strong>{{ $localized($record) ?: '-' }}</strong>
                            <div class="rent-roll-muted">{{ $record['code'] }}</div>
                        </td>
                        <td class="rent-roll-tenant">{{ $lease['tenant'] ?? '-' }}</td>
                        <td>{{ $lease['code'] ?? '-' }}</td>
                        <td>
                            @if($lease)
                                {{ $lease['started_at'] ?: '-' }}<br>
                                <span class="rent-roll-muted">{{ $lease['ends_at'] ?: '-' }}</span>
                            @else
                                -
                            @endif
                        </td>
                        <td class="amount">{{ $lease ? $money($lease['rent_amount'], $lease['currency']) : '-' }}</td>
                        <td class="amount">{{ $lease ? $money($lease['total_paid'], $lease['currency']) : '-' }}</td>
                        <td class="amount">{{ $lease ? $money($lease['balance'], $lease['currency']) : '-' }}</td>
                        <td class="amount">{{ $lease ? $money($lease['overdue'], $lease['currency']) : '-' }}</td>
                        <td class="rent-roll-state">
                            {{ trans("app.reports.rent_roll_state_{$record['state']}", locale: 'en') }}
                            <div class="rtl">{{ trans("app.reports.rent_roll_state_{$record['state']}", locale: 'ar') }}</div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="10">No rentable records match this scope / لا توجد سجلات قابلة للتأجير ضمن هذا النطاق</td></tr>
                @endforelse
            </tbody>
        </table>
    </section>

    <div class="document-footer">Property Management Control | Rent Roll | سجل الإيجارات</div>
</body>
</html>
