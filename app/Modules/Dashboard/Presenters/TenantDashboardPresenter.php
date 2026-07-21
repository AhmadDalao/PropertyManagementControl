<?php

namespace App\Modules\Dashboard\Presenters;

use App\Models\Document;
use App\Models\Lease;
use App\Models\MaintenanceRequest;
use App\Models\Payment;
use App\Models\User;
use App\Modules\Dashboard\Queries\TenantDashboardQuery;

class TenantDashboardPresenter
{
    public function __construct(
        private readonly TenantDashboardQuery $dashboard,
        private readonly DashboardActionPresenter $actions,
    ) {}

    /** @return array<string, mixed> */
    public function present(User $user): array
    {
        $data = $this->dashboard->forUser($user);
        $lease = $data['lease'];

        return [
            'mode' => 'tenant',
            'stats' => [
                'leaseCode' => $lease?->code,
                'daysLeft' => $lease?->days_remaining,
                'amountLeft' => $lease ? (float) $lease->balance_remaining : 0.0,
                'dueNow' => $lease ? (float) $lease->due_now_amount : 0.0,
                'overdue' => $lease ? (float) $lease->overdue_amount : 0.0,
                'paidAmount' => $lease ? (float) $lease->total_paid : 0.0,
                'maintenanceRequests' => $data['requestCount'],
            ],
            'tenantPortal' => [
                'lease' => $lease ? $this->lease($lease) : null,
                'payments' => $data['payments']->map($this->payment(...))->all(),
                'requests' => $data['requests']->map($this->request(...))->all(),
                'documents' => $data['documents']->map($this->document(...))->all(),
            ],
            'nextActions' => $this->actions->tenant($lease !== null),
        ];
    }

    /** @return array<string, mixed> */
    private function lease(Lease $lease): array
    {
        return [
            'id' => $lease->id,
            'code' => $lease->code,
            'days_remaining' => $lease->days_remaining,
            'balance_remaining' => (float) $lease->balance_remaining,
            'due_now' => (float) $lease->due_now_amount,
            'overdue' => (float) $lease->overdue_amount,
            'next_due_date' => $lease->next_due_installment?->due_date?->toDateString(),
            'total_paid' => (float) $lease->total_paid,
            'rent_amount' => (float) $lease->rent_amount,
            'currency' => $lease->currency,
            'started_at' => $lease->started_at?->toDateString(),
            'ends_at' => $lease->ends_at?->toDateString(),
            'leaseable' => $lease->leaseable,
            'contract_url' => route('leases.contract', $lease),
            'statement_url' => route('leases.statement', $lease),
        ];
    }

    /** @return array<string, mixed> */
    private function payment(Payment $payment): array
    {
        return [
            'id' => $payment->id,
            'amount' => (float) $payment->amount,
            'currency' => $payment->currency,
            'received_on' => $payment->received_on?->toDateString(),
            'reference' => $payment->reference,
            'receipt_url' => route('payments.receipt', $payment),
        ];
    }

    /** @return array<string, mixed> */
    private function request(MaintenanceRequest $request): array
    {
        return [
            'id' => $request->id,
            'title' => $request->title,
            'status' => $request->status,
            'created_at' => $request->created_at?->toISOString(),
        ];
    }

    /** @return array<string, mixed> */
    private function document(Document $document): array
    {
        return [
            'id' => $document->id,
            'title_en' => $document->title_en,
            'title_ar' => $document->title_ar,
            'type' => $document->type,
            'download_url' => route('documents.download', $document),
        ];
    }
}
