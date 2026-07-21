<?php

namespace App\Models;

use App\Models\Concerns\LogsModelActivity;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property-read Collection<int, CmsPageSection> $pageSections
 * @property-read Collection<int, NavigationItem> $navigationItems
 */
class CmsPage extends Model
{
    use LogsModelActivity;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'is_homepage' => 'boolean',
            'is_visible' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    /** @return HasMany<CmsPageSection, $this> */
    public function pageSections(): HasMany
    {
        return $this->hasMany(CmsPageSection::class)->orderBy('sort_order');
    }

    /** @return HasMany<NavigationItem, $this> */
    public function navigationItems(): HasMany
    {
        return $this->hasMany(NavigationItem::class);
    }
}
