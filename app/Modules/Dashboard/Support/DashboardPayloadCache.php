<?php

namespace App\Modules\Dashboard\Support;

use App\Models\User;
use Closure;
use Illuminate\Support\Facades\Cache;

final class DashboardPayloadCache
{
    private const int CACHE_SECONDS = 20;

    /**
     * @param  Closure(): array<string, mixed>  $resolver
     * @return array<string, mixed>
     */
    public function remember(User $user, ?int $propertyId, Closure $resolver): array
    {
        if (! app()->isProduction()) {
            return $resolver();
        }

        return Cache::remember(
            $this->key($user, $propertyId),
            self::CACHE_SECONDS,
            $resolver,
        );
    }

    private function key(User $user, ?int $propertyId): string
    {
        return implode(':', [
            'dashboard-payload-v1',
            $user->id,
            app()->getLocale(),
            $propertyId ?? 'all',
        ]);
    }
}
