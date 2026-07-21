<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Lease contract {{ $lease->code }}</title>
    @include('pdf.partials.document-styles')
</head>
<body>
    <table class="document-header">
        <tr>
            <td>
                <span class="brand-mark">PMC</span>
                <h1 class="document-title">Lease Contract</h1>
                <div class="document-title-ar">عقد إيجار</div>
            </td>
            <td class="document-meta">
                <div><span>Contract / العقد</span><strong>{{ $lease->code }}</strong></div>
                <div><span>Portfolio / المحفظة</span><strong>{{ $lease->portfolio?->name_en }} / {{ $lease->portfolio?->name_ar }}</strong></div>
                <div><span>Generated / تاريخ الإنشاء</span><strong>{{ now()->format('Y-m-d H:i') }}</strong></div>
            </td>
        </tr>
    </table>

    <div class="notice">
        This document records the lease data held in Property Management Control. Portfolio-approved legal clauses and the uploaded signed PDF govern the final agreement.
        <div class="notice-ar">يسجل هذا المستند بيانات العقد المحفوظة في نظام إدارة العقارات. تسري البنود القانونية المعتمدة من المحفظة ونسخة PDF الموقعة على الاتفاق النهائي.</div>
    </div>

    <section class="section">
        <h2 class="section-title clearfix">Parties <span>أطراف العقد</span></h2>
        <table class="two-column">
            <tr>
                <td>
                    <div class="card">
                        <h3>Landlord / المؤجر</h3>
                        <div class="pair"><span>Name</span><strong>{{ $lease->portfolio?->owner?->name ?: $lease->portfolio?->name_en }}</strong></div>
                        <div class="pair"><span>Portfolio</span><strong>{{ $lease->portfolio?->name_en }} / {{ $lease->portfolio?->name_ar }}</strong></div>
                        <div class="pair"><span>Contact</span><strong>{{ $lease->portfolio?->contact_phone ?: '-' }} | {{ $lease->portfolio?->contact_email ?: '-' }}</strong></div>
                    </div>
                </td>
                <td>
                    <div class="card">
                        <h3>Tenant / المستأجر</h3>
                        <div class="pair"><span>Name</span><strong>{{ $lease->tenantProfile?->user?->name ?: '-' }}</strong></div>
                        <div class="pair"><span>National ID / Registration</span><strong>{{ $lease->tenantProfile?->national_id ?: '-' }}</strong></div>
                        <div class="pair"><span>Contact</span><strong>{{ $lease->tenantProfile?->user?->phone ?: '-' }} | {{ $lease->tenantProfile?->user?->email ?: '-' }}</strong></div>
                    </div>
                </td>
            </tr>
        </table>
    </section>

    <section class="section">
        <h2 class="section-title clearfix">Property and period <span>العقار والمدة</span></h2>
        <table class="two-column">
            <tr>
                <td>
                    <div class="card">
                        <div class="pair"><span>Property</span><strong>{{ $lease->leaseable?->title_en }} / {{ $lease->leaseable?->title_ar }}</strong></div>
                        <div class="pair"><span>Asset code</span><strong>{{ $lease->leaseable?->code ?: '-' }}</strong></div>
                        <div class="pair"><span>Address</span><strong>{{ $lease->leaseable?->address ?: '-' }}</strong></div>
                        <div class="pair rtl"><span>العنوان</span><strong>{{ $lease->leaseable?->address_ar ?: $lease->leaseable?->address ?: '-' }}</strong></div>
                    </div>
                </td>
                <td>
                    <div class="card">
                        <div class="pair"><span>Start date / البداية</span><strong>{{ $lease->started_at?->format('Y-m-d') }}</strong></div>
                        <div class="pair"><span>End date / النهاية</span><strong>{{ $lease->ends_at?->format('Y-m-d') }}</strong></div>
                        <div class="pair"><span>Signed date / التوقيع</span><strong>{{ $lease->signed_at?->format('Y-m-d') ?: '-' }}</strong></div>
                        <div class="pair"><span>Managed by / المسؤول</span><strong>{{ $lease->managedBy?->name ?: '-' }}</strong></div>
                    </div>
                </td>
            </tr>
        </table>
    </section>

    <section class="section">
        <h2 class="section-title clearfix">Financial terms <span>الشروط المالية</span></h2>
        <table class="data">
            <thead>
                <tr>
                    <th>Rent</th>
                    <th>Deposit</th>
                    <th>Tax</th>
                    <th>Discount</th>
                    <th>Frequency</th>
                    <th>Billing day</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>{{ number_format((float) $lease->rent_amount, 2) }} {{ $lease->currency }}</td>
                    <td>{{ number_format((float) $lease->deposit_amount, 2) }} {{ $lease->currency }}</td>
                    <td>{{ number_format((float) $lease->tax_amount, 2) }} {{ $lease->currency }}</td>
                    <td>{{ number_format((float) $lease->discount_amount, 2) }} {{ $lease->currency }}</td>
                    <td>{{ ucfirst($lease->payment_frequency) }}</td>
                    <td>{{ $lease->billing_day ?: '-' }}</td>
                </tr>
            </tbody>
        </table>
    </section>

    <section class="section allow-break">
        <h2 class="section-title clearfix">Installment schedule <span>جدول الأقساط</span></h2>
        <table class="data">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Installment / القسط</th>
                    <th>Period / الفترة</th>
                    <th>Due date / الاستحقاق</th>
                    <th class="amount">Amount / المبلغ</th>
                </tr>
            </thead>
            <tbody>
                @foreach($lease->installments as $installment)
                    <tr>
                        <td>{{ $installment->sequence }}</td>
                        <td>{{ $installment->label }}</td>
                        <td>{{ $installment->period_start?->format('Y-m-d') }} - {{ $installment->period_end?->format('Y-m-d') }}</td>
                        <td>{{ $installment->due_date?->format('Y-m-d') }}</td>
                        <td class="amount">{{ number_format((float) $installment->amount_due, 2) }} {{ $lease->currency }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </section>

    <section class="section">
        <h2 class="section-title clearfix">Approved terms <span>البنود المعتمدة</span></h2>
        <table class="two-column">
            <tr>
                <td><div class="card terms">{{ data_get($lease->terms_json, 'en') ?: 'No portfolio-specific legal terms were entered in the system.' }}</div></td>
                <td><div class="card terms rtl">{{ data_get($lease->terms_json, 'ar') ?: 'لم تتم إضافة بنود قانونية خاصة بالمحفظة في النظام.' }}</div></td>
            </tr>
        </table>
    </section>

    <section class="section">
        <h2 class="section-title clearfix">Signatures <span>التوقيعات</span></h2>
        <table class="signature-table">
            <tr>
                <td><strong>Landlord / المؤجر</strong><div class="signature-line">Name, signature, date</div></td>
                <td><strong>Tenant / المستأجر</strong><div class="signature-line">Name, signature, date</div></td>
                <td><strong>Manager / المدير</strong><div class="signature-line">Name, signature, date</div></td>
            </tr>
        </table>
    </section>

    <div class="document-footer">{{ $lease->code }} | Property Management Control | Generated {{ now()->format('Y-m-d H:i') }}</div>
</body>
</html>
