<?php

namespace App\Modules\Expenses\Support;

use App\Models\ExpenseEntry;
use App\Models\User;
use App\Modules\Shared\PortfolioScope;

class ExpenseAccess
{
    public function __construct(private readonly PortfolioScope $portfolios) {}

    public function ensureManager(User $actor): void
    {
        abort_unless(
            $actor->hasAnyRole(['superadmin', 'owner', 'property_manager']),
            403,
            trans('app.errors.section_access_denied'),
        );
    }

    public function ensureCanManage(User $actor, ExpenseEntry $expense): void
    {
        $this->ensureManager($actor);
        $this->portfolios->ensureAccess($actor, $expense->portfolio_id);
    }
}
