<?php

namespace App\Modules\Localization\Support;

final class SupportedLocales
{
    /** @var array<int, string> */
    public const ALL = ['en', 'ar'];

    public function contains(mixed $locale): bool
    {
        return is_string($locale) && in_array($locale, self::ALL, true);
    }

    public function fallback(): string
    {
        $locale = config('app.locale', 'en');

        return $this->contains($locale) ? $locale : 'en';
    }
}
