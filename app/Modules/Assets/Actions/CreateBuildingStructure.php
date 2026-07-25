<?php

namespace App\Modules\Assets\Actions;

use App\Models\Asset;
use App\Models\Portfolio;
use App\Models\User;
use App\Modules\Assets\Support\BuildingStructureInputGuard;
use App\Modules\Assets\Support\BuildingStructurePlan;
use App\Modules\Assets\Support\BuildingStructureReferenceGuard;
use App\Modules\Shared\PortfolioScope;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class CreateBuildingStructure
{
    public function __construct(
        private readonly PortfolioScope $portfolios,
        private readonly BuildingStructureInputGuard $input,
        private readonly BuildingStructureReferenceGuard $references,
        private readonly BuildingStructureFactory $factory,
        private readonly BuildingStructurePlan $plan,
    ) {}

    /** @param array<string, mixed> $data */
    public function handle(User $actor, array $data): Asset
    {
        abort_unless(
            $actor->hasAnyRole(['superadmin', 'owner']),
            403,
            trans('app.errors.section_access_denied'),
        );

        $portfolioId = $this->portfolioId($actor, $data);
        $this->portfolios->ensureAccess($actor, $portfolioId);
        $this->input->ensure($data);

        return DB::transaction(function () use ($data, $portfolioId): Asset {
            $portfolio = Portfolio::query()->lockForUpdate()->find($portfolioId);

            if (! $portfolio || $portfolio->status !== 'active') {
                $this->fail('portfolio_id', trans('app.errors.asset_portfolio_inactive'));
            }

            $this->ensureCodesAvailable($data);
            $this->references->ensure($data, $portfolioId);

            return $this->factory->create($data, $portfolioId);
        }, 3);
    }

    /** @param array<string, mixed> $data */
    private function ensureCodesAvailable(array $data): void
    {
        $codes = $this->plan->codes(
            strtoupper(trim((string) $data['code_prefix'])),
            (int) $data['floor_start'],
            (int) $data['floor_count'],
            (int) $data['units_per_floor'],
        );

        if (Asset::query()->whereIn('code', $codes)->lockForUpdate()->exists()) {
            $this->fail('code_prefix', trans('app.assets.builder.code_conflict'));
        }
    }

    /** @param array<string, mixed> $data */
    private function portfolioId(User $actor, array $data): int
    {
        $portfolioId = $actor->hasRole('superadmin')
            ? (int) ($data['portfolio_id'] ?? 0)
            : (int) ($actor->portfolio_id ?? 0);

        if ($portfolioId < 1) {
            $this->fail('portfolio_id', trans('validation.required', [
                'attribute' => trans('app.assets.portfolio'),
            ]));
        }

        return $portfolioId;
    }

    private function fail(string $field, string $message): never
    {
        throw ValidationException::withMessages([$field => $message]);
    }
}
