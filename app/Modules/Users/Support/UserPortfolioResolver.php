<?php

namespace App\Modules\Users\Support;

use App\Models\Portfolio;
use App\Models\User;
use Illuminate\Validation\ValidationException;

final class UserPortfolioResolver
{
    public function __construct(private readonly UserAccess $access) {}

    public function resolve(User $actor, mixed $requestedPortfolioId, string $role): ?Portfolio
    {
        if ($role === 'superadmin') {
            return null;
        }

        $portfolioId = filter_var(
            $requestedPortfolioId ?? $actor->portfolio_id,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]],
        );

        if (! $portfolioId) {
            throw ValidationException::withMessages([
                'portfolio_id' => trans('app.errors.role_requires_portfolio'),
            ]);
        }

        $this->access->ensurePortfolioAccess($actor, (int) $portfolioId);
        $portfolio = Portfolio::query()->lockForUpdate()->whereKey((int) $portfolioId)->firstOrFail();

        if ($portfolio->status !== 'active') {
            throw ValidationException::withMessages([
                'portfolio_id' => trans('app.errors.user_portfolio_inactive'),
            ]);
        }

        return $portfolio;
    }
}
