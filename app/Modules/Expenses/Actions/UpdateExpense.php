<?php

namespace App\Modules\Expenses\Actions;

use App\Models\ExpenseEntry;
use App\Models\User;
use App\Modules\Expenses\Support\ExpenseAccess;
use App\Modules\Expenses\Support\ExpenseAttributes;
use App\Modules\Expenses\Support\ExpenseInputGuard;
use App\Modules\Expenses\Support\ExpenseReferenceGuard;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class UpdateExpense
{
    public function __construct(
        private readonly ExpenseAccess $access,
        private readonly ExpenseInputGuard $input,
        private readonly ExpenseReferenceGuard $references,
        private readonly ExpenseAttributes $attributes,
    ) {}

    /** @param array<string, mixed> $data */
    public function handle(User $actor, ExpenseEntry $expense, array $data): ExpenseEntry
    {
        $this->access->ensureCanManage($actor, $expense);
        $this->input->validateUpdate($data);

        return DB::transaction(function () use ($actor, $expense, $data): ExpenseEntry {
            $locked = ExpenseEntry::query()->lockForUpdate()->findOrFail($expense->id);
            $this->access->ensureCanManage($actor, $locked);

            if ($locked->status === 'void') {
                throw ValidationException::withMessages([
                    'status' => trans('app.errors.expense_void_locked'),
                ]);
            }

            $references = $this->references->withinPortfolio($data, $locked->portfolio_id);
            $locked->update($this->attributes->forUpdate($data, $references));

            return $locked->refresh()->load(['portfolio', 'asset', 'maintenanceRequest', 'createdBy']);
        }, attempts: 3);
    }
}
