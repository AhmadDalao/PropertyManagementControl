<?php

namespace App\Modules\Expenses\Support;

use App\Models\Portfolio;
use App\Models\User;
use App\Modules\Shared\PortfolioScope;
use Illuminate\Validation\ValidationException;

final class ExpensePortfolioGuard
{
    public function __construct(private readonly PortfolioScope $portfolios) {}

    public function active(User $actor, mixed $requestedPortfolioId): Portfolio
    {
        $portfolioId = filter_var(
            $requestedPortfolioId ?? $actor->portfolio_id,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]],
        );

        if (! $portfolioId) {
            throw ValidationException::withMessages([
                'portfolio_id' => trans('app.errors.expense_requires_portfolio'),
            ]);
        }

        $this->portfolios->ensureAccess($actor, (int) $portfolioId);
        $portfolio = Portfolio::query()->lockForUpdate()->findOrFail((int) $portfolioId);

        if ($portfolio->status !== 'active') {
            throw ValidationException::withMessages([
                'portfolio_id' => trans('app.errors.expense_portfolio_inactive'),
            ]);
        }

        return $portfolio;
    }
}
