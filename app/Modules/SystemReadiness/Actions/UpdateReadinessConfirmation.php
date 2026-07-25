<?php

namespace App\Modules\SystemReadiness\Actions;

use App\Models\OperationalReadinessCheck;
use App\Models\Portfolio;
use App\Models\User;
use App\Modules\SystemReadiness\Support\ReadinessAccess;
use App\Modules\SystemReadiness\Support\ReadinessCheckCatalog;
use Illuminate\Support\Facades\DB;

final class UpdateReadinessConfirmation
{
    public function __construct(
        private readonly ReadinessAccess $access,
        private readonly ReadinessCheckCatalog $catalog,
    ) {}

    public function handle(
        User $actor,
        string $key,
        bool $confirmed,
        ?string $evidence,
        ?int $portfolioId,
    ): OperationalReadinessCheck {
        $this->access->ensureSuperadmin($actor);
        $scope = $this->catalog->scope($key);

        if ($scope === 'portfolio') {
            abort_unless(
                $portfolioId !== null && Portfolio::query()->whereKey($portfolioId)->exists(),
                422,
                trans('app.readiness.portfolio_required'),
            );
        } else {
            $portfolioId = null;
        }

        $scopeKey = $this->catalog->scopeKey($scope, $portfolioId);

        return DB::transaction(function () use ($actor, $confirmed, $evidence, $key, $portfolioId, $scopeKey): OperationalReadinessCheck {
            $check = OperationalReadinessCheck::query()
                ->lockForUpdate()
                ->firstOrNew([
                    'scope_key' => $scopeKey,
                    'key' => $key,
                ]);

            $check->fill([
                'portfolio_id' => $portfolioId,
                'confirmed_by_user_id' => $confirmed ? $actor->id : null,
                'is_confirmed' => $confirmed,
                'evidence' => $confirmed ? trim((string) $evidence) : null,
                'confirmed_at' => $confirmed ? now() : null,
            ]);
            $check->save();

            return $check;
        });
    }
}
