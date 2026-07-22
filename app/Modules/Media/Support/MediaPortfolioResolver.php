<?php

namespace App\Modules\Media\Support;

use App\Models\Portfolio;
use App\Models\User;
use App\Modules\Shared\PortfolioScope;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;

final class MediaPortfolioResolver
{
    public function __construct(private readonly PortfolioScope $portfolios) {}

    public function forCreate(User $actor, mixed $requestedPortfolioId, bool $lock = false): ?int
    {
        return $this->resolve($actor, $requestedPortfolioId, true, $lock);
    }

    public function forUpdate(
        User $actor,
        ?int $currentPortfolioId,
        mixed $requestedPortfolioId,
        bool $lock = false,
    ): ?int {
        $portfolioId = $this->normalize($requestedPortfolioId);
        $requireActive = $portfolioId !== $currentPortfolioId;

        return $this->resolve($actor, $portfolioId, $requireActive, $lock);
    }

    private function resolve(User $actor, mixed $requestedPortfolioId, bool $requireActive, bool $lock): ?int
    {
        $portfolioId = $this->normalize($requestedPortfolioId);
        $this->portfolios->ensureAccess($actor, $portfolioId);

        if ($portfolioId === null) {
            return null;
        }

        $query = Portfolio::query();

        if ($lock) {
            $query->lockForUpdate();
        }

        $portfolio = $query->find($portfolioId);

        if (! $portfolio) {
            throw (new ModelNotFoundException)->setModel(Portfolio::class, [$portfolioId]);
        }

        if ($requireActive && $portfolio->status !== 'active') {
            throw ValidationException::withMessages([
                'portfolio_id' => trans('app.errors.media_portfolio_inactive'),
            ]);
        }

        return $portfolio->id;
    }

    private function normalize(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        $portfolioId = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        if ($portfolioId === false) {
            throw ValidationException::withMessages([
                'portfolio_id' => trans('validation.integer', [
                    'attribute' => trans('app.media.validation_attributes.portfolio_id'),
                ]),
            ]);
        }

        return (int) $portfolioId;
    }
}
