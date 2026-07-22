<?php

namespace App\Modules\Expenses\Actions;

use App\Models\ExpenseEntry;
use App\Models\User;
use App\Modules\Expenses\Support\ExpenseAccess;
use App\Modules\Expenses\Support\ExpenseAttributes;
use App\Modules\Expenses\Support\ExpenseInputGuard;
use App\Modules\Expenses\Support\ExpensePortfolioGuard;
use App\Modules\Expenses\Support\ExpenseReferenceGuard;
use Illuminate\Support\Facades\DB;

final class CreateExpense
{
    public function __construct(
        private readonly ExpenseAccess $access,
        private readonly ExpenseInputGuard $input,
        private readonly ExpensePortfolioGuard $portfolios,
        private readonly ExpenseReferenceGuard $references,
        private readonly ExpenseAttributes $attributes,
    ) {}

    /** @param array<string, mixed> $data */
    public function handle(User $actor, array $data): ExpenseEntry
    {
        $this->access->ensureManager($actor);
        $this->input->validateCreate($data);

        return DB::transaction(function () use ($actor, $data): ExpenseEntry {
            $portfolio = $this->portfolios->active($actor, $data['portfolio_id'] ?? null);
            $references = $this->references->withinPortfolio($data, $portfolio->id);

            return ExpenseEntry::query()
                ->create($this->attributes->forCreate($actor, $portfolio, $data, $references))
                ->load(['portfolio', 'asset', 'maintenanceRequest', 'createdBy']);
        }, attempts: 3);
    }
}
