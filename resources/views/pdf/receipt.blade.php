<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Payment receipt {{ $payment->reference ?: $payment->id }}</title>
    @include('pdf.partials.document-styles')
</head>
<body>
    <table class="document-header">
        <tr>
            <td>
                <span class="brand-mark">PMC</span>
                <h1 class="document-title">Payment Receipt</h1>
                <div class="document-title-ar">إيصال دفعة</div>
            </td>
            <td class="document-meta">
                <div><span>Reference / المرجع</span><strong>{{ $payment->reference ?: '#'.$payment->id }}</strong></div>
                <div><span>Status / الحالة</span><strong>{{ strtoupper($payment->status) }}</strong></div>
                <div><span>Generated / تاريخ الإنشاء</span><strong>{{ now()->format('Y-m-d H:i') }}</strong></div>
            </td>
        </tr>
    </table>

    <div class="money-highlight">
        <span>Amount received / المبلغ المستلم</span>
        <strong>{{ number_format((float) $payment->amount, 2) }} {{ $payment->currency }}</strong>
    </div>

    <section class="section">
        <h2 class="section-title clearfix">Payment details <span>بيانات الدفعة</span></h2>
        <table class="two-column">
            <tr>
                <td>
                    <div class="card">
                        <div class="pair"><span>Tenant / المستأجر</span><strong>{{ $payment->tenantProfile?->user?->name ?: '-' }}</strong></div>
                        <div class="pair"><span>Lease / العقد</span><strong>{{ $payment->lease?->code ?: '-' }}</strong></div>
                        <div class="pair"><span>Property / العقار</span><strong>{{ $payment->lease?->leaseable?->title_en }} / {{ $payment->lease?->leaseable?->title_ar }}</strong></div>
                    </div>
                </td>
                <td>
                    <div class="card">
                        <div class="pair"><span>Received on / تاريخ الاستلام</span><strong>{{ $payment->received_on?->format('Y-m-d') }}</strong></div>
                        <div class="pair"><span>Method / الطريقة</span><strong>{{ str($payment->method)->replace('_', ' ')->headline() }}</strong></div>
                        <div class="pair"><span>Type / النوع</span><strong>{{ str($payment->type)->headline() }}</strong></div>
                        <div class="pair"><span>Recorded by / سجله</span><strong>{{ $payment->recordedBy?->name ?: '-' }}</strong></div>
                    </div>
                </td>
            </tr>
        </table>
    </section>

    <section class="section allow-break">
        <h2 class="section-title clearfix">Allocation <span>توزيع الدفعة</span></h2>
        <table class="data">
            <thead>
                <tr>
                    <th>Installment / القسط</th>
                    <th>Due date / الاستحقاق</th>
                    <th>Type / النوع</th>
                    <th class="amount">Allocated / الموزع</th>
                </tr>
            </thead>
            <tbody>
                @forelse($payment->allocations as $allocation)
                    <tr>
                        <td>{{ $allocation->leaseInstallment?->label ?: '-' }}</td>
                        <td>{{ $allocation->leaseInstallment?->due_date?->format('Y-m-d') ?: '-' }}</td>
                        <td>{{ str($allocation->allocation_type)->headline() }}</td>
                        <td class="amount">{{ number_format((float) $allocation->amount, 2) }} {{ $payment->currency }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4">No installment allocation recorded / لا يوجد توزيع مسجل على الأقساط</td></tr>
                @endforelse
            </tbody>
        </table>
    </section>

    <div class="notice">
        This system receipt confirms a recorded payment. Keep the bank or cash evidence with the signed portfolio records.
        <div class="notice-ar">يؤكد هذا الإيصال تسجيل الدفعة في النظام. احتفظ بإثبات التحويل أو السداد النقدي مع سجلات المحفظة الموقعة.</div>
    </div>

    <div class="document-footer">{{ $payment->lease?->portfolio?->name_en }} | {{ $payment->reference ?: '#'.$payment->id }} | Property Management Control</div>
</body>
</html>
