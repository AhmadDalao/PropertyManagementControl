<?php

namespace App\Modules\Portfolios\Presenters;

use App\Models\Asset;
use App\Models\Lease;
use App\Models\MaintenanceRequest;
use App\Models\Portfolio;
use App\Models\User;
use App\Modules\Portfolios\Support\PortfolioAccess;
use App\Modules\Shared\ResourcePresenter;
use App\Modules\Users\Support\UserAccess;
use App\Support\PortfolioModules;
use Illuminate\Support\Collection;

class PortfolioDetailPresenter
{
    public function __construct(
        private readonly PortfolioAccess $access,
        private readonly UserAccess $users,
        private readonly ResourcePresenter $resources,
    ) {}

    /** @return array<string, mixed> */
    public function present(Portfolio $portfolio, User $actor): array
    {
        $this->access->ensureCanView($actor, $portfolio);
        $portfolio->loadMissing(['owner:id,name,status', 'showcaseDataset:id,status']);
        $settings = PortfolioModules::normalize($portfolio->module_settings);
        $assets = $this->moduleVisible($actor, $settings, 'assets')
            ? $portfolio->assets()
                ->latest()
                ->limit(8)
                ->get(['id', 'portfolio_id', 'title_en', 'title_ar', 'code', 'asset_type', 'occupancy_status', 'status'])
            : collect();
        $peopleQuery = $this->users->directoryScope(User::query(), $actor)
            ->where('portfolio_id', $portfolio->id);
        $people = $this->moduleVisible($actor, $settings, 'users')
            ? (clone $peopleQuery)
                ->with('roles:id,name')
                ->latest()
                ->limit(8)
                ->get(['id', 'portfolio_id', 'name', 'email', 'status', 'created_at'])
            : collect();
        $leases = $this->moduleVisible($actor, $settings, 'leases')
            ? $portfolio->leases()
                ->with(['tenantProfile.user:id,name', 'leaseable'])
                ->latest()
                ->limit(8)
                ->get()
            : collect();
        $maintenance = $this->moduleVisible($actor, $settings, 'maintenance')
            ? $portfolio->maintenanceRequests()
                ->with('asset:id,portfolio_id,title_en,title_ar,code')
                ->latest('requested_at')
                ->limit(8)
                ->get()
            : collect();
        $documents = $this->moduleVisible($actor, $settings, 'documents')
            ? $portfolio->documents()
                ->latest()
                ->limit(8)
                ->get()
            : collect();
        $assetSummary = $portfolio->assets()
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("SUM(CASE WHEN occupancy_status = 'vacant' AND rentable = 1 THEN 1 ELSE 0 END) as vacant_count")
            ->selectRaw("SUM(CASE WHEN status != 'archived' THEN valuation_amount ELSE 0 END) as valuation_total")
            ->first();
        $leaseSummary = $portfolio->leases()
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_count")
            ->first();
        $openMaintenance = $portfolio->maintenanceRequests()
            ->whereIn('status', ['open', 'in_progress'])
            ->count();
        $postedRevenue = (float) $portfolio->payments()->where('status', 'posted')->sum('amount');
        $postedExpenses = (float) $portfolio->expenseEntries()->where('status', 'posted')->sum('amount');
        $valuation = (float) ($assetSummary?->getAttribute('valuation_total') ?? 0);
        $visibleUserCount = (clone $peopleQuery)->count();
        $currency = $portfolio->default_currency;
        $enabledModules = collect(PortfolioModules::definitions())
            ->filter(fn (array $definition): bool => $settings[(string) $definition['key']] ?? true)
            ->pluck('label')
            ->join(', ');
        $actions = $this->actions($portfolio, $actor, $settings);
        $name = $this->resources->localized($portfolio->name_en, $portfolio->name_ar)
            ?? $portfolio->code;
        $location = implode(' · ', array_filter([$portfolio->city, $portfolio->country]));

        return [
            'header' => [
                'eyebrow' => trans('app.portfolios.detail_eyebrow'),
                'title' => $name,
                'description' => trans('app.portfolios.detail_description', [
                    'code' => $portfolio->code,
                    'location' => $location ?: trans('app.portfolios.location_not_set'),
                ]),
                'backHref' => route('portfolios.index'),
                'backLabel' => trans('app.portfolios.all_portfolios'),
                'actions' => $actions,
            ],
            'decisionCards' => [
                [
                    'title' => trans('app.portfolios.portfolio_status'),
                    'value' => trans("app.status.{$portfolio->status}"),
                    'detail' => trans('app.portfolios.module_count', ['count' => count(array_filter($settings))]),
                    'tone' => $portfolio->status === 'active' ? 'teal' : 'danger',
                    'icon' => 'bi-shield-check',
                ],
                [
                    'title' => trans('app.portfolios.recorded_valuation'),
                    'value' => $this->money($valuation, $currency),
                    'detail' => trans('app.portfolios.vacant_units', [
                        'count' => (int) ($assetSummary?->getAttribute('vacant_count') ?? 0),
                    ]),
                    'tone' => 'primary',
                    'icon' => 'bi-buildings',
                ],
                [
                    'title' => trans('app.portfolios.posted_revenue'),
                    'value' => $this->money($postedRevenue, $currency),
                    'detail' => trans('app.portfolios.active_lease_count', [
                        'count' => (int) ($leaseSummary?->getAttribute('active_count') ?? 0),
                    ]),
                    'tone' => 'teal',
                    'icon' => 'bi-cash-stack',
                ],
                [
                    'title' => trans('app.portfolios.net_position'),
                    'value' => $this->money($postedRevenue - $postedExpenses, $currency),
                    'detail' => trans('app.portfolios.expense_summary', [
                        'amount' => $this->money($postedExpenses, $currency),
                    ]),
                    'tone' => $postedRevenue - $postedExpenses >= 0 ? 'blue' : 'danger',
                    'icon' => 'bi-graph-up-arrow',
                ],
            ],
            'stats' => $this->resources->detailItems([
                ['label' => trans('app.portfolios.assets'), 'value' => (int) ($assetSummary?->getAttribute('total') ?? 0), 'tone' => 'primary'],
                ['label' => trans('app.portfolios.users'), 'value' => $visibleUserCount],
                ['label' => trans('app.portfolios.active_leases_label'), 'value' => (int) ($leaseSummary?->getAttribute('active_count') ?? 0), 'tone' => 'teal'],
                ['label' => trans('app.portfolios.open_maintenance'), 'value' => $openMaintenance, 'tone' => $openMaintenance > 0 ? 'danger' : 'muted'],
            ]),
            'sections' => [
                [
                    'title' => trans('app.portfolios.business_profile'),
                    'description' => trans('app.portfolios.business_profile_help'),
                    'items' => $this->resources->detailItems([
                        ['label' => trans('app.portfolios.name_ar'), 'value' => $portfolio->name_ar],
                        ['label' => trans('app.portfolios.code'), 'value' => $portfolio->code],
                        ['label' => trans('app.portfolios.status'), 'value' => trans("app.status.{$portfolio->status}")],
                        ['label' => trans('app.portfolios.default_currency'), 'value' => $currency],
                        ['label' => trans('app.portfolios.contact_email'), 'value' => $portfolio->contact_email],
                        ['label' => trans('app.portfolios.contact_phone'), 'value' => $portfolio->contact_phone],
                        ['label' => trans('app.portfolios.location'), 'value' => $location],
                        ['label' => trans('app.portfolios.address'), 'value' => $this->resources->localized($portfolio->address, $portfolio->address_ar)],
                    ]),
                ],
                [
                    'title' => trans('app.portfolios.ownership_modules'),
                    'description' => trans('app.portfolios.ownership_modules_help'),
                    'items' => $this->resources->detailItems([
                        [
                            'label' => trans('app.portfolios.owner'),
                            'value' => $portfolio->owner?->name ?: trans('app.portfolios.no_owner'),
                            'href' => $this->users->recordHref($actor, $portfolio->owner),
                        ],
                        ['label' => trans('app.portfolios.enabled_modules'), 'value' => $enabledModules],
                        ['label' => trans('app.portfolios.showcase_state'), 'value' => $portfolio->is_showcase ? trans('app.portfolios.showcase') : trans('app.portfolios.live_data')],
                        ['label' => trans('app.portfolios.created_at'), 'value' => $portfolio->created_at?->toDateTimeString()],
                        ['label' => trans('app.portfolios.updated_at'), 'value' => $portfolio->updated_at?->toDateTimeString()],
                    ]),
                ],
            ],
            'related' => [
                $this->assetTable(
                    $assets,
                    $portfolio,
                    $portfolio->status === 'active' && ($settings['assets'] ?? true),
                ),
                $this->peopleTable(
                    $people,
                    $portfolio,
                    $actor,
                    $portfolio->status === 'active' && ($settings['users'] ?? true),
                ),
                $this->leaseTable($leases),
                $this->maintenanceTable($maintenance),
            ],
            'documents' => $this->resources->documentStrip($documents),
            'timeline' => $this->resources->activityTimeline($portfolio),
        ];
    }

    /**
     * @param  array<string, bool>  $settings
     * @return array<int, array<string, mixed>>
     */
    private function actions(Portfolio $portfolio, User $actor, array $settings): array
    {
        $actions = [];

        if ($this->access->canUpdate($actor, $portfolio)) {
            $actions[] = [
                'label' => trans('app.portfolios.edit_portfolio'),
                'href' => route('portfolios.edit', $portfolio),
                'variant' => 'primary',
            ];
        }

        if ($portfolio->status === 'active' && ($settings['assets'] ?? true)) {
            $actions[] = [
                'label' => trans('app.portfolios.create_asset'),
                'href' => route('assets.create', ['portfolio_id' => $portfolio->id]),
                'variant' => 'secondary',
            ];
        }

        if ($portfolio->status === 'active' && ($settings['users'] ?? true)) {
            $actions[] = [
                'label' => trans('app.portfolios.create_user'),
                'href' => route('users.create', ['portfolio_id' => $portfolio->id]),
                'variant' => 'secondary',
            ];
        }

        if ($this->access->canArchive($actor) && $portfolio->status !== 'archived') {
            $actions[] = [
                'label' => trans('app.portfolios.archive_portfolio'),
                'href' => route('portfolios.destroy', $portfolio),
                'method' => 'delete',
                'variant' => 'danger',
                'confirm' => trans('app.portfolios.archive_confirm', ['name' => $portfolio->name_en]),
            ];
        }

        return $actions;
    }

    /**
     * @param  Collection<int, Asset>  $assets
     * @return array<string, mixed>
     */
    private function assetTable(Collection $assets, Portfolio $portfolio, bool $canCreate): array
    {
        return [
            'title' => trans('app.portfolios.recent_assets'),
            'description' => trans('app.portfolios.recent_assets_help'),
            'columns' => [
                trans('app.portfolios.asset'),
                trans('app.portfolios.code'),
                trans('app.portfolios.type'),
                trans('app.portfolios.occupancy'),
            ],
            'rows' => $assets->map(fn (Asset $asset): array => [
                trans('app.portfolios.asset') => [
                    'label' => $this->resources->localized($asset->title_en, $asset->title_ar) ?? '-',
                    'href' => route('assets.show', $asset),
                ],
                trans('app.portfolios.code') => $asset->code,
                trans('app.portfolios.type') => trans("app.status.{$asset->asset_type}"),
                trans('app.portfolios.occupancy') => trans("app.status.{$asset->occupancy_status}"),
            ])->all(),
            'emptyText' => trans('app.portfolios.no_assets'),
            'actionHref' => $canCreate ? route('assets.create', ['portfolio_id' => $portfolio->id]) : null,
            'actionLabel' => $canCreate ? trans('app.portfolios.add_asset') : null,
        ];
    }

    /**
     * @param  Collection<int, User>  $people
     * @return array<string, mixed>
     */
    private function peopleTable(
        Collection $people,
        Portfolio $portfolio,
        User $actor,
        bool $canCreate,
    ): array {
        return [
            'title' => trans('app.portfolios.people'),
            'description' => trans('app.portfolios.people_help'),
            'columns' => [
                trans('app.portfolios.user'),
                trans('app.portfolios.email'),
                trans('app.portfolios.role'),
                trans('app.portfolios.status'),
            ],
            'rows' => $people->map(fn (User $user): array => [
                trans('app.portfolios.user') => [
                    'label' => $user->name,
                    'href' => $this->users->recordHref($actor, $user),
                ],
                trans('app.portfolios.email') => $user->email,
                trans('app.portfolios.role') => $user->roles
                    ->pluck('name')
                    ->map(fn (string $role): string => trans("app.roles.{$role}"))
                    ->join(', '),
                trans('app.portfolios.status') => trans("app.status.{$user->status}"),
            ])->all(),
            'emptyText' => trans('app.portfolios.no_people'),
            'actionHref' => $canCreate ? route('users.create', ['portfolio_id' => $portfolio->id]) : null,
            'actionLabel' => $canCreate ? trans('app.portfolios.add_user') : null,
        ];
    }

    /**
     * @param  Collection<int, Lease>  $leases
     * @return array<string, mixed>
     */
    private function leaseTable(Collection $leases): array
    {
        return [
            'title' => trans('app.portfolios.recent_leases'),
            'description' => trans('app.portfolios.recent_leases_help'),
            'columns' => [
                trans('app.portfolios.lease'),
                trans('app.portfolios.tenant'),
                trans('app.portfolios.asset'),
                trans('app.portfolios.status'),
            ],
            'rows' => $leases->map(fn (Lease $lease): array => [
                trans('app.portfolios.lease') => [
                    'label' => $lease->code,
                    'href' => route('leases.show', $lease),
                ],
                trans('app.portfolios.tenant') => $lease->tenantProfile?->user?->name,
                trans('app.portfolios.asset') => $this->leaseableName($lease),
                trans('app.portfolios.status') => trans("app.status.{$lease->status}"),
            ])->all(),
            'emptyText' => trans('app.portfolios.no_leases'),
        ];
    }

    /**
     * @param  Collection<int, MaintenanceRequest>  $requests
     * @return array<string, mixed>
     */
    private function maintenanceTable(Collection $requests): array
    {
        return [
            'title' => trans('app.portfolios.recent_maintenance'),
            'description' => trans('app.portfolios.recent_maintenance_help'),
            'columns' => [
                trans('app.portfolios.request'),
                trans('app.portfolios.asset'),
                trans('app.portfolios.priority'),
                trans('app.portfolios.status'),
            ],
            'rows' => $requests->map(fn (MaintenanceRequest $request): array => [
                trans('app.portfolios.request') => [
                    'label' => '#'.$request->id.' '.$request->title,
                    'href' => route('maintenance-requests.show', $request),
                ],
                trans('app.portfolios.asset') => $this->resources->localized(
                    $request->asset?->title_en,
                    $request->asset?->title_ar,
                ),
                trans('app.portfolios.priority') => trans("app.status.{$request->priority}"),
                trans('app.portfolios.status') => trans("app.status.{$request->status}"),
            ])->all(),
            'emptyText' => trans('app.portfolios.no_maintenance'),
        ];
    }

    private function money(float $amount, string $currency): string
    {
        return number_format($amount, 2).' '.$currency;
    }

    /** @param array<string, bool> $settings */
    private function moduleVisible(User $actor, array $settings, string $module): bool
    {
        return $actor->hasRole('superadmin') || ($settings[$module] ?? true);
    }

    private function leaseableName(Lease $lease): ?string
    {
        $english = data_get($lease->leaseable, 'title_en');
        $arabic = data_get($lease->leaseable, 'title_ar');

        return $this->resources->localized(
            is_string($english) ? $english : null,
            is_string($arabic) ? $arabic : null,
        );
    }
}
