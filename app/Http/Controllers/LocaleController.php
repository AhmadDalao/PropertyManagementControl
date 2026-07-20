<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LocaleController extends Controller
{
    public function update(Request $request, string $locale): RedirectResponse
    {
        abort_unless(in_array($locale, ['en', 'ar'], true), 404);

        $request->session()->put('locale', $locale);

        if ($request->user()) {
            $request->user()->update(['preferred_locale' => $locale]);
        }

        return redirect()->to($this->redirectTarget());
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
