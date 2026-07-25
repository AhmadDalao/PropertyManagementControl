<?php

namespace App\Modules\Dashboard\Presenters;

use App\Models\CmsPage;
use App\Models\Portfolio;
use App\Models\TenantProfile;
use App\Models\User;
use App\Modules\Shared\Authorization\AssignedPropertyScope;
use App\Modules\Shared\PortfolioScope;

class SetupChecklistPresenter
{
    public function __construct(
        private readonly PortfolioScope $portfolios,
        private readonly AssignedPropertyScope $assignments,
    ) {}

    /**
     * @param  array<string, int|float>  $stats
     * @return array<int, array{label:string, done:bool, href:string}>
     */
    public function present(User $user, array $stats): array
    {
        if ($this->assignments->restricts($user) && ! $this->assignments->hasAssignments($user)) {
            return [[
                'label' => trans('app.dashboard.property_assignment_action'),
                'done' => false,
                'href' => '/portfolios',
            ]];
        }

        $items = [
            [
                'label' => 'Create portfolio',
                'done' => $user->hasRole('superadmin')
                    ? Portfolio::query()->exists()
                    : $user->portfolio_id !== null,
                'href' => '/portfolios/create',
            ],
            ['label' => 'Create users', 'done' => $stats['totalUsers'] > 1, 'href' => '/users/create'],
            ['label' => 'Create assets', 'done' => $stats['totalAssets'] > 0, 'href' => '/assets/create'],
            [
                'label' => 'Create profiles',
                'done' => $this->assignments
                    ->tenants($this->portfolios->apply(TenantProfile::query(), $user), $user)
                    ->exists(),
                'href' => '/tenants/create',
            ],
            ['label' => 'Create leases', 'done' => $stats['activeLeases'] > 0, 'href' => '/leases/create'],
        ];

        if ($user->hasRole('superadmin')) {
            $items[] = [
                'label' => 'Publish website',
                'done' => CmsPage::query()->where('status', 'published')->exists(),
                'href' => '/cms',
            ];
        }

        return $items;
    }
}
