<?php

namespace App\Http\Middleware;

use App\Modules\Wording\UiTranslationCatalog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Supported UI locales.
     */
    private array $supportedLocales = ['en', 'ar'];

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $sessionLocale = $request->hasSession() ? $request->session()->get('locale') : null;
        $locale = $request->route('locale')
            ?? $request->query('locale')
            ?? $sessionLocale
            ?? $request->user()?->preferred_locale
            ?? config('app.locale', 'en');

        if (! in_array($locale, $this->supportedLocales, true)) {
            $locale = config('app.locale', 'en');
        }

        app()->setLocale($locale);
        app(UiTranslationCatalog::class)->applyLaravelOverrides($locale);

        if ($request->hasSession()) {
            $request->session()->put('locale', $locale);
        }

        return $next($request);
    }
}
