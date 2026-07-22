<?php

namespace App\Modules\Cms\Support;

use App\Models\CmsPage;
use Illuminate\Support\Str;

final class CmsPageAttributes
{
    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function forCreate(array $data): array
    {
        return $this->build($data);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function forUpdate(CmsPage $page, array $data): array
    {
        return $this->build($data, $page);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function build(array $data, ?CmsPage $page = null): array
    {
        $status = (string) $data['status'];
        $isHomepage = (bool) ($data['is_homepage'] ?? ($page ? $page->is_homepage : false));
        $isVisible = (bool) ($data['is_visible'] ?? ($page ? $page->is_visible : true));

        if ($status === 'archived') {
            $isHomepage = false;
            $isVisible = false;
        }

        return [
            'slug' => $this->uniqueSlug((string) ($data['slug'] ?: $data['title_en']), $page?->id),
            'title_en' => $data['title_en'],
            'title_ar' => $data['title_ar'],
            'excerpt_en' => $data['excerpt_en'] ?? null,
            'excerpt_ar' => $data['excerpt_ar'] ?? null,
            'seo_title_en' => $data['seo_title_en'] ?? null,
            'seo_title_ar' => $data['seo_title_ar'] ?? null,
            'seo_description_en' => $data['seo_description_en'] ?? null,
            'seo_description_ar' => $data['seo_description_ar'] ?? null,
            'status' => $status,
            'is_homepage' => $isHomepage,
            'is_visible' => $isVisible,
            'published_at' => $status === 'published'
                ? ($page && $page->published_at ? $page->published_at : now())
                : null,
        ];
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
