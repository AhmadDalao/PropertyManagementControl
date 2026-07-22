<?php

namespace App\Modules\Cms\Support;

use App\Models\CmsPage;
use App\Models\CmsPageSection;
use App\Models\CmsSection;
use Illuminate\Validation\ValidationException;

final class CmsPublicationPolicy
{
    /** @param array<string, mixed> $payload */
    public function ensurePageCanPublish(array $payload, ?CmsPage $page = null): void
    {
        $this->ensureHomepageIsPublic($payload);

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
            ->every(fn (CmsPageSection $pageSection): bool => $this->sectionIsPublishable($pageSection->section));

        if (! filled($payload['title_ar'] ?? null) || ! $pairedFieldsComplete || ! $sectionsComplete) {
            throw ValidationException::withMessages([
                'status' => trans('app.errors.cms_arabic_incomplete'),
            ]);
        }
    }

    public function ensureSectionCanRender(CmsPage $page, CmsSection $section, bool $visible): void
    {
        if (! $visible || $page->status !== 'published' || ! $page->is_visible) {
            return;
        }

        if ($section->status !== 'active') {
            throw ValidationException::withMessages([
                'cms_section_id' => trans('app.errors.cms_live_section_inactive'),
            ]);
        }

        if (! $this->sectionIsPublishable($section)) {
            throw ValidationException::withMessages([
                'cms_section_id' => trans('app.errors.cms_live_section_incomplete'),
            ]);
        }
    }

    /** @param array<string, mixed> $payload */
    public function ensureSectionUpdateCanPublish(CmsSection $section, array $payload): void
    {
        if (($payload['status'] ?? null) !== 'active') {
            return;
        }

        $isUsedByPublicPage = $section->pageSections()
            ->where('is_visible', true)
            ->whereHas('page', fn ($query) => $query
                ->where('status', 'published')
                ->where('is_visible', true))
            ->exists();

        if ($isUsedByPublicPage && ! $this->sectionPayloadIsPublishable($payload)) {
            throw ValidationException::withMessages([
                'content_ar' => trans('app.errors.cms_live_section_incomplete'),
            ]);
        }
    }

    /** @param array<string, mixed> $payload */
    public function clearOtherHomepages(array $payload, ?CmsPage $page = null): void
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

    private function sectionIsPublishable(CmsSection $section): bool
    {
        return $this->sectionPayloadIsPublishable([
            'status' => $section->status,
            'name_ar' => $section->name_ar,
            'content_en' => $section->content_en,
            'content_ar' => $section->content_ar,
        ]);
    }

    /** @param array<string, mixed> $payload */
    private function sectionPayloadIsPublishable(array $payload): bool
    {
        return ($payload['status'] ?? null) === 'active'
            && filled($payload['name_ar'] ?? null)
            && (blank($payload['content_en'] ?? null) || filled($payload['content_ar'] ?? null));
    }
}
