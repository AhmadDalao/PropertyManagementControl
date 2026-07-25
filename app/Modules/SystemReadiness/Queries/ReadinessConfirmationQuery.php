<?php

namespace App\Modules\SystemReadiness\Queries;

use App\Models\OperationalReadinessCheck;
use App\Modules\SystemReadiness\Support\ReadinessCheckCatalog;
use Illuminate\Support\Collection;

final class ReadinessConfirmationQuery
{
    public function __construct(private readonly ReadinessCheckCatalog $catalog) {}

    /** @return list<array<string, mixed>> */
    public function system(): array
    {
        return $this->forScope('system', null, ReadinessCheckCatalog::SYSTEM_KEYS);
    }

    /** @return list<array<string, mixed>> */
    public function portfolio(int $portfolioId): array
    {
        return $this->forScope(
            $this->catalog->scopeKey('portfolio', $portfolioId),
            $portfolioId,
            ReadinessCheckCatalog::PORTFOLIO_KEYS,
        );
    }

    /**
     * @param  list<string>  $keys
     * @return list<array<string, mixed>>
     */
    private function forScope(string $scopeKey, ?int $portfolioId, array $keys): array
    {
        /** @var Collection<string, OperationalReadinessCheck> $records */
        $records = OperationalReadinessCheck::query()
            ->with('confirmedBy:id,name')
            ->where('scope_key', $scopeKey)
            ->whereIn('key', $keys)
            ->get()
            ->keyBy('key');

        return array_map(function (string $key) use ($portfolioId, $records): array {
            $record = $records->get($key);

            if (! $record instanceof OperationalReadinessCheck) {
                return [
                    'key' => $key,
                    'label' => trans("app.readiness.manual.{$key}.label"),
                    'description' => trans("app.readiness.manual.{$key}.description"),
                    'is_confirmed' => false,
                    'evidence' => null,
                    'confirmed_at' => null,
                    'confirmed_by' => null,
                    'portfolio_id' => $portfolioId,
                ];
            }

            return [
                'key' => $key,
                'label' => trans("app.readiness.manual.{$key}.label"),
                'description' => trans("app.readiness.manual.{$key}.description"),
                'is_confirmed' => $record->is_confirmed,
                'evidence' => $record->evidence,
                'confirmed_at' => $record->confirmed_at?->toIso8601String(),
                'confirmed_by' => $record->confirmedBy?->name,
                'portfolio_id' => $portfolioId,
            ];
        }, $keys);
    }
}
