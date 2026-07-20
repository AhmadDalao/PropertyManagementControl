<?php

namespace App\Support;

use App\Modules\Wording\UiTranslationCatalog;

final readonly class LocalizedCopy
{
    /**
     * @param  array<string, scalar|null>  $replacements
     */
    public function __construct(
        public string $key,
        public string $fallback = '',
        public array $replacements = [],
    ) {}

    public function resolve(?string $locale = null): string
    {
        return app(UiTranslationCatalog::class)->translate(
            $this->key,
            $this->replacements,
            $locale,
            $this->fallback,
        );
    }

    /**
     * @return array{key:string,fallback:string,replacements:array<string, scalar|null>}
     */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'fallback' => $this->fallback,
            'replacements' => $this->replacements,
        ];
    }
}
