<?php

namespace App\Modules\Portfolios\Support;

use App\Models\Portfolio;
use App\Models\User;
use Illuminate\Http\Request;

final class PortfolioSetupContinuation
{
    public const QUERY_KEY = 'setup_portfolio_id';

    public function __construct(private readonly PortfolioAccess $access) {}

    public function fromRequest(Request $request, User $actor): ?Portfolio
    {
        return $this->resolve($actor, $request->query(self::QUERY_KEY));
    }

    public function resolve(User $actor, mixed $value): ?Portfolio
    {
        if ($value === null || $value === '') {
            return null;
        }

        abort_unless(
            is_scalar($value)
                && filter_var((string) $value, FILTER_VALIDATE_INT) !== false,
            404,
        );

        $portfolio = Portfolio::query()
            ->whereKey((int) $value)
            ->whereNull('showcase_dataset_id')
            ->where('status', 'active')
            ->firstOrFail();

        $this->access->ensureCanUpdate($actor, $portfolio);

        return $portfolio;
    }

    /** @param array<string, mixed> $parameters
     * @return array<string, mixed>
     */
    public function query(Portfolio $portfolio, array $parameters = []): array
    {
        return [...$parameters, self::QUERY_KEY => $portfolio->id];
    }

    public function matches(?Portfolio $portfolio, ?int $recordPortfolioId): bool
    {
        return $portfolio instanceof Portfolio
            && $portfolio->id === $recordPortfolioId;
    }

    public function ensureMatches(?Portfolio $portfolio, mixed $recordPortfolioId): void
    {
        if ($portfolio instanceof Portfolio) {
            abort_unless(
                is_scalar($recordPortfolioId)
                    && filter_var((string) $recordPortfolioId, FILTER_VALIDATE_INT) !== false
                    && $portfolio->id === (int) $recordPortfolioId,
                404,
            );
        }
    }

    public function name(Portfolio $portfolio): string
    {
        return app()->isLocale('ar')
            ? ($portfolio->name_ar ?: $portfolio->name_en)
            : ($portfolio->name_en ?: $portfolio->name_ar);
    }
}
