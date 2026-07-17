<?php

namespace App\Models;

use App\Models\Concerns\LogsModelActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CmsPageSection extends Model
{
    use HasFactory;
    use LogsModelActivity;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'is_visible' => 'boolean',
            'settings_json' => 'array',
        ];
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(CmsPage::class, 'cms_page_id');
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(CmsSection::class, 'cms_section_id');
    }
}
