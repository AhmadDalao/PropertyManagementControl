<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #1b2430; font-size: 14px; }
        .card { border: 1px solid #d9dee7; border-radius: 12px; padding: 16px; }
    </style>
</head>
<body>
    <h1>Payment Receipt</h1>
    <div class="card">
        <p>Reference: {{ $payment->reference ?: $payment->id }}</p>
        <p>Tenant: {{ $payment->tenantProfile?->user?->name }}</p>
        <p>Lease: {{ $payment->lease?->code }}</p>
        <p>Received on: {{ $payment->received_on?->format('Y-m-d') }}</p>
        <p>Amount: {{ $payment->amount }} {{ $payment->currency }}</p>
        <p>Method: {{ $payment->method }}</p>
    </div>
</body>
</html>
