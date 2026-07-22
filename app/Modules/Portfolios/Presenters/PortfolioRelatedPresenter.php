<?php

namespace App\Modules\Portfolios\Presenters;

use App\Models\Asset;
use App\Models\Lease;
use App\Models\MaintenanceRequest;
use App\Models\Portfolio;
use App\Models\User;
use App\Modules\Portfolios\Data\PortfolioDetailData;
use App\Modules\Shared\ResourcePresenter;
use App\Modules\Users\Support\UserAccess;
use Illuminate\Support\Collection;

class PortfolioRelatedPresenter
{
    public function __construct(
        private readonly ResourcePresenter $resources,
        private readonly UserAccess $users,
    ) {}

    /** @return array<int, array<string, mixed>> */
    public function present(PortfolioDetailData $data, User $actor): array
    {
        $portfolio = $data->portfolio;

        return [
            $this->assetTable(
                $data->assets,
                $portfolio,
                $portfolio->status === 'active' && ($data->settings['assets'] ?? true),
            ),
            $this->peopleTable(
                $data->people,
                $portfolio,
                $actor,
                $portfolio->status === 'active' && ($data->settings['users'] ?? true),
            ),
            $this->leaseTable($data->leases),
            $this->maintenanceTable($data->maintenance),
        ];
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
    private function peopleTable(Collection $people, Portfolio $portfolio, User $actor, bool $canCreate): array
    {
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
