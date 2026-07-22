<?php

namespace App\Http\Middleware;

use App\Modules\PublicSite\Queries\PublicNavigationQuery;
use App\Modules\Wording\UiTranslationCatalog;
use App\Support\PortfolioModules;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $locale = app()->getLocale();

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'app' => [
                'name' => config('app.name'),
                'locale' => $locale,
                'direction' => $locale === 'ar' ? 'rtl' : 'ltr',
                'translations' => app(UiTranslationCatalog::class)->forLocale($locale),
            ],
            'auth' => [
                'user' => $request->user()
                    ? [
                        'id' => $request->user()?->id,
                        'name' => $request->user()?->name,
                        'email' => $request->user()?->email,
                        'phone' => $request->user()?->phone,
                        'portfolio_id' => $request->user()?->portfolio_id,
                        'preferred_locale' => $request->user()?->preferred_locale,
                        'status' => $request->user()?->status,
                        'force_password_reset' => $request->user()?->force_password_reset,
                        'last_login_at' => $request->user()?->last_login_at?->toIso8601String(),
                        'roles' => $request->user()?->getRoleNames()->values()->all(),
                        'portfolio' => $request->user()?->portfolio ? [
                            'id' => $request->user()->portfolio->id,
                            'name_en' => $request->user()->portfolio->name_en,
                            'name_ar' => $request->user()->portfolio->name_ar,
                            'code' => $request->user()->portfolio->code,
                            'module_settings' => PortfolioModules::normalize($request->user()->portfolio->module_settings),
                        ] : null,
                    ]
                    : null,
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'warning' => fn () => $request->session()->get('warning'),
                'status' => fn () => $request->session()->get('status'),
            ],
            'publicNavigation' => [
                'header' => fn () => app(PublicNavigationQuery::class)->header(),
            ],
        ];
    }
}
