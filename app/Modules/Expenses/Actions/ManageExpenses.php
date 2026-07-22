<?php

namespace App\Modules\Expenses\Actions;

use App\Models\ExpenseEntry;
use App\Models\User;

final class ManageExpenses
{
    public function __construct(
        private readonly CreateExpense $create,
        private readonly UpdateExpense $update,
        private readonly VoidExpense $void,
    ) {}

    /** @param array<string, mixed> $data */
    public function create(User $actor, array $data): ExpenseEntry
    {
        return $this->create->handle($actor, $data);
    }

    /** @param array<string, mixed> $data */
    public function update(User $actor, ExpenseEntry $expense, array $data): ExpenseEntry
    {
        return $this->update->handle($actor, $expense, $data);
    }

    public function void(User $actor, ExpenseEntry $expense): ExpenseEntry
    {
        return $this->void->handle($actor, $expense);
    }
}
