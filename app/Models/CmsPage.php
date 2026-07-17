<?php

namespace App\Models;

use App\Models\Concerns\LogsModelActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CmsPage extends Model
{
    use HasFactory;
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

    public function pageSections(): HasMany
    {
        return $this->hasMany(CmsPageSection::class)->orderBy('sort_order');
    }

    public function navigationItems(): HasMany
    {
        return $this->hasMany(NavigationItem::class);
    }
}
