<?php

namespace App\Modules\Localization\Support;

use Illuminate\Http\Request;

final class LocaleResolver
{
    public function __construct(
        private readonly SupportedLocales $locales,
    ) {}

    public function resolve(Request $request): string
    {
        $sessionLocale = $request->hasSession()
            ? $request->session()->get('locale')
            : null;
        $candidate = $request->route('locale')
            ?? $request->query('locale')
            ?? $sessionLocale
            ?? $request->user()?->preferred_locale;

        return $this->locales->contains($candidate)
            ? $candidate
            : $this->locales->fallback();
    }
}
