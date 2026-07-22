<?php

namespace App\Modules\Cms\Support;

use App\Models\CmsPage;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

final class CmsRules
{
    /** @return array<string, array<int, mixed>> */
    public static function page(?CmsPage $page = null): array
    {
        return [
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('cms_pages', 'slug')->ignore($page?->id),
            ],
            'title_en' => ['required', 'string', 'max:255'],
            'title_ar' => ['required', 'string', 'max:255'],
            'excerpt_en' => ['nullable', 'string', 'max:3000'],
            'excerpt_ar' => ['nullable', 'string', 'max:3000'],
            'seo_title_en' => ['nullable', 'string', 'max:255'],
            'seo_title_ar' => ['nullable', 'string', 'max:255'],
            'seo_description_en' => ['nullable', 'string', 'max:3000'],
            'seo_description_ar' => ['nullable', 'string', 'max:3000'],
            'status' => ['required', Rule::in(CmsOptions::PAGE_STATUSES)],
            'is_homepage' => ['sometimes', 'boolean'],
            'is_visible' => ['sometimes', 'boolean'],
        ];
    }

    /** @return array<string, array<int, mixed>> */
    public static function section(): array
    {
        return [
            'section_type' => ['required', Rule::in(CmsOptions::SECTION_TYPES)],
            'name_en' => ['required', 'string', 'max:255'],
            'name_ar' => ['required', 'string', 'max:255'],
            'content_en' => ['nullable', 'array', 'max:100'],
            'content_ar' => ['nullable', 'array', 'max:100'],
            'settings_json' => ['nullable', 'array', 'max:100'],
            'status' => ['required', Rule::in(CmsOptions::SECTION_STATUSES)],
        ];
    }

    /** @return array<string, array<int, mixed>> */
    public static function navigation(): array
    {
        return [
            'parent_id' => ['nullable', 'integer', 'exists:navigation_items,id'],
            'cms_page_id' => [
                'nullable',
                'integer',
                Rule::exists('cms_pages', 'id')->where(
                    fn (Builder $query): Builder => $query->where('status', '!=', 'archived'),
                ),
            ],
            'location' => ['required', Rule::in(CmsOptions::NAVIGATION_LOCATIONS)],
            'title_en' => ['required', 'string', 'max:255'],
            'title_ar' => ['required', 'string', 'max:255'],
            'url' => [
                'nullable',
                'string',
                'max:255',
                'required_without:cms_page_id',
                'regex:/^(?:\/(?!\/)|#|https?:\/\/|mailto:|tel:)/i',
            ],
            'target' => ['required', Rule::in(CmsOptions::NAVIGATION_TARGETS)],
            'sort_order' => ['required', 'integer', 'min:0'],
            'is_visible' => ['sometimes', 'boolean'],
        ];
    }

    /** @return array<string, array<int, mixed>> */
    public static function attachment(): array
    {
        return [
            'cms_section_id' => [
                'required',
                'integer',
                Rule::exists('cms_sections', 'id')->where(
                    fn (Builder $query): Builder => $query->where('status', '!=', 'archived'),
                ),
            ],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_visible' => ['sometimes', 'boolean'],
        ];
    }

    /** @return array<string, array<int, mixed>> */
    public static function pageSection(): array
    {
        return [
            'sort_order' => ['sometimes', 'integer', 'min:0'],
            'is_visible' => ['sometimes', 'boolean'],
            'settings_json' => ['sometimes', 'nullable', 'array', 'max:100'],
        ];
    }

    /** @return array<string, array<int, mixed>> */
    public static function reorder(): array
    {
        return [
            'ordered_ids' => ['required', 'array', 'min:1'],
            'ordered_ids.*' => ['required', 'integer', 'distinct', 'exists:cms_page_sections,id'],
        ];
    }

    /** @return array<string, string> */
    public static function attributes(): array
    {
        $attributes = trans('app.cms.validation_attributes');

        return is_array($attributes)
            ? array_filter($attributes, fn (mixed $value, mixed $key): bool => is_string($key) && is_string($value), ARRAY_FILTER_USE_BOTH)
            : [];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function normalizePage(array $data): array
    {
        foreach ([
            'title_en',
            'title_ar',
            'excerpt_en',
            'excerpt_ar',
            'seo_title_en',
            'seo_title_ar',
            'seo_description_en',
            'seo_description_ar',
        ] as $field) {
            self::trim($data, $field);
        }

        self::nullWhenBlank($data, [
            'excerpt_en',
            'excerpt_ar',
            'seo_title_en',
            'seo_title_ar',
            'seo_description_en',
            'seo_description_ar',
        ]);

        $slug = trim((string) ($data['slug'] ?? ''));
        $data['slug'] = $slug === '' ? null : (Str::slug($slug) ?: null);

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function normalizeSection(array $data): array
    {
        self::trim($data, 'name_en');
        self::trim($data, 'name_ar');

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function normalizeNavigation(array $data): array
    {
        foreach (['title_en', 'title_ar', 'url'] as $field) {
            self::trim($data, $field);
        }

        self::nullWhenBlank($data, ['parent_id', 'cms_page_id', 'url']);

        return $data;
    }

    /** @param array<string, mixed> $data */
    private static function trim(array &$data, string $field): void
    {
        if (array_key_exists($field, $data)) {
            $data[$field] = trim((string) $data[$field]);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, string>  $fields
     */
    private static function nullWhenBlank(array &$data, array $fields): void
    {
        foreach ($fields as $field) {
            if (($data[$field] ?? null) === '') {
                $data[$field] = null;
            }
        }
    }

    private function __construct() {}
}
