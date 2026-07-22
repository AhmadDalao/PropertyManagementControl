<?php

namespace App\Modules\Documentation\Support;

use App\Modules\Wording\UiTranslationCatalog;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class DocumentationLocalizer
{
    public function __construct(
        private readonly UiTranslationCatalog $translations,
    ) {}

    /**
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>
     */
    public function localize(array $item, string $collection): array
    {
        $localized = Arr::get(
            $this->translations->forLocale(app()->getLocale()),
            "property_docs.{$collection}.{$this->translationKey($collection, $item)}",
            [],
        );
        $slug = $collection === 'guides'
            ? Str::slug((string) ($item['title'] ?? ''))
            : null;

        if (is_array($localized)) {
            $item = array_replace_recursive($item, $localized);
        }

        return $slug === null ? $item : [...$item, 'slug' => $slug];
    }

    /** @param array<string, mixed> $item */
    private function translationKey(string $collection, array $item): string
    {
        return match ($collection) {
            'workflows' => (string) ($item['key'] ?? ''),
            'role_guides' => (string) ($item['role'] ?? ''),
            'page_shortcuts' => Str::slug((string) ($item['label'] ?? '')),
            default => Str::slug((string) ($item['title'] ?? '')),
        };
    }
}
