<?php

namespace App\Modules\Cms\Queries;

use App\Models\CmsPage;
use App\Models\CmsSection;
use Illuminate\Database\Eloquent\Collection;

final class CmsBuilderQuery
{
    private const LIBRARY_LIMIT = 100;

    /** @return array{page:CmsPage,sections:Collection<int,CmsSection>,libraryLimitReached:bool} */
    public function handle(CmsPage $page): array
    {
        $page->loadMissing([
            'pageSections.section',
            'navigationItems' => fn ($query) => $query->orderBy('sort_order'),
        ]);
        $libraryCount = CmsSection::query()->where('status', '!=', 'archived')->count();

        return [
            'page' => $page,
            'sections' => CmsSection::query()
                ->select(['id', 'section_type', 'name_en', 'name_ar', 'status', 'content_en', 'content_ar', 'settings_json'])
                ->where('status', '!=', 'archived')
                ->withCount('pageSections')
                ->orderByRaw("CASE WHEN status = 'active' THEN 0 ELSE 1 END")
                ->orderBy('name_en')
                ->limit(self::LIBRARY_LIMIT)
                ->get(),
            'libraryLimitReached' => $libraryCount > self::LIBRARY_LIMIT,
        ];
    }
}
