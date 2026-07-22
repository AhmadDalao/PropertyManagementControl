<?php

namespace App\Modules\Leases\Support;

use App\Models\Portfolio;
use App\Models\User;
use App\Modules\Shared\PortfolioScope;
use Illuminate\Validation\ValidationException;

final class LeasePortfolioResolver
{
    public function __construct(private readonly PortfolioScope $portfolios) {}

    public function resolve(User $actor, mixed $requestedPortfolioId): int
    {
        $portfolioId = filter_var(
            $requestedPortfolioId ?? $actor->portfolio_id,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]],
        );

        if (! $portfolioId) {
            throw ValidationException::withMessages([
                'portfolio_id' => trans('validation.required', [
                    'attribute' => trans('app.leases.portfolio'),
                ]),
            ]);
        }

        $this->portfolios->ensureAccess($actor, (int) $portfolioId);

        if (! Portfolio::query()->lockForUpdate()->whereKey($portfolioId)->where('status', 'active')->exists()) {
            throw ValidationException::withMessages([
                'portfolio_id' => trans('app.errors.lease_portfolio_inactive'),
            ]);
        }

        return (int) $portfolioId;
    }
}
