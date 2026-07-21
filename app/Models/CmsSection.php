<?php

namespace App\Models;

use App\Models\Concerns\LogsModelActivity;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** @property-read Collection<int, CmsPageSection> $pageSections */
class CmsSection extends Model
{
    use LogsModelActivity;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'content_en' => 'array',
            'content_ar' => 'array',
            'settings_json' => 'array',
        ];
    }

    /** @return HasMany<CmsPageSection, $this> */
    public function pageSections(): HasMany
    {
        return $this->hasMany(CmsPageSection::class);
    }
}
