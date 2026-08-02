<?php

namespace App\Modules\Dashboard\Queries;

use App\Models\Asset;
use App\Models\Portfolio;
use App\Models\User;

final class PlatformCompositionQuery
{
    /** @return array<string, mixed>|null */
    public function forUser(User $actor): ?array
    {
        if (! $actor->hasRole('superadmin')) {
            return null;
        }

        $livePortfolios = Portfolio::query()->whereNull('showcase_dataset_id');
        $showcasePortfolios = Portfolio::query()->whereNotNull('showcase_dataset_id');
        $liveProperties = Asset::query()
            ->whereNull('parent_id')
            ->where('status', 'active')
            ->whereHas('portfolio', fn ($query) => $query
                ->whereNull('showcase_dataset_id')
                ->where('status', 'active'));
        $showcaseProperties = Asset::query()
            ->whereNull('parent_id')
            ->where('status', 'active')
            ->whereHas('portfolio', fn ($query) => $query
                ->whereNotNull('showcase_dataset_id')
                ->where('status', '!=', 'archived'));
        $liveUsers = User::query()->whereNull('showcase_dataset_id');

        return [
            'portfolios' => [
                'live_active' => (clone $livePortfolios)->where('status', 'active')->count(),
                'live_inactive' => (clone $livePortfolios)->where('status', 'inactive')->count(),
                'live_archived' => (clone $livePortfolios)->where('status', 'archived')->count(),
                'showcase' => (clone $showcasePortfolios)
                    ->where('status', '!=', 'archived')
                    ->count(),
            ],
            'properties' => [
                'live' => $liveProperties->count(),
                'showcase' => $showcaseProperties->count(),
                'asset_records' => Asset::query()
                    ->whereHas('portfolio', fn ($query) => $query->where('status', 'active'))
                    ->count(),
            ],
            'accounts' => [
                'live_active' => (clone $liveUsers)->where('status', 'active')->count(),
                'live_inactive' => (clone $liveUsers)->where('status', '!=', 'active')->count(),
                'showcase' => User::query()->whereNotNull('showcase_dataset_id')->count(),
                'roles' => [
                    'superadmins' => $this->activeLiveRoleCount('superadmin'),
                    'owners' => $this->activeLiveRoleCount('owner'),
                    'managers' => $this->activeLiveRoleCount('property_manager'),
                    'tenants' => $this->activeLiveRoleCount('tenant'),
                ],
            ],
        ];
    }

    private function activeLiveRoleCount(string $role): int
    {
        return User::query()
            ->whereNull('showcase_dataset_id')
            ->where('status', 'active')
            ->role($role)
            ->count();
    }
}
