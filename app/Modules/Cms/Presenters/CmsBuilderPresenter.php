<?php

namespace App\Modules\Cms\Presenters;

use App\Models\CmsPage;
use App\Models\CmsSection;
use App\Models\User;
use App\Modules\Cms\Support\CmsAccess;
use App\Modules\Shared\ResourcePresenter;

class CmsBuilderPresenter
{
    private const LIBRARY_LIMIT = 100;

    public function __construct(
        private readonly CmsAccess $access,
        private readonly ResourcePresenter $resources,
    ) {}

    /** @return array<string, mixed> */
    public function present(User $actor, CmsPage $page): array
    {
        $this->access->ensureAdmin($actor);
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
            'timeline' => $this->resources->activityTimeline($page),
        ];
    }
}
