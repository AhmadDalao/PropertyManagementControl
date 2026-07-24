<?php

namespace App\Modules\Localization\Actions;

use App\Modules\Localization\Support\SupportedLocales;
use Illuminate\Http\Request;

final class SwitchLocale
{
    public function __construct(
        private readonly SupportedLocales $locales,
    ) {}

    public function execute(Request $request, string $locale): string
    {
        abort_unless($this->locales->contains($locale), 404);

        $request->session()->put('locale', $locale);
        $request->user()?->update(['preferred_locale' => $locale]);

        return $this->redirectTarget();
    }

    private function redirectTarget(): string
    {
        $previous = url()->previous();
        $path = parse_url($previous, PHP_URL_PATH) ?: '/';
        $queryString = parse_url($previous, PHP_URL_QUERY);
        $query = [];

        if (is_string($queryString)) {
            parse_str($queryString, $query);
        }

        unset($query['locale']);

        return $path.($query === [] ? '' : '?'.http_build_query($query));
    }
}
