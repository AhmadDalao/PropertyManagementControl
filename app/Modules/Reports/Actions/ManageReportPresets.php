<?php

namespace App\Modules\Reports\Actions;

use App\Models\ReportPreset;
use App\Models\User;
use App\Modules\Reports\Support\ReportAccess;
use App\Modules\Reports\Support\ReportFilterSet;
use Illuminate\Support\Facades\DB;

class ManageReportPresets
{
    public function __construct(
        private readonly ReportAccess $access,
        private readonly ReportFilterSet $filters,
    ) {}

    /** @param array<string, mixed> $data */
    public function create(User $actor, array $data): ReportPreset
    {
        $filters = $this->filters->stored($data['filters_json'] ?? []);
        $visibility = (string) $data['visibility'];
        $this->access->ensurePortfolioFilter($actor, $filters['portfolio_id'] ?? null);
        $portfolioId = $this->access->portfolioIdForPreset($actor, $visibility, $filters);
        $isDefault = (bool) ($data['is_default'] ?? false);

        return DB::transaction(function () use ($actor, $data, $filters, $visibility, $portfolioId, $isDefault): ReportPreset {
            if ($isDefault) {
                ReportPreset::query()
                    ->where('user_id', $actor->id)
                    ->where('resource', 'portfolio-report')
                    ->update(['is_default' => false]);
            }

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

    public function delete(User $actor, ReportPreset $preset): void
    {
        $this->access->ensureCanDeletePreset($actor, $preset);
        $preset->delete();
    }
}
