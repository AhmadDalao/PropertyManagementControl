<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Owner Statement</title>
    @include('pdf.partials.document-styles')
</head>
<body>
    @php
        $summary = $data['summary'];
        $filters = $data['filters'];
        $context = $data['statement'];
        $money = static fn ($value, $currency = 'SAR') => number_format((float) $value, 2).' '.$currency;
        $option = static fn ($value, $locale) => trans()->has("app.status.{$value}")
            ? trans("app.status.{$value}", locale: $locale)
            : str($value)->replace('_', ' ')->headline();
    @endphp

    <table class="document-header">
        <tr>
            <td>
                <span class="brand-mark">PMC</span>
                <h1 class="document-title">Owner Statement</h1>
                <div class="document-title-ar">كشف المالك</div>
            </td>
            <td class="document-meta">
                <div><span>Period / الفترة</span><strong>{{ $filters['date_from'] }} - {{ $filters['date_to'] }}</strong></div>
                <div><span>Prepared for / أعد لصالح</span><strong>{{ $context['prepared_for'] }}</strong></div>
                <div><span>Generated / تاريخ الإصدار</span><strong>{{ now()->format('Y-m-d H:i') }}</strong></div>
            </td>
        </tr>
    </table>

    <section class="section">
        <h2 class="section-title clearfix">Statement scope <span>نطاق الكشف</span></h2>
        <table class="two-column">
            <tr>
                <td><div class="card"><div class="pair"><span>Portfolio / المحفظة</span><strong>{{ $context['portfolio']['en'] }}</strong><strong class="rtl">{{ $context['portfolio']['ar'] }}</strong></div></div></td>
                <td><div class="card"><div class="pair"><span>Property / العقار</span><strong>{{ $context['property']['en'] }}</strong><strong class="rtl">{{ $context['property']['ar'] }}</strong></div></div></td>
            </tr>
        </table>
    </section>

    <section class="section">
        <h2 class="section-title clearfix">Financial summary <span>الملخص المالي</span></h2>
        <table class="data">
            <tbody>
                <tr><td>Collected / المحصل</td><td class="amount">{{ $money($summary['revenue']) }}</td><td>Expenses / المصاريف</td><td class="amount">{{ $money($summary['expenses']) }}</td></tr>
                <tr><td>Net position / صافي المركز</td><td class="amount">{{ $money($summary['net']) }}</td><td>Arrears / المتأخرات</td><td class="amount">{{ $money($summary['arrears']) }}</td></tr>
                <tr><td>Collection rate / نسبة التحصيل</td><td class="amount">{{ number_format((float) $summary['collectionRate'], 1) }}%</td><td>Occupancy / الإشغال</td><td class="amount">{{ number_format((float) $summary['occupancyRate'], 1) }}%</td></tr>
                <tr><td>Active leases / العقود النشطة</td><td class="amount">{{ $summary['activeLeases'] }}</td><td>Open maintenance / الصيانة المفتوحة</td><td class="amount">{{ $summary['openRequests'] }}</td></tr>
            </tbody>
        </table>
    </section>

    <section class="section allow-break">
        <h2 class="section-title clearfix">Contracts in arrears <span>العقود المتأخرة</span></h2>
        <table class="data">
            <thead><tr><th>Lease / العقد</th><th>Tenant / المستأجر</th><th>Property / العقار</th><th class="amount">Balance / الرصيد</th></tr></thead>
            <tbody>
                @forelse($data['arrearsLeases'] as $lease)
                    <tr><td>{{ $lease['code'] }}</td><td>{{ $lease['tenant'] ?: '-' }}</td><td>{{ $lease['asset'] ?: '-' }}</td><td class="amount">{{ $money($lease['arrears_amount'], $lease['currency']) }}</td></tr>
                @empty
                    <tr><td colspan="4">No overdue contracts / لا توجد عقود متأخرة</td></tr>
                @endforelse
            </tbody>
        </table>
    </section>

    <section class="section allow-break">
        <h2 class="section-title clearfix">Recent payments <span>أحدث الدفعات</span></h2>
        <table class="data">
            <thead><tr><th>Reference / المرجع</th><th>Tenant / المستأجر</th><th>Date / التاريخ</th><th class="amount">Amount / المبلغ</th></tr></thead>
            <tbody>
                @forelse($data['recentPayments'] as $payment)
                    <tr><td>{{ $payment['reference'] }}</td><td>{{ $payment['tenant'] ?: '-' }}</td><td>{{ $payment['received_on'] ?: '-' }}</td><td class="amount">{{ $money($payment['amount'], $payment['currency']) }}</td></tr>
                @empty
                    <tr><td colspan="4">No posted payments / لا توجد دفعات مرحلة</td></tr>
                @endforelse
            </tbody>
        </table>
    </section>

    <section class="section allow-break">
        <h2 class="section-title clearfix">Maintenance backlog <span>طلبات الصيانة المتراكمة</span></h2>
        <table class="data">
            <thead><tr><th>Request / الطلب</th><th>Property / العقار</th><th>Status / الحالة</th><th>Priority / الأولوية</th></tr></thead>
            <tbody>
                @forelse($data['maintenanceBacklog'] as $request)
                    <tr><td>{{ $request['title'] }}</td><td>{{ $request['asset'] ?: '-' }}</td><td>{{ $option($request['status'], 'en') }} / {{ $option($request['status'], 'ar') }}</td><td>{{ $option($request['priority'], 'en') }} / {{ $option($request['priority'], 'ar') }}</td></tr>
                @empty
                    <tr><td colspan="4">No open requests / لا توجد طلبات مفتوحة</td></tr>
                @endforelse
            </tbody>
        </table>
    </section>

    <div class="document-footer">Property Management Control | Owner Statement | كشف المالك</div>
</body>
</html>
