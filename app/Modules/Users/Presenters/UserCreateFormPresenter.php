<?php

namespace App\Modules\Users\Presenters;

use App\Models\User;
use App\Modules\Portfolios\Support\PortfolioSetupContinuation;
use App\Modules\Users\Data\UserFormData;
use App\Modules\Users\Support\UserOptions;

final class UserCreateFormPresenter
{
    public function __construct(
        private readonly UserFormDefinitionPresenter $definition,
        private readonly PortfolioSetupContinuation $continuation,
    ) {}

    /**
     * @param  array<string, mixed>  $defaults
     * @return array<string, mixed>
     */
    public function present(User $actor, array $defaults): array
    {
        $setup = $this->continuation->resolve(
            $actor,
            $defaults[PortfolioSetupContinuation::QUERY_KEY] ?? null,
        );
        $requestedRole = $defaults['role'] ?? null;
        $continuesSetup = $setup
            && is_string($requestedRole)
            && in_array($requestedRole, ['owner', 'property_manager'], true)
            && in_array($requestedRole, UserOptions::assignableRoles($actor), true);

        if ($continuesSetup) {
            $defaults[PortfolioSetupContinuation::QUERY_KEY] = $setup->id;
        } else {
            unset($defaults[PortfolioSetupContinuation::QUERY_KEY]);
        }

        $definition = $this->definition->present(new UserFormData($actor, defaults: $defaults));
        $role = (string) $definition['initialValues']['role'];
        $roleLabel = trans("app.roles.{$role}");
        $portfolioName = $setup ? $this->continuation->name($setup) : '';

        return [
            'title' => $continuesSetup
                ? trans('app.portfolios.setup_user_title', [
                    'role' => $roleLabel,
                    'portfolio' => $portfolioName,
                ])
                : trans('app.users.create_user'),
            'description' => $continuesSetup
                ? trans('app.portfolios.setup_user_description')
                : trans('app.users.create_description'),
            'backHref' => $continuesSetup
                ? route('portfolios.show', $setup)
                : route('users.index'),
            'backLabel' => $continuesSetup
                ? trans('app.portfolios.back_to_setup')
                : trans('app.users.all_users'),
            'action' => $continuesSetup
                ? route('users.store', $this->continuation->query($setup))
                : route('users.store'),
            'method' => 'post',
            'submitLabel' => $continuesSetup
                ? trans('app.portfolios.setup_user_submit', ['role' => $roleLabel])
                : trans('app.users.create_user'),
            ...$definition,
        ];
    }
}
