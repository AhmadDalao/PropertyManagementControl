<?php

namespace App\Modules\Cms\Queries;

use App\Models\CmsSection;
use Illuminate\Database\Eloquent\Collection;

final class CmsSectionLibraryQuery
{
    private const LIMIT = 60;

    /** @return array{sections:Collection<int,CmsSection>,sectionLimitReached:bool} */
    public function handle(): array
    {
        $count = CmsSection::query()->count();

        return [
            'sections' => CmsSection::query()
                ->select(['id', 'section_type', 'name_en', 'name_ar', 'status', 'created_at'])
                ->withCount('pageSections')
                ->orderByRaw("CASE WHEN status = 'active' THEN 0 WHEN status = 'inactive' THEN 1 ELSE 2 END")
                ->orderBy('name_en')
                ->limit(self::LIMIT)
                ->get(),
            'sectionLimitReached' => $count > self::LIMIT,
        ];
    }
}
