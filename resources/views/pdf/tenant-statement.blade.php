<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #1b2430; font-size: 14px; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th, td { border-bottom: 1px solid #d9dee7; padding: 8px 0; text-align: left; }
    </style>
</head>
<body>
    <h1>Tenant Statement</h1>
    <p>Lease: {{ $lease->code }}</p>
    <p>Tenant: {{ $lease->tenantProfile?->user?->name }}</p>
    <p>Balance remaining: {{ $lease->balance_remaining }} {{ $lease->currency }}</p>

    <table>
        <thead>
            <tr>
                <th>Label</th>
                <th>Status</th>
                <th>Due</th>
                <th>Paid</th>
            </tr>
        </thead>
        <tbody>
            @foreach($lease->installments as $installment)
                <tr>
                    <td>{{ $installment->label }}</td>
                    <td>{{ $installment->status }}</td>
                    <td>{{ $installment->amount_due }}</td>
                    <td>{{ $installment->amount_paid }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
