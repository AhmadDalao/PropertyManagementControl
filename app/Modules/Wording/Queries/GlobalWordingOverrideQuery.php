<?php

namespace App\Modules\Wording\Queries;

use App\Models\LabelOverride;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class GlobalWordingOverrideQuery
{
    /**
     * @return Collection<int, LabelOverride>
     */
    public function forLocale(string $locale): Collection
    {
        return $this->base()
            ->where('locale', $locale)
            ->get();
    }

    /**
     * @return Collection<int, LabelOverride>
     */
    public function bilingual(): Collection
    {
        return $this->base()
            ->whereIn('locale', ['en', 'ar'])
            ->get();
    }

    public function index(string $group, string $key, string $locale): string
    {
        return "{$group}\0{$key}\0{$locale}";
    }

    /**
     * @return Builder<LabelOverride>
     */
    private function base(): Builder
    {
        return LabelOverride::query()
            ->whereNull('portfolio_id')
            ->whereNull('context_type')
            ->whereNull('context_id');
    }
}
