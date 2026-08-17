<?php

namespace App\Modules\Users\Presenters;

use App\Models\User;
use App\Modules\Portfolios\Support\PortfolioModules;
use App\Modules\Users\Data\UserDetailData;

final class UserWorkflowPresenter
{
    public function __construct(private readonly UserWorkflowActionPresenter $actions, private readonly UserWorkflowCompletionPresenter $completion) {}

    /** @return array<string, mixed> */
    public function present(UserDetailData $data, User $actor): array
    {
        $user = $data->user;
        $workflow = $this->priority($user, $actor);

        return [
            ...$workflow,
            'status' => trans("app.status.{$user->status}"),
            'actions' => $this->completion->complete($user, $actor, $workflow['actions']),
        ];
    }

    /** @return array<string, mixed> */
    private function priority(User $user, User $actor): array
    {
        if ($user->status !== 'active') {
            return $this->workflow(
                'access_blocked_title',
                'access_blocked_help',
                'danger',
                'bi-shield-exclamation',
                [$this->actions->edit($user)],
            );
        }
        if ($user->force_password_reset) {
            $actions = [$this->actions->portalAccess($user, 'primary')];
            if (
                $user->hasRole('property_manager')
                && $actor->hasAnyRole(['superadmin', 'owner'])
                && PortfolioModules::enabledForUser($actor, 'assets')
            ) {
                $actions[] = $this->actions->assignments($user, 'secondary');
            }

            return $this->workflow(
                'handoff_required_title',
                'handoff_required_help',
                'danger',
                'bi-person-lock',
                $actions,
            );
        }

        $assignments = (int) ($user->getAttribute('current_asset_assignments_count') ?? 0);
        if (
            $user->hasRole('property_manager')
            && $actor->hasAnyRole(['superadmin', 'owner'])
            && PortfolioModules::enabledForUser($actor, 'assets')
            && $assignments === 0
        ) {
            return $this->workflow(
                'assignment_required_title',
                'assignment_required_help',
                'danger',
                'bi-buildings',
                [$this->actions->assignments($user)],
            );
        }

        if (
            PortfolioModules::enabledForUser($actor, 'tenants')
            && $user->tenantProfile
        ) {
            return $this->workflow(
                'tenant_profile_next_title',
                'tenant_profile_next_help',
                'teal',
                'bi-person-badge',
                [$this->actions->tenantProfile($user)],
            );
        }

        if ($user->hasRole('owner') && $user->portfolio) {
            return $this->workflow(
                'portfolio_next_title',
                'portfolio_next_help',
                'teal',
                'bi-buildings',
                [$this->actions->portfolio($user)],
            );
        }

        $openWork = (int) ($user->getAttribute('open_assignments_count') ?? 0);
        if ($openWork > 0 && PortfolioModules::enabledForUser($actor, 'maintenance')) {
            return $this->workflow(
                'workload_next_title',
                'workload_next_help',
                'danger',
                'bi-tools',
                [$this->actions->workload($user)],
            );
        }

        if ($user->hasRole('property_manager') && $actor->hasAnyRole(['superadmin', 'owner'])) {
            return $this->workflow(
                'access_ready_title',
                'access_ready_help',
                'teal',
                'bi-shield-check',
                [$this->actions->assignments($user)],
            );
        }

        return $this->workflow(
            'access_ready_title',
            'access_ready_help',
            'teal',
            'bi-shield-check',
            [$this->actions->portalAccess($user, 'primary')],
        );
    }

    /**
     * @param  list<array<string, mixed>>  $actions
     * @return array{eyebrow: string, title: string, description: string, tone: string, icon: string, actions: list<array<string, mixed>>}
     */
    private function workflow(string $title, string $description, string $tone, string $icon, array $actions): array
    {
        return [
            'eyebrow' => trans('app.users.next_action'),
            'title' => trans("app.users.{$title}"),
            'description' => trans("app.users.{$description}"),
            'tone' => $tone,
            'icon' => $icon,
            'actions' => $actions,
        ];
    }
}
