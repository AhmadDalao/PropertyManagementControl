<?php

namespace App\Modules\Wording\Presenters;

use App\Models\LabelOverride;
use App\Modules\Wording\Queries\GlobalWordingOverrideQuery;
use App\Modules\Wording\Support\TranslationDefaults;

class WordingEntryCatalog
{
    public function __construct(
        private readonly TranslationDefaults $defaults,
        private readonly GlobalWordingOverrideQuery $overrides,
    ) {}

    /**
     * @return array<int, array<string, bool|string>>
     */
    public function entries(): array
    {
        $english = $this->defaults->flat('en');
        $arabic = $this->defaults->flat('ar');
        $overrides = $this->overrides->bilingual()
            ->keyBy(fn (LabelOverride $override): string => $this->overrides->index(
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
            $defaultEnglish = $this->defaults->value($group, $key, 'en');
            $defaultArabic = $this->defaults->value($group, $key, 'ar');

            if ($defaultEnglish === null || $defaultArabic === null) {
                continue;
            }

            $englishOverride = $overrides->get($this->overrides->index($group, $key, 'en'));
            $arabicOverride = $overrides->get($this->overrides->index($group, $key, 'ar'));
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
}
