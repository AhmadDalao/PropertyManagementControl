<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\ExpenseEntry;
use App\Models\Lease;
use App\Models\MaintenanceRequest;
use App\Models\Payment;
use App\Models\ReportPreset;
use App\Services\XlsxWorkbook;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReportController extends Controller
{
    public function index(Request $request): Response
    {
        $actor = $this->actor($request);
        $this->requireRoles($actor, ['superadmin', 'owner', 'property_manager']);

        $filters = $this->reportFilters($request);
        $report = $this->buildReport($request, $filters);

        return Inertia::render('admin/reports/index', [
            ...$report,
            'filters' => $filters,
            'portfolioOptions' => $this->portfolioOptions($actor),
            'savedPresets' => $this->reportPresets($actor),
        ]);
    }

    public function storePreset(Request $request): RedirectResponse
    {
        $actor = $this->actor($request);
        $this->requireRoles($actor, ['superadmin', 'owner', 'property_manager']);

        $data = $request->validate([
            'resource' => ['required', 'string', 'max:80'],
            'title_en' => ['required', 'string', 'max:255'],
            'title_ar' => ['nullable', 'string', 'max:255'],
            'visibility' => ['required', 'in:private,portfolio,global'],
            'is_default' => ['nullable', 'boolean'],
            'filters_json' => ['nullable', 'array'],
        ]);

        abort_if(! $actor->hasRole('superadmin') && $data['visibility'] === 'global', 403);

        $portfolioId = $actor->hasRole('superadmin') && $data['visibility'] === 'global'
            ? null
            : $actor->portfolio_id;

        ReportPreset::query()->create([
            'portfolio_id' => $portfolioId,
            'user_id' => $actor->id,
            'resource' => $data['resource'],
            'title_en' => $data['title_en'],
            'title_ar' => $data['title_ar'] ?? null,
            'filters_json' => $data['filters_json'] ?? $this->reportFilters($request),
            'visibility' => $data['visibility'],
            'is_default' => (bool) ($data['is_default'] ?? false),
        ]);

        return to_route('reports.index', $this->reportFilters($request))->with('success', 'Report preset saved.');
    }

    public function destroyPreset(Request $request, ReportPreset $reportPreset): RedirectResponse
    {
        $actor = $this->actor($request);
        $this->requireRoles($actor, ['superadmin', 'owner', 'property_manager']);

        abort_unless(
            $actor->hasRole('superadmin')
            || $reportPreset->user_id === $actor->id
            || ($reportPreset->visibility === 'portfolio' && $reportPreset->portfolio_id === $actor->portfolio_id && $actor->hasRole('owner')),
            403
        );

        $reportPreset->delete();

        return to_route('reports.index', $this->reportFilters($request))->with('success', 'Report preset removed.');
    }

    public function export(Request $request, XlsxWorkbook $workbook): BinaryFileResponse
    {
        $actor = $this->actor($request);
        $this->requireRoles($actor, ['superadmin', 'owner', 'property_manager']);

        $filters = $this->reportFilters($request);
        $report = $this->buildReport($request, $filters);
        $filename = 'portfolio-report-'.now()->format('Ymd-His').'.xlsx';
        $path = $workbook->create($this->reportRows($report, $filters));

        return response()->download($path, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildReport(Request $request, array $filters): array
    {
        $actor = $this->actor($request);
        $paymentQuery = $this->scopedReportQuery(Payment::query(), $actor, $filters)
            ->where('status', 'posted')
            ->with(['lease.leaseable', 'tenantProfile.user']);
        $expenseQuery = $this->scopedReportQuery(ExpenseEntry::query(), $actor, $filters)
            ->where('status', 'posted')
            ->with(['asset']);
        $assetQuery = $this->scopedReportQuery(Asset::query(), $actor, $filters);
        $leaseQuery = $this->scopedReportQuery(Lease::query(), $actor, $filters)
            ->with(['installments', 'tenantProfile.user', 'leaseable']);
        $maintenanceQuery = $this->scopedReportQuery(MaintenanceRequest::query(), $actor, $filters)
            ->with(['asset', 'tenantProfile.user', 'assignedTo']);

        $this->applyReportDateRange($paymentQuery, $filters, 'received_on');
        $this->applyReportDateRange($expenseQuery, $filters, 'incurred_on');
        $this->applyReportDateRange($maintenanceQuery, $filters, 'created_at');

        $payments = $paymentQuery->get();
        $expenses = $expenseQuery->get();
        $assets = $assetQuery->get();
        $leases = $leaseQuery->get();
        $maintenanceRequests = $maintenanceQuery->get();

        $revenue = (float) $payments->sum('amount');
        $expensesTotal = (float) $expenses->sum('amount');
        $rentableAssets = $assets->where('rentable', true);
        $occupiedAssets = $rentableAssets->where('occupancy_status', 'occupied');
        $arrearsLeases = $leases
            ->filter(fn (Lease $lease) => $lease->balance_remaining > 0)
            ->sortByDesc(fn (Lease $lease) => $lease->balance_remaining)
            ->values();
        $maintenanceBacklog = $maintenanceRequests
            ->whereIn('status', ['open', 'in_progress'])
            ->sortByDesc('created_at')
            ->values();

        return [
            'mode' => $actor->hasRole('superadmin') ? 'superadmin' : 'portfolio',
            'summary' => [
                'revenue' => $revenue,
                'expenses' => $expensesTotal,
                'net' => $revenue - $expensesTotal,
                'occupancyRate' => $rentableAssets->count() > 0
                    ? round(($occupiedAssets->count() / $rentableAssets->count()) * 100, 2)
                    : 0,
                'arrears' => (float) $arrearsLeases->sum(fn (Lease $lease) => $lease->balance_remaining),
                'activeLeases' => $leases->where('status', 'active')->count(),
                'unpaidLeases' => $arrearsLeases->count(),
                'openRequests' => $maintenanceBacklog->count(),
                'resolvedRequests' => $maintenanceRequests->where('status', 'resolved')->count(),
            ],
            'charts' => [
                'revenueByMonth' => $payments
                    ->groupBy(fn (Payment $payment) => $payment->received_on?->format('Y-m') ?? 'Unscheduled')
                    ->sortKeys()
                    ->map(fn ($group) => (float) $group->sum('amount')),
                'expenseByCategory' => $expenses
                    ->groupBy(fn (ExpenseEntry $expense) => $expense->category ?: 'uncategorized')
                    ->sortKeys()
                    ->map(fn ($group) => (float) $group->sum('amount')),
                'assetMix' => $assets
                    ->groupBy('asset_type')
                    ->sortKeys()
                    ->map(fn ($group) => $group->count()),
                'maintenanceByStatus' => $maintenanceRequests
                    ->groupBy('status')
                    ->sortKeys()
                    ->map(fn ($group) => $group->count()),
            ],
            'arrearsLeases' => $arrearsLeases
                ->take(10)
                ->map(fn (Lease $lease) => [
                    'id' => $lease->id,
                    'code' => $lease->code,
                    'tenant' => $lease->tenantProfile?->user?->name,
                    'asset' => $lease->leaseable?->title_en,
                    'ends_at' => $lease->ends_at?->toDateString(),
                    'balance_remaining' => $lease->balance_remaining,
                    'currency' => $lease->currency,
                ])
                ->all(),
            'topAssets' => $this->topAssetsByRevenue($payments),
            'recentPayments' => $payments
                ->sortByDesc('received_on')
                ->take(8)
                ->map(fn (Payment $payment) => [
                    'id' => $payment->id,
                    'reference' => $payment->reference ?: '#'.$payment->id,
                    'tenant' => $payment->tenantProfile?->user?->name,
                    'lease' => $payment->lease?->code,
                    'amount' => (float) $payment->amount,
                    'currency' => $payment->currency,
                    'received_on' => $payment->received_on?->toDateString(),
                ])
                ->values()
                ->all(),
            'recentExpenses' => $expenses
                ->sortByDesc('incurred_on')
                ->take(8)
                ->map(fn (ExpenseEntry $expense) => [
                    'id' => $expense->id,
                    'title' => $expense->title,
                    'category' => $expense->category,
                    'asset' => $expense->asset?->title_en,
                    'amount' => (float) $expense->amount,
                    'currency' => $expense->currency,
                    'incurred_on' => $expense->incurred_on?->toDateString(),
                ])
                ->values()
                ->all(),
            'maintenanceBacklog' => $maintenanceBacklog
                ->take(8)
                ->map(fn (MaintenanceRequest $maintenanceRequest) => [
                    'id' => $maintenanceRequest->id,
                    'title' => $maintenanceRequest->title,
                    'asset' => $maintenanceRequest->asset?->title_en,
                    'tenant' => $maintenanceRequest->tenantProfile?->user?->name,
                    'status' => $maintenanceRequest->status,
                    'priority' => $maintenanceRequest->priority,
                    'created_at' => $maintenanceRequest->created_at?->toDateString(),
                ])
                ->values()
                ->all(),
        ];
    }

    /**
     * @param  array<string, mixed>  $report
     * @param  array<string, mixed>  $filters
     * @return array<int, array<int, mixed>>
     */
    private function reportRows(array $report, array $filters): array
    {
        $rows = [
            ['Property Management Control Report'],
            ['Date From', $filters['date_from'] ?: 'All'],
            ['Date To', $filters['date_to'] ?: 'All'],
            [],
            ['Section', 'Metric', 'Value'],
        ];

        foreach ($report['summary'] as $metric => $value) {
            $rows[] = ['Summary', str($metric)->snake(' ')->title()->toString(), $value];
        }

        $rows[] = [];
        $rows[] = ['Revenue by Month', 'Month', 'Amount'];
        foreach ($report['charts']['revenueByMonth'] as $month => $amount) {
            $rows[] = ['Revenue by Month', $month, $amount];
        }

        $rows[] = [];
        $rows[] = ['Expense by Category', 'Category', 'Amount'];
        foreach ($report['charts']['expenseByCategory'] as $category => $amount) {
            $rows[] = ['Expense by Category', $category, $amount];
        }

        $rows[] = [];
        $rows[] = ['Arrears', 'Lease', 'Tenant', 'Asset', 'Balance', 'Currency'];
        foreach ($report['arrearsLeases'] as $lease) {
            $rows[] = [
                'Arrears',
                $lease['code'],
                $lease['tenant'],
                $lease['asset'],
                $lease['balance_remaining'],
                $lease['currency'],
            ];
        }

        $rows[] = [];
        $rows[] = ['Maintenance Backlog', 'ID', 'Title', 'Asset', 'Status', 'Priority'];
        foreach ($report['maintenanceBacklog'] as $request) {
            $rows[] = [
                'Maintenance Backlog',
                $request['id'],
                $request['title'],
                $request['asset'],
                $request['status'],
                $request['priority'],
            ];
        }

        return $rows;
    }

    /**
     * @return array<string, string|int|null>
     */
    private function reportFilters(Request $request): array
    {
        return [
            'date_from' => trim((string) $request->query('date_from', now()->startOfYear()->toDateString())),
            'date_to' => trim((string) $request->query('date_to', now()->toDateString())),
            'portfolio_id' => $this->nullableInteger($request->query('portfolio_id')),
        ];
    }

    private function reportPresets(mixed $actor): array
    {
        return ReportPreset::query()
            ->where('resource', 'portfolio-report')
            ->where(function (Builder $query) use ($actor): void {
                $query
                    ->where('user_id', $actor->id)
                    ->orWhere(function (Builder $globalQuery): void {
                        $globalQuery
                            ->where('visibility', 'global')
                            ->whereNull('portfolio_id');
                    });

                if ($actor->portfolio_id) {
                    $query->orWhere(function (Builder $portfolioQuery) use ($actor): void {
                        $portfolioQuery
                            ->where('portfolio_id', $actor->portfolio_id)
                            ->where('visibility', 'portfolio');
                    });
                }
            })
            ->latest()
            ->get()
            ->map(fn (ReportPreset $preset) => [
                'id' => $preset->id,
                'title_en' => $preset->title_en,
                'title_ar' => $preset->title_ar,
                'visibility' => $preset->visibility,
                'is_default' => $preset->is_default,
                'filters' => $preset->filters_json ?? [],
                'url' => route('reports.index', $preset->filters_json ?? []),
            ])
            ->all();
    }

    private function scopedReportQuery(Builder $query, mixed $actor, array $filters): Builder
    {
        $this->scopeByPortfolio($query, $actor);

        if (! empty($filters['portfolio_id'])) {
            $query->where('portfolio_id', $filters['portfolio_id']);
        }

        return $query;
    }

    private function applyReportDateRange(Builder $query, array $filters, string $column): Builder
    {
        if (! empty($filters['date_from'])) {
            $query->whereDate($column, '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate($column, '<=', $filters['date_to']);
        }

        return $query;
    }

    /**
     * @return array<int, array{asset:string,revenue:float,currency:string,lease_count:int}>
     */
    private function topAssetsByRevenue($payments): array
    {
        return $payments
            ->filter(fn (Payment $payment) => $payment->lease?->leaseable)
            ->groupBy(fn (Payment $payment) => $payment->lease?->leaseable?->id)
            ->map(function ($group) {
                /** @var Payment $first */
                $first = $group->first();

                return [
                    'asset' => $first->lease?->leaseable?->title_en ?? 'Unknown asset',
                    'revenue' => (float) $group->sum('amount'),
                    'currency' => $first->currency,
                    'lease_count' => $group->pluck('lease_id')->unique()->count(),
                ];
            })
            ->sortByDesc('revenue')
            ->take(8)
            ->values()
            ->all();
    }
}
