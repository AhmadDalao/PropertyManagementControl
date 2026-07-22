<?php

namespace App\Modules\Expenses\Presenters;

use App\Models\ExpenseEntry;
use App\Models\User;
use App\Modules\Expenses\Queries\ExpenseFormOptionsQuery;
use App\Modules\Expenses\Support\ExpenseAccess;

final class ExpenseFormPresenter
{
    public function __construct(
        private readonly ExpenseAccess $access,
        private readonly ExpenseFormOptionsQuery $options,
        private readonly ExpenseCreateFormPresenter $create,
        private readonly ExpenseEditFormPresenter $edit,
    ) {}

    /**
     * @param  array<string, mixed>  $defaults
     * @return array<string, mixed>
     */
    public function present(User $actor, ?ExpenseEntry $expense = null, array $defaults = []): array
    {
        if ($expense) {
            $this->access->ensureCanManage($actor, $expense);
            abort_if($expense->status === 'void', 409, trans('app.errors.expense_void_locked'));

            return $this->edit->present($this->options->get($actor, $expense));
        }

        $this->access->ensureManager($actor);

        return $this->create->present($this->options->get($actor, defaults: $defaults));
    }
}
