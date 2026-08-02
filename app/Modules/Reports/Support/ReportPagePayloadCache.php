<?php

namespace App\Modules\Reports\Support;

use App\Models\User;
use Closure;
use Illuminate\Support\Facades\Cache;

final class ReportPagePayloadCache
{
    private const int CACHE_SECONDS = 20;

    /**
     * @param  array{period:string,date_from:string,date_to:string,portfolio_id:int|null,property_id:int|null}  $filters
     * @param  Closure(): array<string, mixed>  $resolver
     * @return array<string, mixed>
     */
    public function remember(User $actor, array $filters, Closure $resolver): array
    {
        if (! app()->isProduction()) {
            return $resolver();
        }

        return Cache::remember(
            $this->key($actor, $filters),
            self::CACHE_SECONDS,
            $resolver,
        );
    }

    /** @param array{period:string,date_from:string,date_to:string,portfolio_id:int|null,property_id:int|null} $filters */
    private function key(User $actor, array $filters): string
    {
        $context = json_encode([
            'locale' => app()->getLocale(),
            'filters' => $filters,
        ], JSON_THROW_ON_ERROR);

        return 'report-page-payload-v1:'.$actor->id.':'.hash('sha256', $context);
    }
}
