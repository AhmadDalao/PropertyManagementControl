<?php

namespace App\Modules\Expenses\Actions;

use App\Models\ExpenseEntry;
use App\Models\User;
use App\Modules\Expenses\Support\ExpenseAccess;
use Illuminate\Support\Facades\DB;

final class VoidExpense
{
    public function __construct(private readonly ExpenseAccess $access) {}

    public function handle(User $actor, ExpenseEntry $expense): ExpenseEntry
    {
        $this->access->ensureCanManage($actor, $expense);

        return DB::transaction(function () use ($actor, $expense): ExpenseEntry {
            $locked = ExpenseEntry::query()->lockForUpdate()->findOrFail($expense->id);
            $this->access->ensureCanManage($actor, $locked);

            if ($locked->status !== 'void') {
                $locked->update(['status' => 'void']);
            }

            return $locked->refresh();
        }, attempts: 3);
    }
}
