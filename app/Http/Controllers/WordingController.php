<?php

namespace App\Http\Controllers;

use App\Modules\Wording\UiTranslationCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WordingController extends Controller
{
    public function index(Request $request, UiTranslationCatalog $catalog): Response
    {
        $this->requireRoles($this->actor($request), ['superadmin']);

        $entries = $catalog->entries();

        return Inertia::render('admin/wording/index', [
            'entries' => $entries,
            'groups' => collect($entries)
                ->pluck('group')
                ->unique()
                ->values(),
            'customizedCount' => collect($entries)
                ->where('customized', true)
                ->count(),
        ]);
    }

    public function update(
        Request $request,
        UiTranslationCatalog $catalog,
    ): RedirectResponse {
        $this->requireRoles($this->actor($request), ['superadmin']);

        $request->merge([
            'english' => trim((string) $request->input('english')),
            'arabic' => trim((string) $request->input('arabic')),
        ]);

        $data = $request->validate([
            'group' => ['required', 'string', 'max:100'],
            'key' => ['required', 'string', 'max:500'],
            'english' => ['required', 'string', 'max:2000'],
            'arabic' => ['required', 'string', 'max:2000'],
        ]);

        $catalog->save(
            $data['group'],
            $data['key'],
            $data['english'],
            $data['arabic'],
        );

        return back()->with('success', trans('app.wording.saved'));
    }

    public function destroy(
        Request $request,
        UiTranslationCatalog $catalog,
    ): RedirectResponse {
        $this->requireRoles($this->actor($request), ['superadmin']);

        $data = $request->validate([
            'group' => ['required', 'string', 'max:100'],
            'key' => ['required', 'string', 'max:500'],
        ]);

        $catalog->reset($data['group'], $data['key']);

        return back()->with('success', trans('app.wording.reset_complete'));
    }
}
