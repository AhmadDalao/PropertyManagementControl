<?php

namespace App\Modules\Cms\Support;

use App\Models\CmsPage;
use App\Models\CmsPageSection;
use Illuminate\Validation\ValidationException;

final class CmsPageSectionOrder
{
    public function normalize(CmsPage $page): void
    {
        $page->pageSections()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->pluck('id')
            ->values()
            ->each(fn (mixed $id, int $index) => CmsPageSection::query()
                ->whereKey((int) $id)
                ->update(['sort_order' => $index + 1]));
    }

    /** @param array<int, int> $orderedIds */
    public function apply(CmsPage $page, array $orderedIds): void
    {
        $currentIds = $page->pageSections()
            ->lockForUpdate()
            ->pluck('id')
            ->map(fn (mixed $id): int => (int) $id)
            ->all();
        $expected = $currentIds;
        $received = array_values($orderedIds);
        sort($expected);
        $sortedReceived = $received;
        sort($sortedReceived);

        if ($expected !== $sortedReceived) {
            throw ValidationException::withMessages([
                'ordered_ids' => trans('app.errors.cms_reorder_incomplete'),
            ]);
        }

        foreach ($received as $index => $id) {
            CmsPageSection::query()
                ->whereKey($id)
                ->where('cms_page_id', $page->id)
                ->update(['sort_order' => $index + 1]);
        }
    }
}
