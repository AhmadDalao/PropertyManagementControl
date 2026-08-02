<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Property Operating Report</title>
    @include('pdf.partials.document-styles')
</head>
<body>
    @php
        $property = $data['property'];
        $structure = $property['structure'];
        $summary = $data['summary'];
        $filters = $data['filters'];
        $limits = $data['export']['limits'];
        $money = static fn ($value, $currency = 'SAR') => number_format((float) $value, 2).' '.$currency;
        $option = static fn ($value, $locale) => trans()->has("app.status.{$value}")
            ? trans("app.status.{$value}", locale: $locale)
            : str($value)->replace('_', ' ')->headline();
        $limitNote = static fn ($key) => ($limits[$key]['total'] ?? 0) > ($limits[$key]['shown'] ?? 0)
            ? 'Showing '.number_format($limits[$key]['shown']).' of '.number_format($limits[$key]['total'])
                .' records. The XLSX and DOCX files contain the complete scoped period.'
            : null;
    @endphp

    <table class="document-header">
        <tr>
            <td>
                <span class="brand-mark">PMC</span>
                <h1 class="document-title">Property Operating Report</h1>
                <div class="document-title-ar">تقرير تشغيل العقار</div>
            </td>
            <td class="document-meta">
                <div><span>Property / العقار</span><strong>{{ $property['code'] }}</strong></div>
                <div><span>Period / الفترة</span><strong>{{ $filters['date_from'] }} - {{ $filters['date_to'] }}</strong></div>
                <div><span>Generated / تاريخ الإصدار</span><strong>{{ $data['export']['generated_at'] }}</strong></div>
            </td>
        </tr>
    </table>

    <section class="section">
        <h2 class="section-title clearfix">Property profile <span>ملف العقار</span></h2>
        <table class="two-column">
            <tr>
                <td>
                    <div class="card">
                        <div class="pair"><span>Property / العقار</span><strong>{{ $property['title_en'] }}</strong><strong class="rtl">{{ $property['title_ar'] }}</strong></div>
                        <div class="pair"><span>Portfolio / المحفظة</span><strong>{{ $property['portfolio']['name_en'] }}</strong><strong class="rtl">{{ $property['portfolio']['name_ar'] }}</strong></div>
                        <div class="pair"><span>Address / العنوان</span><strong>{{ $property['address_en'] ?: '-' }}</strong><strong class="rtl">{{ $property['address_ar'] ?: '-' }}</strong></div>
                    </div>
                </td>
                <td>
                    <div class="card">
                        <div class="pair"><span>Owner / المالك</span><strong>{{ $property['owner']['name'] ?? '-' }}</strong></div>
                        <div class="pair"><span>Manager / المدير</span><strong>{{ $property['manager']['name'] ?? '-' }}</strong></div>
                        <div class="pair"><span>Status / الحالة</span><strong>{{ $option($property['status'], 'en') }} / {{ $option($property['status'], 'ar') }}</strong></div>
                        <div class="pair"><span>Usage / الاستخدام</span><strong>{{ trans("app.assets.usages.{$property['usage_type']}", locale: 'en') }} / {{ trans("app.assets.usages.{$property['usage_type']}", locale: 'ar') }}</strong></div>
                        <div class="pair"><span>Valuation / التقييم</span><strong>{{ $money($property['valuation_amount'], $property['currency']) }}</strong></div>
                    </div>
                </td>
            </tr>
        </table>
    </section>

    <section class="section">
        <h2 class="section-title clearfix">Structure and occupancy <span>الهيكل والإشغال</span></h2>
        <table class="data">
            <thead>
                <tr>
                    <th>Hierarchy records / سجلات الهيكل</th>
                    <th>Floors / الطوابق</th>
                    <th>Units / الوحدات</th>
                    <th>Rentable / قابل للتأجير</th>
                    <th>Occupied / مشغول</th>
                    <th>Vacant / شاغر</th>
                    <th>Active tenants / مستأجرون نشطون</th>
                    <th>Occupancy / الإشغال</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>{{ number_format($structure['records']) }}</td>
                    <td>{{ number_format($structure['floors']) }}</td>
                    <td>{{ number_format($structure['units']) }}</td>
                    <td>{{ number_format($structure['rentable']) }}</td>
                    <td>{{ number_format($structure['occupied']) }}</td>
                    <td>{{ number_format($structure['vacant']) }}</td>
                    <td>{{ number_format($structure['active_tenants']) }}</td>
                    <td>{{ number_format((float) $summary['occupancyRate'], 1) }}%</td>
                </tr>
            </tbody>
        </table>
    </section>

    <section class="section allow-break">
        <h2 class="section-title clearfix">Financial position <span>المركز المالي</span></h2>
        <table class="data">
            <thead>
                <tr>
                    <th>Currency / العملة</th>
                    <th class="amount">Collected / المحصل</th>
                    <th class="amount">Expenses / المصاريف</th>
                    <th class="amount">Net / الصافي</th>
                    <th class="amount">Scheduled due / المستحق</th>
                    <th class="amount">Scheduled paid / المسدد</th>
                    <th class="amount">Arrears / المتأخرات</th>
                    <th class="amount">Contract balance / رصيد العقود</th>
                    <th class="amount">Collection / التحصيل</th>
                </tr>
            </thead>
            <tbody>
                @foreach($summary['currencyTotals'] as $position)
                    <tr>
                        <td>{{ $position['currency'] }}</td>
                        <td class="amount">{{ $money($position['revenue'], $position['currency']) }}</td>
                        <td class="amount">{{ $money($position['expenses'], $position['currency']) }}</td>
                        <td class="amount">{{ $money($position['net'], $position['currency']) }}</td>
                        <td class="amount">{{ $money($position['scheduledDue'], $position['currency']) }}</td>
                        <td class="amount">{{ $money($position['scheduledPaid'], $position['currency']) }}</td>
                        <td class="amount">{{ $money($position['arrears'], $position['currency']) }}</td>
                        <td class="amount">{{ $money($position['contractBalance'], $position['currency']) }}</td>
                        <td class="amount">{{ number_format((float) $position['collectionRate'], 1) }}%</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </section>

    <section class="section allow-break">
        <h2 class="section-title clearfix">Contracts in arrears <span>العقود المتأخرة</span></h2>
        @if($limitNote('arrearsLeases'))<div class="notice">{{ $limitNote('arrearsLeases') }}</div>@endif
        <table class="data">
            <thead><tr><th>Lease / العقد</th><th>Tenant / المستأجر</th><th>Space / الوحدة</th><th>End / النهاية</th><th class="amount">Balance / الرصيد</th></tr></thead>
            <tbody>
                @forelse($data['arrearsLeases'] as $record)
                    <tr><td>{{ $record['code'] }}</td><td>{{ $record['tenant'] ?: '-' }}</td><td>{{ $record['asset'] ?: '-' }}</td><td>{{ $record['ends_at'] ?: '-' }}</td><td class="amount">{{ $money($record['arrears_amount'], $record['currency']) }}</td></tr>
                @empty
                    <tr><td colspan="5">No overdue contracts / لا توجد عقود متأخرة</td></tr>
                @endforelse
            </tbody>
        </table>
    </section>

    <section class="section allow-break">
        <h2 class="section-title clearfix">Posted payments <span>الدفعات المرحلة</span></h2>
        @if($limitNote('recentPayments'))<div class="notice">{{ $limitNote('recentPayments') }}</div>@endif
        <table class="data">
            <thead><tr><th>Reference / المرجع</th><th>Tenant / المستأجر</th><th>Lease / العقد</th><th>Date / التاريخ</th><th class="amount">Amount / المبلغ</th></tr></thead>
            <tbody>
                @forelse($data['recentPayments'] as $record)
                    <tr><td>{{ $record['reference'] }}</td><td>{{ $record['tenant'] ?: '-' }}</td><td>{{ $record['lease'] ?: '-' }}</td><td>{{ $record['received_on'] ?: '-' }}</td><td class="amount">{{ $money($record['amount'], $record['currency']) }}</td></tr>
                @empty
                    <tr><td colspan="5">No posted payments / لا توجد دفعات مرحلة</td></tr>
                @endforelse
            </tbody>
        </table>
    </section>

    <section class="section allow-break">
        <h2 class="section-title clearfix">Posted expenses <span>المصاريف المرحلة</span></h2>
        @if($limitNote('recentExpenses'))<div class="notice">{{ $limitNote('recentExpenses') }}</div>@endif
        <table class="data">
            <thead><tr><th>Expense / المصروف</th><th>Category / التصنيف</th><th>Space / الوحدة</th><th>Date / التاريخ</th><th class="amount">Amount / المبلغ</th></tr></thead>
            <tbody>
                @forelse($data['recentExpenses'] as $record)
                    <tr><td>{{ $record['title'] }}</td><td>{{ $option($record['category'], 'en') }} / {{ $option($record['category'], 'ar') }}</td><td>{{ $record['asset'] ?: '-' }}</td><td>{{ $record['incurred_on'] ?: '-' }}</td><td class="amount">{{ $money($record['amount'], $record['currency']) }}</td></tr>
                @empty
                    <tr><td colspan="5">No posted expenses / لا توجد مصاريف مرحلة</td></tr>
                @endforelse
            </tbody>
        </table>
    </section>

    <section class="section allow-break">
        <h2 class="section-title clearfix">Maintenance backlog <span>طلبات الصيانة المتراكمة</span></h2>
        @if($limitNote('maintenanceBacklog'))<div class="notice">{{ $limitNote('maintenanceBacklog') }}</div>@endif
        <table class="data">
            <thead><tr><th>Request / الطلب</th><th>Space / الوحدة</th><th>Tenant / المستأجر</th><th>Status / الحالة</th><th>Priority / الأولوية</th><th>Opened / تاريخ الفتح</th></tr></thead>
            <tbody>
                @forelse($data['maintenanceBacklog'] as $record)
                    <tr><td>{{ $record['title'] }}</td><td>{{ $record['asset'] ?: '-' }}</td><td>{{ $record['tenant'] ?: '-' }}</td><td>{{ $option($record['status'], 'en') }} / {{ $option($record['status'], 'ar') }}</td><td>{{ $option($record['priority'], 'en') }} / {{ $option($record['priority'], 'ar') }}</td><td>{{ $record['created_at'] ?: '-' }}</td></tr>
                @empty
                    <tr><td colspan="6">No open requests / لا توجد طلبات مفتوحة</td></tr>
                @endforelse
            </tbody>
        </table>
    </section>

    <section class="section allow-break">
        <h2 class="section-title clearfix">Operational activity <span>النشاط التشغيلي</span></h2>
        @if($limitNote('operationalJournal'))<div class="notice">{{ $limitNote('operationalJournal') }}</div>@endif
        <table class="data">
            <thead><tr><th>Date / التاريخ</th><th>Type / النوع</th><th>Record / السجل</th><th>Context / السياق</th><th>By / بواسطة</th><th class="amount">Amount / المبلغ</th></tr></thead>
            <tbody>
                @forelse($data['operationalJournal'] as $record)
                    <tr><td>{{ $record['occurred_at'] ?: '-' }}</td><td>{{ $record['type_label'] }}</td><td>{{ $record['title'] }}</td><td>{{ $record['subtitle'] }}</td><td>{{ $record['actor'] }}</td><td class="amount">{{ $record['amount'] !== null ? $money($record['amount'], $record['currency'] ?: 'SAR') : '-' }}</td></tr>
                @empty
                    <tr><td colspan="6">No recorded activity / لا يوجد نشاط مسجل</td></tr>
                @endforelse
            </tbody>
        </table>
    </section>

    <div class="document-footer">Property Management Control | Property Operating Report | تقرير تشغيل العقار</div>
</body>
</html>
