<?php

namespace App\Modules\Reports\Actions;

use App\Models\ReportPreset;
use App\Models\User;
use App\Modules\Reports\Support\ReportAccess;
use App\Modules\Reports\Support\ReportFilterSet;
use App\Modules\Reports\Support\ReportPropertyScope;
use Illuminate\Support\Facades\DB;

class ManageReportPresets
{
    public function __construct(
        private readonly ReportAccess $access,
        private readonly ReportFilterSet $filters,
        private readonly ReportPropertyScope $properties,
    ) {}

    /** @param array<string, mixed> $data */
    public function create(User $actor, array $data): ReportPreset
    {
        $filters = $this->filters->stored($data['filters_json'] ?? []);
        $visibility = (string) $data['visibility'];

        if ($visibility === 'global') {
            unset($filters['portfolio_id'], $filters['property_id']);
        }

        $this->access->ensurePortfolioFilter($actor, $filters['portfolio_id'] ?? null);
        $this->properties->assetIds(
            $actor,
            $filters['portfolio_id'] ?? null,
            $filters['property_id'] ?? null,
        );
        $portfolioId = $this->access->portfolioIdForPreset($actor, $visibility, $filters);
        $isDefault = (bool) ($data['is_default'] ?? false);

        return DB::transaction(function () use ($actor, $data, $filters, $visibility, $portfolioId, $isDefault): ReportPreset {
            $this->clearDefault($actor, $isDefault);

            return ReportPreset::query()->create([
                'portfolio_id' => $portfolioId,
                'user_id' => $actor->id,
                'resource' => 'portfolio-report',
                'title_en' => trim((string) $data['title_en']),
                'title_ar' => trim((string) $data['title_ar']),
                'filters_json' => $filters,
                'visibility' => $visibility,
                'is_default' => $isDefault,
            ]);
        });
    }

    /** @param array<string, mixed> $data */
    public function update(User $actor, ReportPreset $preset, array $data): ReportPreset
    {
        $this->access->ensureCanEditPreset($actor, $preset);
        $filters = $this->filters->stored($data['filters_json'] ?? []);
        $visibility = (string) $data['visibility'];

        if ($visibility === 'global') {
            unset($filters['portfolio_id'], $filters['property_id']);
        }

        $this->access->ensurePortfolioFilter($actor, $filters['portfolio_id'] ?? null);
        $this->properties->assetIds(
            $actor,
            $filters['portfolio_id'] ?? null,
            $filters['property_id'] ?? null,
        );
        $portfolioId = $this->access->portfolioIdForPreset($actor, $visibility, $filters);
        $isDefault = (bool) ($data['is_default'] ?? false);

        return DB::transaction(function () use ($actor, $preset, $data, $filters, $visibility, $portfolioId, $isDefault): ReportPreset {
            $this->clearDefault($actor, $isDefault, $preset);
            $preset->update([
                'portfolio_id' => $portfolioId,
                'title_en' => trim((string) $data['title_en']),
                'title_ar' => trim((string) $data['title_ar']),
                'filters_json' => $filters,
                'visibility' => $visibility,
                'is_default' => $isDefault,
            ]);

            return $preset->refresh();
        });
    }

    public function duplicate(User $actor, ReportPreset $preset): ReportPreset
    {
        $this->access->ensureCanViewPreset($actor, $preset);
        $filters = $this->filters->stored($preset->filters_json);
        $this->properties->assetIds(
            $actor,
            $filters['portfolio_id'] ?? null,
            $filters['property_id'] ?? null,
        );

        return $this->create($actor, [
            'title_en' => trim($preset->title_en).' copy',
            'title_ar' => 'نسخة من '.trim($preset->title_ar ?: $preset->title_en),
            'visibility' => 'private',
            'is_default' => false,
            'filters_json' => $filters,
        ]);
    }

    public function delete(User $actor, ReportPreset $preset): void
    {
        $this->access->ensureCanDeletePreset($actor, $preset);
        $preset->delete();
    }

    private function clearDefault(User $actor, bool $isDefault, ?ReportPreset $except = null): void
    {
        if (! $isDefault) {
            return;
        }

        ReportPreset::query()
            ->where('user_id', $actor->id)
            ->where('resource', 'portfolio-report')
            ->when($except, fn ($query) => $query->whereKeyNot($except->getKey()))
            ->update(['is_default' => false]);
    }
}
