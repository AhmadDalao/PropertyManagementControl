<?php

namespace App\Modules\Cms\Queries;

use App\Models\CmsPage;
use App\Models\CmsSection;
use App\Models\NavigationItem;

final class CmsWorkspaceInsightsQuery
{
    /** @return array<string, int> */
    public function handle(): array
    {
        return [
            'pages' => CmsPage::query()->count(),
            'published' => CmsPage::query()->where('status', 'published')->count(),
            'drafts' => CmsPage::query()->where('status', 'draft')->count(),
            'missing_arabic' => CmsPage::query()
                ->where(fn ($query) => $query
                    ->whereNull('title_ar')
                    ->orWhere('title_ar', ''))
                ->count(),
            'sections' => CmsSection::query()->count(),
            'active_sections' => CmsSection::query()->where('status', 'active')->count(),
            'navigation' => NavigationItem::query()->count(),
            'visible_navigation' => NavigationItem::query()->where('is_visible', true)->count(),
        ];
    }
}
