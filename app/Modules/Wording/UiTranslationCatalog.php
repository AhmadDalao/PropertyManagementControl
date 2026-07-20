<?php

namespace App\Modules\Wording;

use App\Models\LabelOverride;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;

class UiTranslationCatalog
{
    /**
     * Return the active UI dictionary with database overrides applied.
     *
     * @return array<string, mixed>
     */
    public function forLocale(string $locale): array
    {
        $translations = $this->defaults($locale);

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

                if (! isset($translations[$override->group_name]) || ! is_array($translations[$override->group_name])) {
                    $translations[$override->group_name] = [];
                }

                $translations[$override->group_name][$override->override_key] = $override->value;
            });

        return $translations;
    }

    /**
     * Build the bilingual catalog used by the wording workspace.
     *
     * @return array<int, array<string, bool|string>>
     */
    public function entries(): array
    {
        $english = $this->defaults('en');
        $arabic = $this->defaults('ar');
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

        $groups = array_values(array_unique([
            ...array_keys($english),
            ...array_keys($arabic),
        ]));

        foreach ($groups as $group) {
            $englishItems = is_array($english[$group] ?? null) ? $english[$group] : [];
            $arabicItems = is_array($arabic[$group] ?? null) ? $arabic[$group] : [];
            $keys = array_values(array_unique([
                ...array_keys($englishItems),
                ...array_keys($arabicItems),
            ]));

            if ($keys === []) {
                continue;
            }

            foreach ($keys as $key) {
                $defaultEnglish = $this->defaultValue($group, $key, 'en');
                $defaultArabic = $this->defaultValue($group, $key, 'ar');

                if ($defaultEnglish === null || $defaultArabic === null) {
                    continue;
                }

                $englishOverride = $overrides->get($this->overrideIndex($group, $key, 'en'));
                $arabicOverride = $overrides->get($this->overrideIndex($group, $key, 'ar'));

                $entries[] = [
                    'group' => $group,
                    'key' => $key,
                    'english' => $englishOverride?->value ?? $defaultEnglish,
                    'arabic' => $arabicOverride?->value ?? $defaultArabic,
                    'default_english' => $defaultEnglish,
                    'default_arabic' => $defaultArabic,
                    'customized' => $englishOverride !== null || $arabicOverride !== null,
                ];
            }
        }

        return $entries;
    }

    public function isEditable(string $group, string $key): bool
    {
        return $this->defaultValue($group, $key, 'en') !== null
            && $this->defaultValue($group, $key, 'ar') !== null;
    }

    public function save(string $group, string $key, string $english, string $arabic): void
    {
        abort_unless($this->isEditable($group, $key), 422, 'Unknown wording key.');

        DB::transaction(function () use ($group, $key, $english, $arabic): void {
            $this->persistLocale($group, $key, 'en', $english);
            $this->persistLocale($group, $key, 'ar', $arabic);
        });
    }

    public function reset(string $group, string $key): void
    {
        abort_unless($this->isEditable($group, $key), 422, 'Unknown wording key.');

        LabelOverride::query()
            ->whereNull('portfolio_id')
            ->whereNull('context_type')
            ->whereNull('context_id')
            ->where('group_name', $group)
            ->where('override_key', $key)
            ->whereIn('locale', ['en', 'ar'])
            ->delete();
    }

    /**
     * @return array<string, mixed>
     */
    private function defaults(string $locale): array
    {
        $translations = Lang::get('app', [], $locale);

        return is_array($translations) ? $translations : [];
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
        $value = $defaults[$group][$key] ?? null;

        if (is_string($value)) {
            return $value;
        }

        if ($group === 'text') {
            $otherLocale = $locale === 'en' ? 'ar' : 'en';
            $otherDefaults = $this->defaults($otherLocale);

            if (is_string($otherDefaults[$group][$key] ?? null)) {
                return $key;
            }
        }

        return null;
    }
}
