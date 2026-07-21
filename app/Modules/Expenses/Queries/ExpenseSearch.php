<?php

namespace App\Modules\Expenses\Queries;

use App\Models\ExpenseEntry;
use App\Models\User;
use App\Modules\Expenses\Support\ExpenseAccess;
use App\Modules\Search\Presenters\SearchResultPresenter;
use App\Modules\Search\Support\ModuleSearchSource;
use App\Modules\Shared\PortfolioScope;
use App\Modules\Shared\TableQuery;
use Illuminate\Database\Eloquent\Builder;

class ExpenseSearch extends ModuleSearchSource
{
    public function __construct(
        private readonly ExpenseAccess $access,
        private readonly PortfolioScope $portfolios,
        private readonly TableQuery $tables,
        private readonly SearchResultPresenter $results,
    ) {}

    public function results(User $actor, string $query): array
    {
        if (! $this->isManager($actor) || ! $this->moduleEnabled($actor, 'expenses')) {
            return [];
        }

        $this->access->ensureManager($actor);
        $expenses = $this->portfolios
            ->apply(ExpenseEntry::query(), $actor)
            ->with('asset');
        $this->tables->search($expenses, $query, [
            'title',
            'description',
            'category',
            'vendor_name',
            fn (Builder $expenses, string $term, string $like) => $expenses->orWhereHas(
                'asset',
                fn (Builder $assets) => $assets
                    ->where('title_en', 'like', $like)
                    ->orWhere('title_ar', 'like', $like)
                    ->orWhere('code', 'like', $like),
            ),
        ]);

        return $expenses
            ->limit(4)
            ->get()
            ->map(fn (ExpenseEntry $expense): array => $this->results->result(
                trans('app.nav.expenses'),
                $expense->title,
                $this->results->localized($expense->asset?->title_en, $expense->asset?->title_ar)
                    ?? $expense->vendor_name,
                $this->results->status($expense->status),
                route('expenses.show', $expense),
            ))
            ->all();
    }
}
