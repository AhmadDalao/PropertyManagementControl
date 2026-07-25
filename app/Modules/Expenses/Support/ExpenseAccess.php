<?php

namespace App\Modules\Expenses\Support;

use App\Models\ExpenseEntry;
use App\Models\User;
use App\Modules\Shared\Authorization\AssignedPropertyScope;

final class ExpenseAccess
{
    public function __construct(private readonly AssignedPropertyScope $assignments) {}

    public function canManageSection(User $actor): bool
    {
        return $actor->hasAnyRole(['superadmin', 'owner', 'property_manager']);
    }

    public function canManage(User $actor, ExpenseEntry $expense): bool
    {
        return $this->canManageSection($actor)
            && ($actor->hasRole('superadmin') || $actor->portfolio_id === $expense->portfolio_id)
            && $this->assignments->allowsExpense($actor, $expense);
    }

    public function ensureManager(User $actor): void
    {
        abort_unless(
            $this->canManageSection($actor),
            403,
            trans('app.errors.section_access_denied'),
        );
    }

    public function ensureCanManage(User $actor, ExpenseEntry $expense): void
    {
        abort_unless(
            $this->canManage($actor, $expense),
            403,
            trans('app.errors.portfolio_access_denied'),
        );
    }
}
