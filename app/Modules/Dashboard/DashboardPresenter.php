<?php

namespace App\Modules\Dashboard;

use App\Models\Asset;
use App\Models\CmsPage;
use App\Models\ExpenseEntry;
use App\Models\Lease;
use App\Models\MaintenanceRequest;
use App\Models\Payment;
use App\Models\Portfolio;
use App\Models\TenantProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class DashboardPresenter
{
    /**
     * @return array<string, mixed>
     */
    public function forUser(User $user): array
    {
        if ($user->hasRole('tenant')) {
            return $this->tenantDashboard($user);
        }

        return $this->operationsDashboard($user);
    }

    /**
     * @return array<string, mixed>
     */
    private function tenantDashboard(User $user): array
    {
        $tenantProfile = TenantProfile::query()
            ->where('user_id', $user->id)
            ->with([
                'leases' => fn ($query) => $query
                    ->whereIn('status', ['active', 'draft'])
                    ->with(['installments', 'documents', 'leaseable']),
                'maintenanceRequests',
                'payments',
            ])
            ->first();

        $activeLease = $tenantProfile?->leases->firstWhere('status', 'active')
            ?? $tenantProfile?->leases->first();

        return [
            'mode' => 'tenant',
            'stats' => [
                'leaseCode' => $activeLease?->code,
                'daysLeft' => $activeLease?->days_remaining,
                'amountLeft' => (float) ($activeLease?->balance_remaining ?? 0),
                'paidAmount' => (float) ($activeLease?->total_paid ?? 0),
                'maintenanceRequests' => $tenantProfile?->maintenanceRequests->count() ?? 0,
            ],
            'tenantPortal' => [
                'tenant' => $tenantProfile,
                'lease' => $activeLease ? [
                    'id' => $activeLease->id,
                    'code' => $activeLease->code,
                    'days_remaining' => $activeLease->days_remaining,
                    'balance_remaining' => (float) $activeLease->balance_remaining,
                    'total_paid' => (float) $activeLease->total_paid,
                    'rent_amount' => (float) $activeLease->rent_amount,
                    'currency' => $activeLease->currency,
                    'started_at' => $activeLease->started_at?->toDateString(),
                    'ends_at' => $activeLease->ends_at?->toDateString(),
                    'leaseable' => $activeLease->leaseable,
                    'contract_url' => route('leases.contract', $activeLease),
                    'statement_url' => route('leases.statement', $activeLease),
                ] : null,
                'payments' => $tenantProfile?->payments()
                    ->latest('received_on')
                    ->limit(8)
                    ->get()
                    ->map(fn (Payment $payment) => [
                        'id' => $payment->id,
                        'amount' => (float) $payment->amount,
                        'currency' => $payment->currency,
                        'received_on' => $payment->received_on?->toDateString(),
                        'reference' => $payment->reference,
                        'receipt_url' => route('payments.receipt', $payment),
                    ]) ?? [],
                'requests' => $tenantProfile?->maintenanceRequests()->latest()->limit(8)->get() ?? [],
                'documents' => $activeLease?->documents()
                    ->latest()
                    ->get()
                    ->map(fn ($document) => [
                        'id' => $document->id,
                        'title_en' => $document->title_en,
                        'type' => $document->type,
                        'download_url' => route('documents.download', $document),
                    ]) ?? [],
            ],
            'nextActions' => $this->tenantNextActions($activeLease !== null),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function operationsDashboard(User $user): array
    {
        $assetQuery = $this->scopeByPortfolio(Asset::query(), $user);
        $leaseQuery = $this->scopeByPortfolio(Lease::query(), $user);
        $paymentQuery = $this->scopeByPortfolio(Payment::query(), $user);
        $maintenanceQuery = $this->scopeByPortfolio(MaintenanceRequest::query(), $user);
        $expenseQuery = $this->scopeByPortfolio(ExpenseEntry::query(), $user);

        $stats = [
            'totalUsers' => $user->hasRole('superadmin')
                ? User::query()->count()
                : User::query()->where('portfolio_id', $user->portfolio_id)->count(),
            'totalPortfolios' => $user->hasRole('superadmin') ? Portfolio::query()->count() : 1,
            'totalAssets' => (clone $assetQuery)->count(),
            'totalValue' => (float) (clone $assetQuery)->sum('valuation_amount'),
            'activeLeases' => (clone $leaseQuery)->where('status', 'active')->count(),
            'monthlyRevenue' => (float) (clone $paymentQuery)
                ->where('status', 'posted')
                ->whereMonth('received_on', now()->month)
                ->whereYear('received_on', now()->year)
                ->sum('amount'),
            'monthlyExpenses' => (float) (clone $expenseQuery)
                ->where('status', 'posted')
                ->whereMonth('incurred_on', now()->month)
                ->whereYear('incurred_on', now()->year)
                ->sum('amount'),
            'openRequests' => (clone $maintenanceQuery)->whereIn('status', ['open', 'in_progress'])->count(),
            'arrears' => (float) (clone $leaseQuery)
                ->with('installments')
                ->get()
                ->sum(fn (Lease $lease) => $lease->balance_remaining),
            'vacantUnits' => (clone $assetQuery)->where('rentable', true)->where('occupancy_status', 'vacant')->count(),
        ];

        $setupChecklist = $this->setupChecklist($user, $stats);

        return [
            'mode' => $user->hasRole('superadmin') ? 'superadmin' : 'portfolio',
            'stats' => $stats,
            'nextActions' => $this->operationsNextActions($setupChecklist, $stats),
            'charts' => [
                'occupancy' => (clone $assetQuery)
                    ->selectRaw('occupancy_status, COUNT(*) as total')
                    ->groupBy('occupancy_status')
                    ->pluck('total', 'occupancy_status'),
                'paymentHealth' => (clone $leaseQuery)
                    ->with(['tenantProfile.user', 'installments'])
                    ->get()
                    ->map(fn (Lease $lease) => [
                        'code' => $lease->code,
                        'tenant' => $lease->tenantProfile?->user?->name,
                        'due' => $lease->total_due,
                        'paid' => $lease->total_paid,
                        'remaining' => $lease->balance_remaining,
                    ]),
                'assetMix' => (clone $assetQuery)
                    ->selectRaw('asset_type, COUNT(*) as total')
                    ->groupBy('asset_type')
                    ->pluck('total', 'asset_type'),
                'maintenanceByStatus' => (clone $maintenanceQuery)
                    ->selectRaw('status, COUNT(*) as total')
                    ->groupBy('status')
                    ->pluck('total', 'status'),
            ],
            'setupChecklist' => $setupChecklist,
            'propertyMap' => $this->propertyMap($assetQuery),
            'expiringLeases' => $this->expiringLeases($leaseQuery),
            'arrearsLeases' => $this->arrearsLeases($leaseQuery),
            'cmsStatus' => [
                'published' => CmsPage::query()->where('status', 'published')->count(),
                'draft' => CmsPage::query()->where('status', 'draft')->count(),
                'homepage' => CmsPage::query()->where('is_homepage', true)->value('title_en'),
            ],
            'recentPayments' => (clone $paymentQuery)->with('tenantProfile.user')->latest('received_on')->limit(8)->get(),
            'recentMaintenance' => (clone $maintenanceQuery)->with('asset')->latest()->limit(8)->get(),
        ];
    }

    /**
     * @return array{assets:array<int, array<string, mixed>>,summary:array<string, mixed>}
     */
    private function propertyMap(Builder $assetQuery): array
    {
        $query = (clone $assetQuery)
            ->with(['portfolio', 'stakeholders.user'])
            ->withCount([
                'children',
                'children as rentable_children_count' => fn (Builder $query) => $query->where('rentable', true),
                'leases as active_leases_count' => fn (Builder $query) => $query->where('status', 'active'),
                'maintenanceRequests as open_requests_count' => fn (Builder $query) => $query->whereIn('status', ['open', 'in_progress']),
            ])
            ->whereIn('asset_type', ['property', 'building', 'space'])
            ->orderByRaw("CASE asset_type WHEN 'property' THEN 0 WHEN 'building' THEN 1 WHEN 'space' THEN 2 ELSE 3 END")
            ->orderBy('title_en');

        $assets = $query->get();

        if ($assets->isEmpty()) {
            $assets = (clone $assetQuery)
                ->with(['portfolio', 'stakeholders.user'])
                ->withCount([
                    'children',
                    'children as rentable_children_count' => fn (Builder $query) => $query->where('rentable', true),
                    'leases as active_leases_count' => fn (Builder $query) => $query->where('status', 'active'),
                    'maintenanceRequests as open_requests_count' => fn (Builder $query) => $query->whereIn('status', ['open', 'in_progress']),
                ])
                ->orderBy('title_en')
                ->limit(18)
                ->get();
        }

        $mappedAssets = $assets
            ->values()
            ->map(fn (Asset $asset, int $index) => $this->propertyMapAsset($asset, $index))
            ->all();

        return [
            'assets' => $mappedAssets,
            'summary' => [
                'mapped' => collect($mappedAssets)->filter(fn (array $asset) => $asset['has_coordinates'])->count(),
                'total' => count($mappedAssets),
                'zones' => collect($mappedAssets)->pluck('zone')->filter()->unique()->values()->all(),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function propertyMapAsset(Asset $asset, int $index): array
    {
        $map = is_array($asset->meta_json['map'] ?? null) ? $asset->meta_json['map'] : [];
        $fallbackX = 14 + (($index * 23) % 72);
        $fallbackY = 18 + (($index * 31) % 62);
        $owner = $asset->stakeholders->firstWhere('relationship_type', 'owner');
        $manager = $asset->stakeholders->firstWhere('relationship_type', 'manager');

        return [
            'id' => $asset->id,
            'title' => $asset->title_en,
            'code' => $asset->code,
            'portfolio' => $asset->portfolio?->name_en,
            'asset_type' => $asset->asset_type,
            'usage_type' => $asset->usage_type,
            'status' => $asset->status,
            'occupancy_status' => $asset->occupancy_status,
            'valuation_amount' => (float) $asset->valuation_amount,
            'currency' => $asset->currency,
            'address' => $asset->address,
            'zone' => $map['zone'] ?? 'Zone '.chr(65 + ($index % 6)),
            'land_number' => $map['land_number'] ?? $asset->unit_label ?? $asset->code,
            'latitude' => isset($map['latitude']) ? (float) $map['latitude'] : null,
            'longitude' => isset($map['longitude']) ? (float) $map['longitude'] : null,
            'x' => $this->mapPercent($map['x'] ?? null, $fallbackX),
            'y' => $this->mapPercent($map['y'] ?? null, $fallbackY),
            'has_coordinates' => isset($map['latitude'], $map['longitude']) || isset($map['x'], $map['y']),
            'href' => route('assets.show', $asset),
            'children_count' => (int) $asset->children_count,
            'rentable_children_count' => (int) $asset->rentable_children_count,
            'active_leases_count' => (int) $asset->active_leases_count,
            'open_requests_count' => (int) $asset->open_requests_count,
            'owner' => $owner?->user?->name,
            'manager' => $manager?->user?->name,
        ];
    }

    private function mapPercent(mixed $value, float $fallback): float
    {
        if (is_numeric($value)) {
            return max(4, min(96, (float) $value));
        }

        return $fallback;
    }

    /**
     * @param  array<string, mixed>  $stats
     * @return array<int, array{label:string, done:bool, href:string}>
     */
    private function setupChecklist(User $user, array $stats): array
    {
        $setupChecklist = [
            ['label' => 'Create portfolio', 'done' => $user->hasRole('superadmin') ? Portfolio::query()->exists() : (bool) $user->portfolio_id, 'href' => '/portfolios/create'],
            ['label' => 'Create users', 'done' => $stats['totalUsers'] > 1, 'href' => '/users/create'],
            ['label' => 'Create assets', 'done' => $stats['totalAssets'] > 0, 'href' => '/assets/create'],
            ['label' => 'Create profiles', 'done' => (clone $this->scopeByPortfolio(TenantProfile::query(), $user))->exists(), 'href' => '/tenants/create'],
            ['label' => 'Create leases', 'done' => $stats['activeLeases'] > 0, 'href' => '/leases/create'],
        ];

        if ($user->hasRole('superadmin')) {
            $setupChecklist[] = [
                'label' => 'Publish website',
                'done' => CmsPage::query()->where('status', 'published')->exists(),
                'href' => '/cms',
            ];
        }

        return $setupChecklist;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function expiringLeases(Builder $leaseQuery): array
    {
        return (clone $leaseQuery)
            ->with(['tenantProfile.user', 'leaseable', 'installments'])
            ->where('status', 'active')
            ->whereDate('ends_at', '<=', now()->addDays(90))
            ->orderBy('ends_at')
            ->limit(8)
            ->get()
            ->map(fn (Lease $lease) => [
                'id' => $lease->id,
                'code' => $lease->code,
                'tenant' => $lease->tenantProfile?->user?->name,
                'asset' => $lease->leaseable?->title_en,
                'ends_at' => $lease->ends_at?->toDateString(),
                'days_remaining' => $lease->days_remaining,
                'balance_remaining' => $lease->balance_remaining,
                'currency' => $lease->currency,
            ])
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function arrearsLeases(Builder $leaseQuery): array
    {
        return (clone $leaseQuery)
            ->with(['tenantProfile.user', 'leaseable', 'installments'])
            ->whereIn('status', ['active', 'expired'])
            ->get()
            ->filter(fn (Lease $lease) => $lease->balance_remaining > 0)
            ->sortByDesc(fn (Lease $lease) => $lease->balance_remaining)
            ->take(8)
            ->values()
            ->map(fn (Lease $lease) => [
                'id' => $lease->id,
                'code' => $lease->code,
                'tenant' => $lease->tenantProfile?->user?->name,
                'asset' => $lease->leaseable?->title_en,
                'balance_remaining' => $lease->balance_remaining,
                'currency' => $lease->currency,
            ])
            ->all();
    }

    /**
     * @param  array<int, array{label:string, done:bool, href:string}>  $setupChecklist
     * @param  array<string, mixed>  $stats
     * @return array<int, array{label:string, description:string, href:string, icon:string}>
     */
    private function operationsNextActions(array $setupChecklist, array $stats): array
    {
        $actions = [];

        if ((float) ($stats['arrears'] ?? 0) > 0) {
            $actions[] = [
                'label' => 'Collect outstanding rent',
                'description' => 'Open payment and arrears views before balances get stale.',
                'href' => '/payments',
                'icon' => 'bi-cash-stack',
            ];
        }

        if ((int) ($stats['openRequests'] ?? 0) > 0) {
            $actions[] = [
                'label' => 'Triage maintenance backlog',
                'description' => 'Assign priority, publish tenant updates, and record service cost.',
                'href' => '/maintenance-requests',
                'icon' => 'bi-tools',
            ];
        }

        foreach ($setupChecklist as $item) {
            if ($item['done']) {
                continue;
            }

            $actions[] = [
                'label' => $item['label'],
                'description' => match ($item['label']) {
                    'Create portfolio' => 'Set the owner account boundary before adding data.',
                    'Create users' => 'Add owner, manager, and tenant accounts with clean roles.',
                    'Create assets' => 'Build buildings, floors, units, spaces, and stakeholder assignments.',
                    'Create profiles' => 'Create tenant profiles before writing contracts.',
                    'Create leases' => 'Connect tenants to rentable assets and generate installments.',
                    'Publish website' => 'Use the CMS builder to publish the public landing page.',
                    default => 'Complete this setup step before scaling operations.',
                },
                'href' => $item['href'],
                'icon' => match ($item['label']) {
                    'Create portfolio' => 'bi-buildings',
                    'Create users' => 'bi-people',
                    'Create assets' => 'bi-diagram-3',
                    'Create profiles' => 'bi-person-badge',
                    'Create leases' => 'bi-file-earmark-text',
                    'Publish website' => 'bi-layout-wtf',
                    default => 'bi-arrow-right-circle',
                },
            ];
        }

        $actions[] = [
            'label' => 'Open operating manual',
            'description' => 'Use workflows, page shortcuts, and control checks before changing production data.',
            'href' => '/documentation',
            'icon' => 'bi-journal-richtext',
        ];

        return array_slice($actions, 0, 4);
    }

    /**
     * @return array<int, array{label:string, description:string, href:string, icon:string}>
     */
    private function tenantNextActions(bool $hasLease): array
    {
        if (! $hasLease) {
            return [
                [
                    'label' => 'Wait for lease activation',
                    'description' => 'Your owner or manager needs to assign a lease before rent and documents appear.',
                    'href' => '/documentation',
                    'icon' => 'bi-hourglass-split',
                ],
                [
                    'label' => 'Read tenant guide',
                    'description' => 'Learn how payments, documents, and maintenance requests work in this portal.',
                    'href' => '/documentation',
                    'icon' => 'bi-journal-richtext',
                ],
            ];
        }

        return [
            [
                'label' => 'Download contract',
                'description' => 'Keep a copy of your current lease contract and tenant statement.',
                'href' => '/dashboard',
                'icon' => 'bi-file-earmark-arrow-down',
            ],
            [
                'label' => 'Submit maintenance request',
                'description' => 'Report electrical, plumbing, HVAC, or general issues from the service queue.',
                'href' => '/maintenance-requests',
                'icon' => 'bi-tools',
            ],
            [
                'label' => 'Review tenant guide',
                'description' => 'Check what you can see, download, and request from your portal.',
                'href' => '/documentation',
                'icon' => 'bi-journal-richtext',
            ],
        ];
    }

    private function scopeByPortfolio(Builder $query, User $user, string $column = 'portfolio_id'): Builder
    {
        if ($user->hasRole('superadmin')) {
            return $query;
        }

        return $query->where($column, $user->portfolio_id ?? 0);
    }
}
