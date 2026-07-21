<?php

namespace App\Modules\Dashboard\Queries;

use App\Models\MaintenanceRequest;
use App\Models\Payment;
use App\Models\User;
use App\Modules\Shared\PortfolioScope;

class OperationsActivityQuery
{
    public function __construct(private readonly PortfolioScope $portfolios) {}

    /**
     * @return array{
     *     recentPayments:array<int, array<string, mixed>>,
     *     recentMaintenance:array<int, array<string, mixed>>
     * }
     */
    public function forUser(User $user): array
    {
        $payments = $this->portfolios->apply(Payment::query(), $user)
            ->with('tenantProfile.user')
            ->where('status', 'posted')
            ->latest('received_on')
            ->limit(8)
            ->get()
            ->map(fn (Payment $payment): array => [
                'id' => $payment->id,
                'amount' => (float) $payment->amount,
                'currency' => $payment->currency,
                'received_on' => $payment->received_on?->toDateString(),
                'tenant_profile' => [
                    'user' => ['name' => $payment->tenantProfile?->user?->name],
                ],
            ])
            ->all();
        $maintenance = $this->portfolios->apply(MaintenanceRequest::query(), $user)
            ->with('asset')
            ->latest()
            ->limit(8)
            ->get()
            ->map(fn (MaintenanceRequest $request): array => [
                'id' => $request->id,
                'title' => $request->title,
                'status' => $request->status,
                'priority' => $request->priority,
                'created_at' => $request->created_at?->toISOString(),
                'asset' => $request->asset ? [
                    'title_en' => $request->asset->title_en,
                    'title_ar' => $request->asset->title_ar,
                ] : null,
            ])
            ->all();

        return [
            'recentPayments' => $payments,
            'recentMaintenance' => $maintenance,
        ];
    }
}
