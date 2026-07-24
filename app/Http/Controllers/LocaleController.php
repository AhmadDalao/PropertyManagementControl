<?php

namespace App\Http\Controllers;

use App\Modules\Localization\Actions\SwitchLocale;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LocaleController extends Controller
{
    public function __construct(
        private readonly SwitchLocale $locales,
    ) {}

    public function update(Request $request, string $locale): RedirectResponse
    {
        return redirect()->to($this->locales->execute($request, $locale));
    }
}
