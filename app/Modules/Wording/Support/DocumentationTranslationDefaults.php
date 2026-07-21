<?php

namespace App\Modules\Wording\Support;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Str;

class DocumentationTranslationDefaults
{
    /**
     * @return array<string, mixed>
     */
    public function forLocale(string $locale): array
    {
        $configuration = config('property_docs', []);

        if (! is_array($configuration)) {
            return [];
        }

        return collect($configuration)
            ->filter(fn (mixed $items): bool => is_array($items))
            ->mapWithKeys(function (array $items, string $collection) use ($locale): array {
                $records = collect($items)
                    ->filter(fn (mixed $item): bool => is_array($item))
                    ->mapWithKeys(function (array $item) use ($collection, $locale): array {
                        $key = $this->key($collection, $item);
                        $content = $this->content($item);
                        $localized = Lang::get("property_docs.{$collection}.{$key}", [], $locale);

                        if (is_array($localized)) {
                            $content = array_replace_recursive($content, $localized);
                        }

                        return [$key => $this->content($content)];
                    })
                    ->all();

                return [$collection => $records];
            })
            ->all();
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function key(string $collection, array $item): string
    {
        return match ($collection) {
            'workflows' => (string) ($item['key'] ?? ''),
            'role_guides' => (string) ($item['role'] ?? ''),
            'page_shortcuts' => Str::slug((string) ($item['label'] ?? '')),
            default => Str::slug((string) ($item['title'] ?? '')),
        };
    }

    /**
     * Exclude routes, roles, icons, modules, and tags from editable wording.
     *
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>
     */
    private function content(array $item): array
    {
        $allowed = [
            'title',
            'audience',
            'summary',
            'outcome',
            'label',
            'category',
            'description',
            'action',
            'responsibilities',
            'features',
            'steps',
            'rules',
            'checks',
        ];

        return collect(Arr::only($item, $allowed))
            ->map(function (mixed $value): mixed {
                if (! is_array($value)) {
                    return $value;
                }

                return collect($value)
                    ->map(fn (mixed $entry): mixed => is_array($entry)
                        ? Arr::only($entry, ['label'])
                        : $entry)
                    ->all();
            })
            ->all();
    }
}
