<?php

namespace App\Modules\Wording\Support;

use Illuminate\Support\Arr;

class TranslationDefaults
{
    public const FRAMEWORK_GROUPS = ['auth', 'pagination', 'passwords', 'validation'];

    /** @var array<string, array<string, mixed>> */
    private array $resolved = [];

    public function __construct(
        private readonly DocumentationTranslationDefaults $documentation,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function forLocale(string $locale): array
    {
        if (isset($this->resolved[$locale])) {
            return $this->resolved[$locale];
        }

        $defaults = $this->languageFile($locale, 'app');
        $defaults['property_docs'] = $this->documentation->forLocale($locale);

        foreach (self::FRAMEWORK_GROUPS as $group) {
            $defaults[$group] = $this->languageFile($locale, $group);
        }

        return $this->resolved[$locale] = $defaults;
    }

    /**
     * @return array<string, string>
     */
    public function flat(string $locale): array
    {
        return collect(Arr::dot($this->forLocale($locale)))
            ->filter(fn (mixed $value): bool => is_string($value))
            ->map(fn (mixed $value): string => (string) $value)
            ->all();
    }

    public function value(string $group, string $key, string $locale): ?string
    {
        $defaults = $this->forLocale($locale);
        $value = $group === 'text'
            ? ($defaults['text'][$key] ?? null)
            : Arr::get($defaults, "{$group}.{$key}");

        if (is_string($value)) {
            return $value;
        }

        if ($group === 'text') {
            $otherLocale = $locale === 'en' ? 'ar' : 'en';
            $otherDefaults = $this->forLocale($otherLocale);

            if (is_string($otherDefaults['text'][$key] ?? null)) {
                return $key;
            }
        }

        return null;
    }

    public function isEditable(string $group, string $key): bool
    {
        return $this->value($group, $key, 'en') !== null
            && $this->value($group, $key, 'ar') !== null;
    }

    /**
     * @return array<string, mixed>
     */
    private function languageFile(string $locale, string $group): array
    {
        $path = lang_path("{$locale}/{$group}.php");

        if (! is_file($path)) {
            return [];
        }

        $translations = require $path;

        return is_array($translations) ? $translations : [];
    }
}
