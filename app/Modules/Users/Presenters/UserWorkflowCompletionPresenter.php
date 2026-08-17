<?php

namespace App\Modules\Users\Presenters;

use App\Models\User;
use App\Modules\Portfolios\Support\PortfolioModules;

final class UserWorkflowCompletionPresenter
{
    public function __construct(private readonly UserWorkflowActionPresenter $actions) {}

    /**
     * @param  list<array<string, mixed>>  $actions
     * @return list<array<string, mixed>>
     */
    public function complete(User $user, User $actor, array $actions): array
    {
        if ($user->status !== 'active') {
            return $actions;
        }

        if (
            $user->hasRole('property_manager')
            && $actor->hasAnyRole(['superadmin', 'owner'])
            && PortfolioModules::enabledForUser($actor, 'assets')
        ) {
            $actions = $this->appendOnce($actions, $this->actions->assignments($user, 'secondary'));
        }

        $actions = $this->appendOnce($actions, $this->actions->portalAccess($user, 'secondary'));
        $actions[] = [
            'label' => trans('app.users.suspend_user'),
            'href' => route('users.destroy', $user),
            'method' => 'delete',
            'variant' => 'danger',
            'confirm' => trans('app.users.archive_confirm', ['name' => $user->name]),
        ];

        return $actions;
    }

    /**
     * @param  list<array<string, mixed>>  $actions
     * @param  array<string, mixed>  $candidate
     * @return list<array<string, mixed>>
     */
    private function appendOnce(array $actions, array $candidate): array
    {
        if (! collect($actions)->contains(
            fn (array $action): bool => ($action['href'] ?? null) === $candidate['href'],
        )) {
            $actions[] = $candidate;
        }

        return $actions;
    }
}
