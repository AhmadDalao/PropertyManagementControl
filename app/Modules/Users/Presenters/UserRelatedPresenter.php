<?php

namespace App\Modules\Users\Presenters;

use App\Models\AssetStakeholder;
use App\Models\MaintenanceRequest;
use App\Modules\Shared\ResourcePresenter;
use Illuminate\Support\Collection;

final class UserRelatedPresenter
{
    public function __construct(private readonly ResourcePresenter $resources) {}

    /**
     * @param  Collection<int, AssetStakeholder>  $stakeholders
     * @param  Collection<int, MaintenanceRequest>  $maintenance
     * @return array<int, array<string, mixed>>
     */
    public function present(Collection $stakeholders, Collection $maintenance): array
    {
        return [
            $this->stakeholders($stakeholders),
            $this->maintenance($maintenance),
        ];
    }

    /**
     * @param  Collection<int, AssetStakeholder>  $stakeholders
     * @return array<string, mixed>
     */
    private function stakeholders(Collection $stakeholders): array
    {
        return [
            'title' => trans('app.users.assets_title'),
            'description' => trans('app.users.assets_help'),
            'columns' => [
                trans('app.users.asset'),
                trans('app.users.relationship'),
                trans('app.users.primary'),
                trans('app.users.status'),
            ],
            'rows' => $stakeholders->map(fn (AssetStakeholder $stakeholder): array => [
                trans('app.users.asset') => $stakeholder->asset
                    ? [
                        'label' => $this->resources->localized($stakeholder->asset->title_en, $stakeholder->asset->title_ar) ?? '-',
                        'href' => route('assets.show', $stakeholder->asset),
                    ]
                    : '-',
                trans('app.users.relationship') => trans("app.users.relationship_{$stakeholder->relationship_type}"),
                trans('app.users.primary') => trans('app.users.'.($stakeholder->is_primary ? 'yes' : 'no')),
                trans('app.users.status') => trans('app.status.'.($stakeholder->ends_on ? 'inactive' : 'active')),
            ])->all(),
            'emptyText' => trans('app.users.no_assets'),
        ];
    }

    /**
     * @param  Collection<int, MaintenanceRequest>  $requests
     * @return array<string, mixed>
     */
    private function maintenance(Collection $requests): array
    {
        return [
            'title' => trans('app.users.maintenance_title'),
            'description' => trans('app.users.maintenance_help'),
            'columns' => [
                trans('app.users.request'),
                trans('app.users.asset'),
                trans('app.users.status'),
                trans('app.users.priority'),
            ],
            'rows' => $requests->map(fn (MaintenanceRequest $request): array => [
                trans('app.users.request') => [
                    'label' => '#'.$request->id.' '.$request->title,
                    'href' => route('maintenance-requests.show', $request),
                ],
                trans('app.users.asset') => $request->asset
                    ? [
                        'label' => $this->resources->localized($request->asset->title_en, $request->asset->title_ar) ?? '-',
                        'href' => route('assets.show', $request->asset),
                    ]
                    : '-',
                trans('app.users.status') => trans("app.status.{$request->status}"),
                trans('app.users.priority') => trans("app.status.{$request->priority}"),
            ])->all(),
            'emptyText' => trans('app.users.no_maintenance'),
        ];
    }
}
