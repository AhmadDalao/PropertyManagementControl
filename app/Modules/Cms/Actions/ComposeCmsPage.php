<?php

namespace App\Modules\Cms\Actions;

use App\Models\CmsPage;
use App\Models\CmsPageSection;
use App\Models\CmsSection;
use App\Models\User;
use App\Modules\Cms\Support\CmsAccess;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ComposeCmsPage
{
    public function __construct(private readonly CmsAccess $access) {}

    /** @param array<string, mixed> $data */
    public function attach(User $actor, CmsPage $target, array $data): CmsPageSection
    {
        $this->access->ensureAdmin($actor);

        return DB::transaction(function () use ($target, $data): CmsPageSection {
            $page = CmsPage::query()->lockForUpdate()->whereKey($target->id)->firstOrFail();
            $section = CmsSection::query()->lockForUpdate()->whereKey($data['cms_section_id'])->firstOrFail();

            if ($section->status === 'archived') {
                throw ValidationException::withMessages([
                    'cms_section_id' => trans('app.errors.cms_archived_section'),
                ]);
            }

            $pageSection = CmsPageSection::query()->updateOrCreate(
                [
                    'cms_page_id' => $page->id,
                    'cms_section_id' => $section->id,
                ],
                [
                    'sort_order' => $data['sort_order'] ?? ($page->pageSections()->max('sort_order') + 1),
                    'is_visible' => (bool) ($data['is_visible'] ?? true),
                ],
            );
            $this->normalizeOrder($page);

            return $pageSection->refresh();
        });
    }

    /** @param array<string, mixed> $data */
    public function update(User $actor, CmsPageSection $target, array $data): CmsPageSection
    {
        $this->access->ensureAdmin($actor);

        return DB::transaction(function () use ($target, $data): CmsPageSection {
            $pageSection = CmsPageSection::query()->lockForUpdate()->whereKey($target->id)->firstOrFail();
            $pageSection->update([
                'sort_order' => $data['sort_order'] ?? $pageSection->sort_order,
                'is_visible' => array_key_exists('is_visible', $data)
                    ? (bool) $data['is_visible']
                    : $pageSection->is_visible,
                'settings_json' => array_key_exists('settings_json', $data)
                    ? $data['settings_json']
                    : $pageSection->settings_json,
            ]);
            $this->normalizeOrder($pageSection->page()->firstOrFail());

            return $pageSection->refresh();
        });
    }

    /** @param array<int, int> $orderedIds */
    public function reorder(User $actor, CmsPage $target, array $orderedIds): void
    {
        $this->access->ensureAdmin($actor);

        DB::transaction(function () use ($target, $orderedIds): void {
            $page = CmsPage::query()->lockForUpdate()->whereKey($target->id)->firstOrFail();
            $currentIds = $page->pageSections()
                ->lockForUpdate()
                ->pluck('id')
                ->map(fn (mixed $id): int => (int) $id)
                ->all();
            $expected = $currentIds;
            $received = array_values(array_map('intval', $orderedIds));
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
        });
    }

    public function remove(User $actor, CmsPageSection $target): CmsPage
    {
        $this->access->ensureAdmin($actor);

        return DB::transaction(function () use ($target): CmsPage {
            $pageSection = CmsPageSection::query()->lockForUpdate()->whereKey($target->id)->firstOrFail();
            $page = $pageSection->page()->firstOrFail();
            $pageSection->delete();
            $this->normalizeOrder($page);

            return $page;
        });
    }

    private function normalizeOrder(CmsPage $page): void
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
}
