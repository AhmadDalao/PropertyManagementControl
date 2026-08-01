<?php

namespace App\Modules\Tenants\Queries;

use App\Models\Document;
use App\Models\Lease;
use App\Models\LeaseInstallment;
use App\Models\Payment;
use App\Models\TenantProfile;
use App\Models\User;
use App\Modules\Portfolios\Support\PortfolioModules;
use App\Modules\Shared\Authorization\AssignedPropertyScope;
use App\Modules\Tenants\Support\TenantAccess;
use App\Modules\Tenants\Support\TenantStatementFinancials;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

final readonly class TenantAccountStatementQuery
{
    private const ROW_LIMIT = 100;

    public function __construct(
        private TenantAccess $access,
        private AssignedPropertyScope $assignments,
        private TenantStatementFinancials $financials,
        private TenantStatementDocumentQuery $documents,
    ) {}

    /**
     * @param  array{date_from:string,date_to:string}  $filters
     * @return array<string, mixed>
     */
    public function handle(User $actor, TenantProfile $target, array $filters): array
    {
        $this->access->ensureCanManage($actor, $target);
        $tenant = TenantProfile::query()
            ->with(['portfolio:id,code,name_en,name_ar', 'user:id,name,email,phone,preferred_locale'])
            ->whereKey($target->id)
            ->firstOrFail();
        $this->access->ensureCanManage($actor, $tenant);

        $leasesEnabled = PortfolioModules::enabledForUser($actor, 'leases');
        $paymentsEnabled = PortfolioModules::enabledForUser($actor, 'payments');
        $maintenanceEnabled = PortfolioModules::enabledForUser($actor, 'maintenance');
        $documentsEnabled = PortfolioModules::enabledForUser($actor, 'documents');
        $periodStart = CarbonImmutable::parse($filters['date_from'])->startOfDay();
        $periodEnd = CarbonImmutable::parse($filters['date_to'])->endOfDay();
        $arrearsCutoff = $periodEnd->isBefore(today()->startOfDay())
            ? $periodEnd->addDay()
            : CarbonImmutable::today();

        $leases = $leasesEnabled
            ? $this->leaseQuery($tenant, $actor)
                ->with(['leaseable', 'installments'])
                ->latest('started_at')
                ->get()
            : collect();
        $leaseIds = $leases->pluck('id')->map(fn (mixed $id): int => (int) $id)->all();
        $paymentsBase = $paymentsEnabled
            ? $this->assignments->payments(
                $tenant->payments()->getQuery()->with('lease'),
                $actor,
            )
            : null;
        $periodPayments = $paymentsBase
            ? (clone $paymentsBase)
                ->whereDate('received_on', '>=', $filters['date_from'])
                ->whereDate('received_on', '<=', $filters['date_to'])
                ->latest('received_on')
                ->latest('id')
                ->limit(self::ROW_LIMIT)
                ->get()
            : collect();
        $periodPaymentCount = $paymentsBase
            ? (clone $paymentsBase)
                ->whereDate('received_on', '>=', $filters['date_from'])
                ->whereDate('received_on', '<=', $filters['date_to'])
                ->count()
            : 0;
        $periodPaymentTotals = $paymentsBase
            ? (clone $paymentsBase)
                ->whereDate('received_on', '>=', $filters['date_from'])
                ->whereDate('received_on', '<=', $filters['date_to'])
                ->where('status', 'posted')
                ->selectRaw('currency, SUM(amount) as total')
                ->groupBy('currency')
                ->pluck('total', 'currency')
                ->map(fn (mixed $total): float => (float) $total)
                ->all()
            : [];
        $allPaymentIds = $paymentsBase
            ? (clone $paymentsBase)->pluck('id')->map(fn (mixed $id): int => (int) $id)->all()
            : [];
        $installments = $this->financials->periodInstallments($leases, $periodStart, $periodEnd);
        $financials = $this->financials->summaries($leases, $periodPaymentTotals, $installments, $arrearsCutoff);
        $maintenanceBase = $maintenanceEnabled
            ? $this->assignments->maintenance(
                $tenant->maintenanceRequests()->getQuery()->with('asset'),
                $actor,
            )
            : null;
        $maintenance = $maintenanceBase
            ? (clone $maintenanceBase)
                ->whereBetween('requested_at', [$periodStart, $periodEnd])
                ->latest('requested_at')
                ->limit(self::ROW_LIMIT)
                ->get()
            : collect();
        $maintenanceCount = $maintenanceBase
            ? (clone $maintenanceBase)
                ->whereBetween('requested_at', [$periodStart, $periodEnd])
                ->count()
            : 0;
        $openMaintenanceCount = $maintenanceBase
            ? (clone $maintenanceBase)
                ->whereBetween('requested_at', [$periodStart, $periodEnd])
                ->whereIn('status', ['open', 'in_progress'])
                ->count()
            : 0;
        $documentsBase = $documentsEnabled && ($leaseIds !== [] || $allPaymentIds !== [])
            ? $this->documents->handle($actor, $leaseIds, $allPaymentIds)
            : null;
        $documents = $documentsBase
            ? (clone $documentsBase)->latest()->limit(self::ROW_LIMIT)->get()
            : collect();
        $documentCount = $documentsBase ? (clone $documentsBase)->count() : 0;

        return [
            'filters' => $filters,
            'tenant' => [
                'id' => $tenant->id,
                'name' => $tenant->user?->name ?: ($tenant->company_name ?: trans('app.tenants.tenant_number', ['id' => $tenant->id])),
                'email' => $tenant->user?->email,
                'phone' => $tenant->user?->phone,
                'profile_type' => $tenant->profile_type,
                'status' => $tenant->status,
                'national_id' => $tenant->national_id,
                'company_name' => $tenant->company_name,
                'is_showcase' => (bool) $tenant->is_showcase,
                'portfolio' => [
                    'id' => $tenant->portfolio_id,
                    'code' => $tenant->portfolio?->code,
                    'name_en' => $tenant->portfolio?->name_en,
                    'name_ar' => $tenant->portfolio?->name_ar,
                ],
                'back_url' => route('tenants.show', $tenant, false),
            ],
            'statement' => [
                'prepared_for' => $actor->name,
                'generated_at' => now()->toIso8601String(),
                'lease_count' => $leases->count(),
                'active_lease_count' => $leases->where('status', 'active')->count(),
                'open_maintenance_count' => $openMaintenanceCount,
                'document_count' => $documentCount,
                'financials' => $financials,
            ],
            'leases' => $leases->map(fn (Lease $lease): array => [
                'id' => $lease->id,
                'code' => $lease->code,
                'asset_en' => $lease->leaseable?->getAttribute('title_en'),
                'asset_ar' => $lease->leaseable?->getAttribute('title_ar'),
                'status' => $lease->status,
                'started_at' => $lease->started_at?->toDateString(),
                'ends_at' => $lease->ends_at?->toDateString(),
                'total_due' => $lease->total_due,
                'total_paid' => $lease->total_paid,
                'balance' => $lease->balance_remaining,
                'overdue' => $this->financials->overdue($lease, $arrearsCutoff),
                'currency' => $lease->currency ?: 'SAR',
                'href' => route('leases.show', $lease, false),
            ])->values()->all(),
            'installments' => $installments
                ->sortBy('due_date')
                ->take(self::ROW_LIMIT)
                ->map(fn (LeaseInstallment $installment): array => [
                    'id' => $installment->id,
                    'lease_code' => $installment->lease?->code,
                    'due_date' => $installment->due_date?->toDateString(),
                    'label' => $installment->label,
                    'status' => $installment->status,
                    'amount_due' => (float) $installment->amount_due,
                    'amount_paid' => (float) $installment->amount_paid,
                    'remaining' => $installment->remaining_amount,
                    'currency' => $installment->lease?->currency ?: 'SAR',
                    'href' => $installment->lease ? route('leases.show', $installment->lease, false) : null,
                ])
                ->values()
                ->all(),
            'payments' => $periodPayments->map(fn (Payment $payment): array => [
                'id' => $payment->id,
                'reference' => $payment->reference ?: trans('app.payments.payment_number', ['id' => $payment->id]),
                'lease_code' => $payment->lease?->code,
                'received_on' => $payment->received_on?->toDateString(),
                'method' => $payment->method,
                'status' => $payment->status,
                'amount' => (float) $payment->amount,
                'currency' => $payment->currency ?: 'SAR',
                'href' => route('payments.show', $payment, false),
            ])->values()->all(),
            'maintenance' => $maintenance->map(fn ($request): array => [
                'id' => $request->id,
                'title' => $request->title,
                'asset_en' => $request->asset?->title_en,
                'asset_ar' => $request->asset?->title_ar,
                'status' => $request->status,
                'priority' => $request->priority,
                'requested_at' => $request->requested_at?->toDateString(),
                'href' => route('maintenance-requests.show', $request, false),
            ])->values()->all(),
            'documents' => $documents->map(fn (Document $document): array => [
                'id' => $document->id,
                'title_en' => $document->title_en,
                'title_ar' => $document->title_ar,
                'type' => $document->type,
                'created_at' => $document->created_at?->toDateString(),
                'download_url' => route('documents.download', $document, false),
            ])->values()->all(),
            'counts' => [
                'installments' => $installments->count(),
                'payments' => $periodPaymentCount,
                'maintenance' => $maintenanceCount,
                'documents' => $documentCount,
            ],
            'limits' => [
                'rows' => self::ROW_LIMIT,
            ],
        ];
    }

    /** @return Builder<Lease> */
    private function leaseQuery(TenantProfile $tenant, User $actor): Builder
    {
        return $this->assignments->leases($tenant->leases()->getQuery(), $actor);
    }
}
