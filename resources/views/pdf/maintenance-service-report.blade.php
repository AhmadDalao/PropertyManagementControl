<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Maintenance service report #{{ $data->request->id }}</title>
    @include('pdf.partials.document-styles')
</head>
<body>
    @php
        $request = $data->request;
        $status = static fn ($value, $locale) => trans("app.status.{$value}", locale: $locale);
        $date = static fn ($value) => $value?->format('Y-m-d H:i') ?: '-';
    @endphp

    <table class="document-header">
        <tr>
            <td>
                <span class="brand-mark">PMC</span>
                <h1 class="document-title">Maintenance Service Report</h1>
                <div class="document-title-ar">تقرير خدمة الصيانة</div>
            </td>
            <td class="document-meta">
                <div><span>Request / الطلب</span><strong>#{{ $request->id }}</strong></div>
                <div><span>Status / الحالة</span><strong>{{ $status($request->status, 'en') }} / {{ $status($request->status, 'ar') }}</strong></div>
                <div><span>Generated / تاريخ الإصدار</span><strong>{{ now()->format('Y-m-d H:i') }}</strong></div>
            </td>
        </tr>
    </table>

    <section class="section">
        <h2 class="section-title clearfix">Request context <span>بيانات الطلب</span></h2>
        <table class="two-column">
            <tr>
                <td>
                    <div class="card">
                        <div class="pair"><span>Property / العقار</span><strong>{{ $request->asset?->title_en ?: '-' }}</strong><strong class="rtl">{{ $request->asset?->title_ar ?: '-' }}</strong></div>
                        <div class="pair"><span>Tenant / المستأجر</span><strong>{{ $request->tenantProfile?->user?->name ?: '-' }}</strong></div>
                        <div class="pair"><span>Lease / العقد</span><strong>{{ $request->lease?->code ?: '-' }}</strong></div>
                    </div>
                </td>
                <td>
                    <div class="card">
                        <div class="pair"><span>Category / الفئة</span><strong>{{ $status($request->category, 'en') }} / {{ $status($request->category, 'ar') }}</strong></div>
                        <div class="pair"><span>Priority / الأولوية</span><strong>{{ $status($request->priority, 'en') }} / {{ $status($request->priority, 'ar') }}</strong></div>
                        <div class="pair"><span>Requested / تاريخ الطلب</span><strong>{{ $date($request->requested_at) }}</strong></div>
                        <div class="pair"><span>Resolved / تاريخ الحل</span><strong>{{ $date($request->resolved_at) }}</strong></div>
                    </div>
                </td>
            </tr>
        </table>
    </section>

    <section class="section">
        <h2 class="section-title clearfix">Issue and resolution <span>المشكلة والحل</span></h2>
        <div class="card">
            <div class="pair"><span>Issue / المشكلة</span><strong>{{ $request->title }}</strong><div>{{ $request->description }}</div></div>
            <div class="pair"><span>Resolution / ملخص الحل</span><div>{{ $request->resolution_summary ?: '-' }}</div></div>
            <div class="pair"><span>Resolved by / أغلقه</span><strong>{{ $request->resolvedBy?->name ?: '-' }}</strong></div>
        </div>
    </section>

    <section class="section allow-break">
        <h2 class="section-title clearfix">Service visits <span>زيارات الخدمة</span></h2>
        <table class="data">
            <thead>
                <tr>
                    <th>Reference / المرجع</th>
                    <th>Contractor / المقاول</th>
                    <th>Schedule / الموعد</th>
                    <th>Status / الحالة</th>
                </tr>
            </thead>
            <tbody>
                @forelse($data->workOrders as $workOrder)
                    <tr>
                        <td>{{ $workOrder->reference_code }}</td>
                        <td>{{ $workOrder->vendor_name ?: '-' }}</td>
                        <td>{{ $date($workOrder->scheduled_at) }}</td>
                        <td>{{ $status($workOrder->status, 'en') }} / {{ $status($workOrder->status, 'ar') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4">No service visits recorded / لا توجد زيارات خدمة مسجلة</td></tr>
                @endforelse
            </tbody>
        </table>
    </section>

    @unless($data->tenantMode)
        <section class="section">
            <h2 class="section-title clearfix">Recorded cost <span>التكلفة المسجلة</span></h2>
            <div class="money-highlight">
                <span>Posted maintenance expense / مصروف الصيانة المرحل</span>
                <strong>{{ number_format($data->postedExpenseTotal, 2) }} SAR</strong>
            </div>
        </section>
    @endunless

    <section class="section">
        <h2 class="section-title clearfix">Tenant sign-off <span>اعتماد المستأجر</span></h2>
        <div class="card">
            <div class="pair">
                <span>Confirmation / التأكيد</span>
                <strong>
                    {{ $request->tenant_confirmed_at ? 'Confirmed / مؤكد' : 'Pending / بانتظار التأكيد' }}
                </strong>
            </div>
            <div class="pair"><span>Confirmed at / تاريخ التأكيد</span><strong>{{ $date($request->tenant_confirmed_at) }}</strong></div>
            <div class="pair"><span>Tenant note / ملاحظة المستأجر</span><div>{{ $request->tenant_confirmation_note ?: '-' }}</div></div>
        </div>
    </section>

    <table class="two-column">
        <tr>
            <td><div class="card"><div class="pair"><span>Management signature / توقيع الإدارة</span><strong>____________________</strong></div></div></td>
            <td><div class="card"><div class="pair"><span>Tenant signature / توقيع المستأجر</span><strong>____________________</strong></div></div></td>
        </tr>
    </table>

    <div class="document-footer">Property Management Control | Maintenance #{{ $request->id }} | تقرير الصيانة</div>
</body>
</html>
