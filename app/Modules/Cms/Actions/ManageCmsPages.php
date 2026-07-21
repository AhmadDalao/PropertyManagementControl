<?php

namespace App\Modules\Cms\Actions;

use App\Models\CmsPage;
use App\Models\CmsPageSection;
use App\Models\User;
use App\Modules\Cms\Support\CmsAccess;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ManageCmsPages
{
    public function __construct(private readonly CmsAccess $access) {}

    /** @param array<string, mixed> $data */
    public function create(User $actor, array $data): CmsPage
    {
        $this->access->ensureAdmin($actor);

        return DB::transaction(function () use ($data): CmsPage {
            $payload = $this->payload($data);
            $this->ensurePublishable($payload);
            $this->ensureHomepageIsPublic($payload);
            $this->clearOtherHomepages($payload);

            return CmsPage::query()->create($payload);
        });
    }

    /** @param array<string, mixed> $data */
    public function update(User $actor, CmsPage $target, array $data): CmsPage
    {
        $this->access->ensureAdmin($actor);

        return DB::transaction(function () use ($target, $data): CmsPage {
            $page = CmsPage::query()->lockForUpdate()->whereKey($target->id)->firstOrFail();
            $payload = $this->payload($data, $page);
            $this->ensurePublishable($payload, $page);
            $this->ensureHomepageIsPublic($payload);
            $this->clearOtherHomepages($payload, $page);
            $page->update($payload);

            return $page->refresh();
        });
    }

    public function archive(User $actor, CmsPage $target): CmsPage
    {
        $this->access->ensureAdmin($actor);

        return DB::transaction(function () use ($target): CmsPage {
            $page = CmsPage::query()->lockForUpdate()->whereKey($target->id)->firstOrFail();
            $page->update([
                'status' => 'archived',
                'is_visible' => false,
                'is_homepage' => false,
                'published_at' => null,
            ]);

            return $page->refresh();
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function payload(array $data, ?CmsPage $page = null): array
    {
        $status = (string) $data['status'];
        $isHomepage = (bool) ($data['is_homepage'] ?? ($page ? $page->is_homepage : false));
        $isVisible = (bool) ($data['is_visible'] ?? ($page ? $page->is_visible : true));

        if ($status === 'archived') {
            $isHomepage = false;
            $isVisible = false;
        }

        return [
            ...$data,
            'slug' => $this->uniqueSlug(
                (string) ($data['slug'] ?: $data['title_en']),
                $page?->id,
            ),
            'is_homepage' => $isHomepage,
            'is_visible' => $isVisible,
            'published_at' => $status === 'published'
                ? ($page && $page->published_at ? $page->published_at : now())
                : null,
        ];
    }

    /** @param array<string, mixed> $payload */
    private function ensureHomepageIsPublic(array $payload): void
    {
        if (! ($payload['is_homepage'] ?? false)) {
            return;
        }

        if (($payload['status'] ?? null) !== 'published' || ! ($payload['is_visible'] ?? false)) {
            throw ValidationException::withMessages([
                'is_homepage' => trans('app.errors.cms_homepage_must_be_public'),
            ]);
        }
    }

    /** @param array<string, mixed> $payload */
    private function clearOtherHomepages(array $payload, ?CmsPage $page = null): void
    {
        if (! ($payload['is_homepage'] ?? false)) {
            return;
        }

        CmsPage::query()
            ->where('is_homepage', true)
            ->when($page, fn ($query) => $query->whereKeyNot($page->id))
            ->lockForUpdate()
            ->get(['id']);

        CmsPage::query()
            ->where('is_homepage', true)
            ->when($page, fn ($query) => $query->whereKeyNot($page->id))
            ->update(['is_homepage' => false]);
    }

    /** @param array<string, mixed> $payload */
    private function ensurePublishable(array $payload, ?CmsPage $page = null): void
    {
        if (($payload['status'] ?? 'draft') !== 'published') {
            return;
        }

        $pairs = [
            [$payload['excerpt_en'] ?? null, $payload['excerpt_ar'] ?? null],
            [$payload['seo_title_en'] ?? null, $payload['seo_title_ar'] ?? null],
            [$payload['seo_description_en'] ?? null, $payload['seo_description_ar'] ?? null],
        ];
        $pairedFieldsComplete = collect($pairs)
            ->every(fn (array $pair): bool => blank($pair[0]) || filled($pair[1]));
        $sectionsComplete = $page === null || $page
            ->pageSections()
            ->where('is_visible', true)
            ->with('section')
            ->get()
            ->every(function (CmsPageSection $pageSection): bool {
                $section = $pageSection->section;

                return $section->status === 'active'
                    && filled($section->name_ar)
                    && (blank($section->content_en) || filled($section->content_ar));
            });

        if (! filled($payload['title_ar'] ?? null) || ! $pairedFieldsComplete || ! $sectionsComplete) {
            throw ValidationException::withMessages([
                'status' => trans('app.errors.cms_arabic_incomplete'),
            ]);
        }
    }

    private function uniqueSlug(string $value, ?int $ignoreId = null): string
    {
        $base = Str::slug($value) ?: 'page';
        $slug = $base;
        $suffix = 2;

        while (CmsPage::query()
            ->where('slug', $slug)
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}
