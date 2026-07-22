<?php

namespace App\Modules\Assets\Actions;

use App\Models\Asset;
use App\Models\User;
use App\Modules\Assets\Support\AssetAccess;
use App\Modules\Assets\Support\AssetAttributes;
use App\Modules\Assets\Support\AssetInputGuard;
use App\Modules\Assets\Support\AssetMetadata;
use App\Modules\Assets\Support\AssetReferenceGuard;
use App\Modules\Assets\Support\AssetStakeholderManager;
use App\Modules\Shared\PortfolioScope;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CreateAsset
{
    public function __construct(
        private readonly AssetAccess $access,
        private readonly PortfolioScope $portfolios,
        private readonly AssetAttributes $attributes,
        private readonly AssetInputGuard $input,
        private readonly AssetReferenceGuard $references,
        private readonly AssetStakeholderManager $stakeholders,
        private readonly AssetMetadata $metadata,
    ) {}

    /** @param array<string, mixed> $data */
    public function handle(User $actor, array $data): Asset
    {
        $this->access->ensureManager($actor);
        $portfolioId = $this->portfolioId($actor, $data);
        $this->portfolios->ensureAccess($actor, $portfolioId);
        $this->input->ensureCreate($data);

        return DB::transaction(function () use ($actor, $data, $portfolioId): Asset {
            $this->portfolios->ensureAccess($actor, $portfolioId);
            $this->references->ensureActivePortfolio($portfolioId);
            $this->references->ensure($data, $portfolioId);
            $asset = Asset::query()->create([
                ...$this->attributes->from($data),
                'portfolio_id' => $portfolioId,
                'code' => filled($data['code'] ?? null)
                    ? $data['code']
                    : Str::upper(Str::random(8)),
                'slug' => Str::slug($data['title_en']).'-'.Str::lower(Str::random(4)),
                'meta_json' => $this->metadata->merge($data),
            ]);

            $this->stakeholders->sync(
                $asset,
                $this->nullableId($data['primary_owner_user_id'] ?? null),
                $this->nullableId($data['primary_manager_user_id'] ?? null),
            );

            return $asset;
        }, 3);
    }

    /** @param array<string, mixed> $data */
    private function portfolioId(User $actor, array $data): int
    {
        $portfolioId = $data['portfolio_id'] ?? $actor->portfolio_id;

        if (! $portfolioId) {
            throw ValidationException::withMessages([
                'portfolio_id' => trans('validation.required', [
                    'attribute' => trans('app.assets.portfolio'),
                ]),
            ]);
        }

        return (int) $portfolioId;
    }

    private function nullableId(mixed $value): ?int
    {
        return filled($value) ? (int) $value : null;
    }
}
