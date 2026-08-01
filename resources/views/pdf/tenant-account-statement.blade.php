<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Tenant account statement</title>
    @include('pdf.partials.document-styles')
</head>
<body>
    @php
        $tenant = $data['tenant'];
        $filters = $data['filters'];
        $money = static fn ($value, $currency) => number_format((float) $value, 2).' '.$currency;
        $option = static fn ($value, $locale) => trans()->has("app.status.{$value}")
            ? trans("app.status.{$value}", locale: $locale)
            : str($value)->replace('_', ' ')->headline();
    @endphp

    <table class="document-header">
        <tr>
            <td>
                <span class="brand-mark">PMC</span>
                <h1 class="document-title">Tenant Account Statement</h1>
                <div class="document-title-ar">كشف حساب المستأجر</div>
            </td>
            <td class="document-meta">
                <div><span>Period / الفترة</span><strong>{{ $filters['date_from'] }} - {{ $filters['date_to'] }}</strong></div>
                <div><span>Prepared for / أعد لصالح</span><strong>{{ $data['statement']['prepared_for'] }}</strong></div>
                <div><span>Generated / تاريخ الإصدار</span><strong>{{ now()->format('Y-m-d H:i') }}</strong></div>
            </td>
        </tr>
    </table>

    <section class="section">
        <h2 class="section-title clearfix">Tenant profile <span>ملف المستأجر</span></h2>
        <table class="two-column">
            <tr>
                <td>
                    <div class="card">
                        <div class="pair"><span>Tenant / المستأجر</span><strong>{{ $tenant['name'] }}</strong></div>
                        <div class="pair"><span>Email / البريد</span><strong>{{ $tenant['email'] ?: '-' }}</strong></div>
                        <div class="pair"><span>Phone / الهاتف</span><strong>{{ $tenant['phone'] ?: '-' }}</strong></div>
                    </div>
                </td>
                <td>
                    <div class="card">
                        <div class="pair"><span>Portfolio / المحفظة</span><strong>{{ $tenant['portfolio']['name_en'] ?: '-' }}</strong><strong class="rtl">{{ $tenant['portfolio']['name_ar'] ?: '-' }}</strong></div>
                        <div class="pair"><span>Contracts / العقود</span><strong>{{ $data['statement']['lease_count'] }}</strong></div>
                        <div class="pair"><span>Active contracts / العقود النشطة</span><strong>{{ $data['statement']['active_lease_count'] }}</strong></div>
                    </div>
                </td>
            </tr>
        </table>
    </section>

    <section class="section">
        <h2 class="section-title clearfix">Financial position <span>المركز المالي</span></h2>
        <table class="data">
            <thead>
                <tr>
                    <th>Currency / العملة</th>
                    <th class="amount">Scheduled / المجدول</th>
                    <th class="amount">Paid / المسدد</th>
                    <th class="amount">Received / المقبوض</th>
                    <th class="amount">Balance / الرصيد</th>
                    <th class="amount">Overdue / المتأخر</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data['statement']['financials'] as $item)
                    <tr>
                        <td>{{ $item['currency'] }}</td>
                        <td class="amount">{{ $money($item['scheduled_due'], $item['currency']) }}</td>
                        <td class="amount">{{ $money($item['scheduled_paid'], $item['currency']) }}</td>
                        <td class="amount">{{ $money($item['received'], $item['currency']) }}</td>
                        <td class="amount">{{ $money($item['contract_balance'], $item['currency']) }}</td>
                        <td class="amount">{{ $money($item['overdue'], $item['currency']) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </section>

    <section class="section allow-break">
        <h2 class="section-title clearfix">Contracts <span>العقود</span></h2>
        <table class="data">
            <thead><tr><th>Lease / العقد</th><th>Asset / الأصل</th><th>Period / المدة</th><th>Status / الحالة</th><th class="amount">Balance / الرصيد</th></tr></thead>
            <tbody>
                @forelse($data['leases'] as $lease)
                    <tr>
                        <td>{{ $lease['code'] }}</td>
                        <td>{{ $lease['asset_en'] ?: $lease['asset_ar'] ?: '-' }}</td>
                        <td>{{ $lease['started_at'] ?: '-' }} - {{ $lease['ends_at'] ?: '-' }}</td>
                        <td>{{ $option($lease['status'], 'en') }} / {{ $option($lease['status'], 'ar') }}</td>
                        <td class="amount">{{ $money($lease['balance'], $lease['currency']) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5">No contracts / لا توجد عقود</td></tr>
                @endforelse
            </tbody>
        </table>
    </section>

    <section class="section allow-break">
        <h2 class="section-title clearfix">Installments in period <span>أقساط الفترة</span></h2>
        <table class="data">
            <thead><tr><th>Due / الاستحقاق</th><th>Lease / العقد</th><th>Status / الحالة</th><th class="amount">Due / المستحق</th><th class="amount">Paid / المسدد</th><th class="amount">Remaining / المتبقي</th></tr></thead>
            <tbody>
                @forelse($data['installments'] as $installment)
                    <tr>
                        <td>{{ $installment['due_date'] ?: '-' }}</td>
                        <td>{{ $installment['lease_code'] ?: '-' }}</td>
                        <td>{{ $option($installment['status'], 'en') }} / {{ $option($installment['status'], 'ar') }}</td>
                        <td class="amount">{{ $money($installment['amount_due'], $installment['currency']) }}</td>
                        <td class="amount">{{ $money($installment['amount_paid'], $installment['currency']) }}</td>
                        <td class="amount">{{ $money($installment['remaining'], $installment['currency']) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6">No installments in this period / لا توجد أقساط في هذه الفترة</td></tr>
                @endforelse
            </tbody>
        </table>
    </section>

    <section class="section allow-break">
        <h2 class="section-title clearfix">Payment ledger <span>سجل الدفعات</span></h2>
        <table class="data">
            <thead><tr><th>Date / التاريخ</th><th>Reference / المرجع</th><th>Lease / العقد</th><th>Status / الحالة</th><th class="amount">Amount / المبلغ</th></tr></thead>
            <tbody>
                @forelse($data['payments'] as $payment)
                    <tr>
                        <td>{{ $payment['received_on'] ?: '-' }}</td>
                        <td>{{ $payment['reference'] }}</td>
                        <td>{{ $payment['lease_code'] ?: '-' }}</td>
                        <td>{{ $option($payment['status'], 'en') }} / {{ $option($payment['status'], 'ar') }}</td>
                        <td class="amount">{{ $money($payment['amount'], $payment['currency']) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5">No payments in this period / لا توجد دفعات في هذه الفترة</td></tr>
                @endforelse
            </tbody>
        </table>
    </section>

    <section class="section allow-break">
        <h2 class="section-title clearfix">Maintenance activity <span>نشاط الصيانة</span></h2>
        <table class="data">
            <thead><tr><th>Date / التاريخ</th><th>Request / الطلب</th><th>Asset / الأصل</th><th>Status / الحالة</th><th>Priority / الأولوية</th></tr></thead>
            <tbody>
                @forelse($data['maintenance'] as $request)
                    <tr>
                        <td>{{ $request['requested_at'] ?: '-' }}</td>
                        <td>{{ $request['title'] }}</td>
                        <td>{{ $request['asset_en'] ?: $request['asset_ar'] ?: '-' }}</td>
                        <td>{{ $option($request['status'], 'en') }} / {{ $option($request['status'], 'ar') }}</td>
                        <td>{{ $option($request['priority'], 'en') }} / {{ $option($request['priority'], 'ar') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5">No maintenance in this period / لا توجد صيانة في هذه الفترة</td></tr>
                @endforelse
            </tbody>
        </table>
    </section>

    <div class="document-footer">Property Management Control | {{ $tenant['name'] }} | كشف حساب المستأجر</div>
</body>
</html>
