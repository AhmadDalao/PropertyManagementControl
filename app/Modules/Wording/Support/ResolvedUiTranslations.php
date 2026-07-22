<?php

namespace App\Modules\Wording\Support;

use App\Models\LabelOverride;
use App\Modules\Wording\Queries\GlobalWordingOverrideQuery;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Schema;

class ResolvedUiTranslations
{
    /** @var array<string, array<string, mixed>> */
    private array $resolved = [];

    public function __construct(
        private readonly TranslationDefaults $defaults,
        private readonly GlobalWordingOverrideQuery $overrides,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function forLocale(string $locale): array
    {
        if (isset($this->resolved[$locale])) {
            return $this->resolved[$locale];
        }

        return $this->resolved[$locale] = Cache::remember(
            $this->cacheKey($locale),
            now()->addDay(),
            fn (): array => $this->resolve($locale),
        );
    }

    /**
     * @param  array<string, scalar|null>  $replacements
     */
    public function translate(
        string $key,
        array $replacements = [],
        ?string $locale = null,
        string $fallback = '',
    ): string {
        $locale ??= app()->getLocale();
        $value = Arr::get($this->forLocale($locale), $key);
        $translated = is_string($value) ? $value : ($fallback !== '' ? $fallback : $key);

        foreach ($replacements as $name => $replacement) {
            $translated = str_replace(":{$name}", (string) $replacement, $translated);
        }

        return $translated;
    }

    public function text(string $value, ?string $locale = null): string
    {
        $locale ??= app()->getLocale();
        $translated = $this->forLocale($locale)['text'][$value] ?? null;

        return is_string($translated) ? $translated : $value;
    }

    public function applyLaravelOverrides(string $locale): void
    {
        if (! Schema::hasTable('label_overrides')) {
            return;
        }

        Lang::get('app', [], $locale);

        foreach (TranslationDefaults::FRAMEWORK_GROUPS as $group) {
            Lang::get($group, [], $locale);
        }

        $this->overrides->forLocale($locale)
            ->each(function (LabelOverride $override) use ($locale): void {
                if (! $this->defaults->isEditable($override->group_name, $override->override_key)) {
                    return;
                }

                $prefix = in_array(
                    $override->group_name,
                    TranslationDefaults::FRAMEWORK_GROUPS,
                    true,
                ) ? '' : 'app.';

                Lang::addLines(
                    ["{$prefix}{$override->group_name}.{$override->override_key}" => $override->value],
                    $locale,
                );
            });
    }

    public function forget(): void
    {
        foreach (['en', 'ar'] as $locale) {
            Cache::forget($this->cacheKey($locale));
            unset($this->resolved[$locale]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function resolve(string $locale): array
    {
        $translations = $this->defaults->forLocale($locale);

        if (! Schema::hasTable('label_overrides')) {
            return $translations;
        }

        $this->overrides->forLocale($locale)
            ->each(function (LabelOverride $override) use (&$translations): void {
                if (! $this->defaults->isEditable($override->group_name, $override->override_key)) {
                    return;
                }

                if ($override->group_name === 'text') {
                    $translations['text'][$override->override_key] = $override->value;

                    return;
                }

                Arr::set(
                    $translations,
                    "{$override->group_name}.{$override->override_key}",
                    $override->value,
                );
            });

        return $translations;
    }

    private function cacheKey(string $locale): string
    {
        return "ui-translations:v26:{$locale}";
    }
}
