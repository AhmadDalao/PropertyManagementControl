<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #1b2430; font-size: 14px; }
        h1, h2 { margin: 0 0 12px; }
        .card { border: 1px solid #d9dee7; border-radius: 12px; padding: 16px; margin-bottom: 16px; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border-bottom: 1px solid #d9dee7; padding: 8px 0; text-align: left; }
    </style>
</head>
<body>
    <h1>Lease Contract</h1>
    <p>Lease code: {{ $lease->code }}</p>

    <div class="card">
        <h2>Parties</h2>
        <p>Tenant: {{ $lease->tenantProfile?->user?->name }}</p>
        <p>Managed by: {{ $lease->managedBy?->name }}</p>
        <p>Property: {{ $lease->leaseable?->title_en }}</p>
    </div>

    <div class="card">
        <h2>Financials</h2>
        <p>Rent: {{ $lease->rent_amount }} {{ $lease->currency }}</p>
        <p>Deposit: {{ $lease->deposit_amount }} {{ $lease->currency }}</p>
        <p>Period: {{ $lease->started_at?->format('Y-m-d') }} to {{ $lease->ends_at?->format('Y-m-d') }}</p>
    </div>

    <div class="card">
        <h2>Installments</h2>
        <table>
            <thead>
                <tr>
                    <th>Label</th>
                    <th>Due date</th>
                    <th>Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($lease->installments as $installment)
                    <tr>
                        <td>{{ $installment->label }}</td>
                        <td>{{ $installment->due_date?->format('Y-m-d') }}</td>
                        <td>{{ $installment->amount_due }} {{ $lease->currency }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</body>
</html>
