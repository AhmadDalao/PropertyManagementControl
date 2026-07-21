<?php

namespace App\Modules\Cms\Support;

final class CmsOptions
{
    /** @var array<int, string> */
    public const PAGE_STATUSES = ['draft', 'published', 'archived'];

    /** @var array<int, string> */
    public const SECTION_STATUSES = ['active', 'inactive', 'archived'];

    /** @var array<int, string> */
    public const SECTION_TYPES = [
        'hero',
        'role_cards',
        'workflow',
        'dashboard_preview',
        'feature_grid',
        'operations_strip',
        'faq',
        'final_cta',
        'metrics',
        'content',
    ];

    /** @var array<int, string> */
    public const WORKSPACE_VIEWS = ['pages', 'sections', 'navigation'];

    /** @var array<int, string> */
    public const NAVIGATION_LOCATIONS = ['header', 'footer'];

    /** @var array<int, string> */
    public const NAVIGATION_TARGETS = ['_self', '_blank'];

    /** @return array<int, array{label:string,value:string}> */
    public static function sectionTypes(): array
    {
        return collect(self::SECTION_TYPES)
            ->map(fn (string $type): array => [
                'label' => trans("app.cms.section_types.{$type}"),
                'value' => $type,
            ])
            ->all();
    }
}
