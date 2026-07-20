<?php

namespace App\Models\Concerns;

use App\Models\Portfolio;

trait HasShowcaseBadge
{
    /** @var array<int, bool> */
    protected static array $showcasePortfolioFlags = [];

    public function initializeHasShowcaseBadge(): void
    {
        if (! in_array('is_showcase', $this->appends, true)) {
            $this->appends[] = 'is_showcase';
        }
    }

    public function getIsShowcaseAttribute(): bool
    {
        $attributes = $this->getAttributes();

        if (array_key_exists('showcase_dataset_id', $attributes) && $attributes['showcase_dataset_id'] !== null) {
            return true;
        }

        $portfolioId = isset($attributes['portfolio_id'])
            ? (int) $attributes['portfolio_id']
            : null;

        if (! $portfolioId) {
            return false;
        }

        if ($this->relationLoaded('portfolio')) {
            return $this->getRelation('portfolio')?->showcase_dataset_id !== null;
        }

        return self::$showcasePortfolioFlags[$portfolioId] ??= Portfolio::query()
            ->whereKey($portfolioId)
            ->whereNotNull('showcase_dataset_id')
            ->exists();
    }
}
