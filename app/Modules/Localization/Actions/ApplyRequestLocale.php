<?php

namespace App\Modules\Localization\Actions;

use App\Modules\Localization\Support\LocaleResolver;
use App\Modules\Wording\UiTranslationCatalog;
use Illuminate\Http\Request;

final class ApplyRequestLocale
{
    public function __construct(
        private readonly LocaleResolver $resolver,
        private readonly UiTranslationCatalog $translations,
    ) {}

    public function execute(Request $request): string
    {
        $locale = $this->resolver->resolve($request);

        app()->setLocale($locale);
        $this->translations->applyLaravelOverrides($locale);

        if ($request->hasSession()) {
            $request->session()->put('locale', $locale);
        }

        return $locale;
    }
}
