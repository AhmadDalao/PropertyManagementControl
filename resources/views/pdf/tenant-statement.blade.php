<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Tenant statement {{ $lease->code }}</title>
    @include('pdf.partials.document-styles')
</head>
<body>
    <table class="document-header">
        <tr>
            <td>
                <span class="brand-mark">PMC</span>
                <h1 class="document-title">Tenant Statement</h1>
                <div class="document-title-ar">كشف حساب المستأجر</div>
            </td>
            <td class="document-meta">
                <div><span>Lease / العقد</span><strong>{{ $lease->code }}</strong></div>
                <div><span>Tenant / المستأجر</span><strong>{{ $lease->tenantProfile?->user?->name ?: '-' }}</strong></div>
                <div><span>Statement date / تاريخ الكشف</span><strong>{{ now()->format('Y-m-d H:i') }}</strong></div>
            </td>
        </tr>
    </table>

    <section class="section">
        <h2 class="section-title clearfix">Account summary <span>ملخص الحساب</span></h2>
        <table class="two-column">
            <tr>
                <td>
                    <div class="card">
                        <div class="pair"><span>Property / العقار</span><strong>{{ $lease->leaseable?->title_en }} / {{ $lease->leaseable?->title_ar }}</strong></div>
                        <div class="pair"><span>Contract period / مدة العقد</span><strong>{{ $lease->started_at?->format('Y-m-d') }} - {{ $lease->ends_at?->format('Y-m-d') }}</strong></div>
                        <div class="pair"><span>Portfolio / المحفظة</span><strong>{{ $lease->portfolio?->name_en }} / {{ $lease->portfolio?->name_ar }}</strong></div>
                    </div>
                </td>
                <td>
                    <div class="card">
                        <div class="pair"><span>Total scheduled / الإجمالي المجدول</span><strong>{{ number_format((float) $lease->total_due, 2) }} {{ $lease->currency }}</strong></div>
                        <div class="pair"><span>Total paid / المدفوع</span><strong>{{ number_format((float) $lease->total_paid, 2) }} {{ $lease->currency }}</strong></div>
                        <div class="pair"><span>Due now / المستحق الآن</span><strong>{{ number_format((float) $lease->due_now_amount, 2) }} {{ $lease->currency }}</strong></div>
                        <div class="pair"><span>Overdue / المتأخر</span><strong>{{ number_format((float) $lease->overdue_amount, 2) }} {{ $lease->currency }}</strong></div>
                        <div class="pair"><span>Contract balance / رصيد العقد</span><strong>{{ number_format((float) $lease->balance_remaining, 2) }} {{ $lease->currency }}</strong></div>
                    </div>
                </td>
            </tr>
        </table>
    </section>

    <section class="section allow-break">
        <h2 class="section-title clearfix">Installments <span>الأقساط</span></h2>
        <table class="data">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Installment / القسط</th>
                    <th>Due date / الاستحقاق</th>
                    <th>Status / الحالة</th>
                    <th class="amount">Due / المطلوب</th>
                    <th class="amount">Paid / المدفوع</th>
                    <th class="amount">Balance / الرصيد</th>
                </tr>
            </thead>
            <tbody>
                @foreach($lease->installments as $installment)
                    <tr>
                        <td>{{ $installment->sequence }}</td>
                        <td>{{ $installment->label }}</td>
                        <td>{{ $installment->due_date?->format('Y-m-d') }}</td>
                        <td><span class="status {{ $installment->status === 'overdue' ? 'overdue' : '' }}">{{ strtoupper($installment->status) }}</span></td>
                        <td class="amount">{{ number_format((float) $installment->amount_due, 2) }}</td>
                        <td class="amount">{{ number_format((float) $installment->amount_paid, 2) }}</td>
                        <td class="amount">{{ number_format((float) $installment->remaining_amount, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </section>

    <section class="section allow-break">
        <h2 class="section-title clearfix">Posted payments <span>الدفعات المسجلة</span></h2>
        <table class="data">
            <thead>
                <tr>
                    <th>Reference / المرجع</th>
                    <th>Date / التاريخ</th>
                    <th>Method / الطريقة</th>
                    <th>Status / الحالة</th>
                    <th class="amount">Amount / المبلغ</th>
                </tr>
            </thead>
            <tbody>
                @forelse($lease->payments->sortByDesc('received_on') as $payment)
                    <tr>
                        <td>{{ $payment->reference ?: '#'.$payment->id }}</td>
                        <td>{{ $payment->received_on?->format('Y-m-d') }}</td>
                        <td>{{ str($payment->method)->replace('_', ' ')->headline() }}</td>
                        <td>{{ strtoupper($payment->status) }}</td>
                        <td class="amount">{{ number_format((float) $payment->amount, 2) }} {{ $payment->currency }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5">No payments recorded / لا توجد دفعات مسجلة</td></tr>
                @endforelse
            </tbody>
        </table>
    </section>

    <div class="document-footer">{{ $lease->code }} | {{ $lease->tenantProfile?->user?->name }} | Property Management Control</div>
</body>
</html>
