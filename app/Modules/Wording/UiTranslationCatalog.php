<?php

namespace App\Modules\Wording;

use App\Models\LabelOverride;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class UiTranslationCatalog
{
    private const FRAMEWORK_GROUPS = ['auth', 'pagination', 'passwords', 'validation'];

    /**
     * @var array<string, array<string, mixed>>
     */
    private array $resolved = [];

    /**
     * Return the active UI dictionary with database overrides applied.
     *
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
            function () use ($locale): array {
                $translations = $this->defaults($locale);

                if (! Schema::hasTable('label_overrides')) {
                    return $translations;
                }

                LabelOverride::query()
                    ->whereNull('portfolio_id')
                    ->whereNull('context_type')
                    ->whereNull('context_id')
                    ->where('locale', $locale)
                    ->get()
                    ->each(function (LabelOverride $override) use (&$translations): void {
                        if (! $this->isEditable($override->group_name, $override->override_key)) {
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
            },
        );
    }

    /**
     * Build the bilingual catalog used by the wording workspace.
     *
     * @return array<int, array<string, bool|string>>
     */
    public function entries(): array
    {
        $english = $this->flatDefaults('en');
        $arabic = $this->flatDefaults('ar');
        $overrides = LabelOverride::query()
            ->whereNull('portfolio_id')
            ->whereNull('context_type')
            ->whereNull('context_id')
            ->whereIn('locale', ['en', 'ar'])
            ->get()
            ->keyBy(fn (LabelOverride $override): string => $this->overrideIndex(
                $override->group_name,
                $override->override_key,
                $override->locale,
            ));
        $entries = [];

        $paths = array_values(array_unique([
            ...array_keys($english),
            ...array_keys($arabic),
        ]));

        foreach ($paths as $path) {
            if (! str_contains($path, '.')) {
                continue;
            }

            [$group, $key] = explode('.', $path, 2);
            $defaultEnglish = $this->defaultValue($group, $key, 'en');
            $defaultArabic = $this->defaultValue($group, $key, 'ar');

            if ($defaultEnglish === null || $defaultArabic === null) {
                continue;
            }

            $englishOverride = $overrides->get($this->overrideIndex($group, $key, 'en'));
            $arabicOverride = $overrides->get($this->overrideIndex($group, $key, 'ar'));
            $hasEnglishOverride = $englishOverride instanceof LabelOverride;
            $hasArabicOverride = $arabicOverride instanceof LabelOverride;

            $entries[] = [
                'group' => $group,
                'key' => $key,
                'english' => $hasEnglishOverride ? $englishOverride->value : $defaultEnglish,
                'arabic' => $hasArabicOverride ? $arabicOverride->value : $defaultArabic,
                'default_english' => $defaultEnglish,
                'default_arabic' => $defaultArabic,
                'customized' => $hasEnglishOverride || $hasArabicOverride,
            ];
        }

        return collect($entries)
            ->sortBy(fn (array $entry): string => "{$entry['group']}.{$entry['key']}")
            ->values()
            ->all();
    }

    public function isEditable(string $group, string $key): bool
    {
        return $this->defaultValue($group, $key, 'en') !== null
            && $this->defaultValue($group, $key, 'ar') !== null;
    }

    public function save(string $group, string $key, string $english, string $arabic): void
    {
        abort_unless($this->isEditable($group, $key), 422, trans('app.errors.unknown_wording_key'));
        $this->guardRequiredTokens($group, $key, $english, $arabic);

        DB::transaction(function () use ($group, $key, $english, $arabic): void {
            $this->persistLocale($group, $key, 'en', $english);
            $this->persistLocale($group, $key, 'ar', $arabic);
        });

        $this->forget();
    }

    public function reset(string $group, string $key): void
    {
        abort_unless($this->isEditable($group, $key), 422, trans('app.errors.unknown_wording_key'));

        LabelOverride::query()
            ->whereNull('portfolio_id')
            ->whereNull('context_type')
            ->whereNull('context_id')
            ->where('group_name', $group)
            ->where('override_key', $key)
            ->whereIn('locale', ['en', 'ar'])
            ->delete();

        $this->forget();
    }

    /**
     * Resolve an editable UI key with database overrides and token replacement.
     *
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
        $translations = $this->forLocale($locale);
        $translated = $translations['text'][$value] ?? null;

        return is_string($translated) ? $translated : $value;
    }

    /**
     * Make database overrides available to Laravel validation, auth, and pagination.
     */
    public function applyLaravelOverrides(string $locale): void
    {
        if (! Schema::hasTable('label_overrides')) {
            return;
        }

        LabelOverride::query()
            ->whereNull('portfolio_id')
            ->whereNull('context_type')
            ->whereNull('context_id')
            ->where('locale', $locale)
            ->get()
            ->each(function (LabelOverride $override) use ($locale): void {
                if (! $this->isEditable($override->group_name, $override->override_key)) {
                    return;
                }

                if (in_array($override->group_name, self::FRAMEWORK_GROUPS, true)) {
                    Lang::addLines(
                        ["{$override->group_name}.{$override->override_key}" => $override->value],
                        $locale,
                    );

                    return;
                }

                Lang::addLines(
                    ["app.{$override->group_name}.{$override->override_key}" => $override->value],
                    $locale,
                );
            });
    }

    /**
     * @return array<string, mixed>
     */
    private function defaults(string $locale): array
    {
        $translations = Lang::get('app', [], $locale);

        $defaults = is_array($translations) ? $translations : [];
        $defaults['property_docs'] = $this->documentationDefaults($locale);

        foreach (self::FRAMEWORK_GROUPS as $group) {
            $groupTranslations = Lang::get($group, [], $locale);
            $defaults[$group] = is_array($groupTranslations) ? $groupTranslations : [];
        }

        return $defaults;
    }

    /**
     * Build stable editable paths for the config-backed documentation content.
     *
     * @return array<string, mixed>
     */
    private function documentationDefaults(string $locale): array
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
                        $key = match ($collection) {
                            'workflows' => (string) $item['key'],
                            'role_guides' => (string) $item['role'],
                            'page_shortcuts' => Str::slug((string) $item['label']),
                            default => Str::slug((string) $item['title']),
                        };
                        $content = $this->documentationContent($item);
                        $localized = Lang::get("property_docs.{$collection}.{$key}", [], $locale);

                        if (is_array($localized)) {
                            $content = array_replace_recursive($content, $localized);
                        }

                        return [$key => $this->documentationContent($content)];
                    })
                    ->all();

                return [$collection => $records];
            })
            ->all();
    }

    /**
     * Exclude routes, roles, icons, modules, and tags from editable wording.
     *
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>
     */
    private function documentationContent(array $item): array
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

    private function persistLocale(
        string $group,
        string $key,
        string $locale,
        string $value,
    ): void {
        $query = LabelOverride::query()
            ->whereNull('portfolio_id')
            ->whereNull('context_type')
            ->whereNull('context_id')
            ->where('group_name', $group)
            ->where('override_key', $key)
            ->where('locale', $locale);
        $default = $this->defaultValue($group, $key, $locale);

        if ($value === $default) {
            $query->delete();

            return;
        }

        $override = $query->first() ?? new LabelOverride([
            'portfolio_id' => null,
            'group_name' => $group,
            'override_key' => $key,
            'locale' => $locale,
            'context_type' => null,
            'context_id' => null,
        ]);
        $override->value = $value;
        $override->save();
    }

    private function overrideIndex(string $group, string $key, string $locale): string
    {
        return "{$group}\0{$key}\0{$locale}";
    }

    private function defaultValue(
        string $group,
        string $key,
        string $locale,
    ): ?string {
        $defaults = $this->defaults($locale);
        $value = $group === 'text'
            ? ($defaults['text'][$key] ?? null)
            : Arr::get($defaults, "{$group}.{$key}");

        if (is_string($value)) {
            return $value;
        }

        if ($group === 'text') {
            $otherLocale = $locale === 'en' ? 'ar' : 'en';
            $otherDefaults = $this->defaults($otherLocale);

            if (is_string($otherDefaults['text'][$key] ?? null)) {
                return $key;
            }
        }

        return null;
    }

    /**
     * @return array<string, string>
     */
    private function flatDefaults(string $locale): array
    {
        return collect(Arr::dot($this->defaults($locale)))
            ->filter(fn (mixed $value): bool => is_string($value))
            ->map(fn (mixed $value): string => (string) $value)
            ->all();
    }

    private function cacheKey(string $locale): string
    {
        return "ui-translations:v10:{$locale}";
    }

    private function forget(): void
    {
        foreach (['en', 'ar'] as $locale) {
            Cache::forget($this->cacheKey($locale));
            unset($this->resolved[$locale]);
        }
    }

    private function guardRequiredTokens(
        string $group,
        string $key,
        string $english,
        string $arabic,
    ): void {
        foreach (['en' => $english, 'ar' => $arabic] as $locale => $value) {
            preg_match_all('/:[A-Za-z_]+/', $this->defaultValue($group, $key, $locale) ?? '', $defaultMatches);
            preg_match_all('/:[A-Za-z_]+/', $value, $valueMatches);
            $missing = array_diff(array_unique($defaultMatches[0]), array_unique($valueMatches[0]));

            abort_if(
                $missing !== [],
                422,
                $this->translate(
                    'errors.wording_tokens_missing',
                    ['tokens' => implode(', ', $missing)],
                    app()->getLocale(),
                    'Required placeholders are missing: :tokens',
                ),
            );
        }
    }
}
