<?php

namespace App\Modules\Wording;

use App\Modules\Wording\Actions\ManageWordingOverrides;
use App\Modules\Wording\Presenters\WordingEntryCatalog;
use App\Modules\Wording\Support\ResolvedUiTranslations;
use App\Modules\Wording\Support\TranslationDefaults;

class UiTranslationCatalog
{
    public function __construct(
        private readonly ResolvedUiTranslations $resolved,
        private readonly TranslationDefaults $defaults,
        private readonly WordingEntryCatalog $entries,
        private readonly ManageWordingOverrides $overrides,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function forLocale(string $locale): array
    {
        return $this->resolved->forLocale($locale);
    }

    /**
     * @return array<int, array<string, bool|string>>
     */
    public function entries(): array
    {
        return $this->entries->entries();
    }

    public function isEditable(string $group, string $key): bool
    {
        return $this->defaults->isEditable($group, $key);
    }

    public function save(string $group, string $key, string $english, string $arabic): void
    {
        $this->overrides->save($group, $key, $english, $arabic);
        $this->resolved->forget();
    }

    public function reset(string $group, string $key): void
    {
        $this->overrides->reset($group, $key);
        $this->resolved->forget();
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
        return $this->resolved->translate($key, $replacements, $locale, $fallback);
    }

    public function text(string $value, ?string $locale = null): string
    {
        return $this->resolved->text($value, $locale);
    }

    public function applyLaravelOverrides(string $locale): void
    {
        $this->resolved->applyLaravelOverrides($locale);
    }
}
