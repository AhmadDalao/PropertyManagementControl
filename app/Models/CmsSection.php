<?php

namespace App\Models;

use App\Models\Concerns\LogsModelActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CmsSection extends Model
{
    use HasFactory;
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

    public function pageSections(): HasMany
    {
        return $this->hasMany(CmsPageSection::class);
    }
}
