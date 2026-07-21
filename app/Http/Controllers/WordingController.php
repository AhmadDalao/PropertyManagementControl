<?php

namespace App\Http\Controllers;

use App\Modules\Wording\Presenters\WordingPagePresenter;
use App\Modules\Wording\Requests\ResetWordingRequest;
use App\Modules\Wording\Requests\SaveWordingRequest;
use App\Modules\Wording\Requests\WordingIndexRequest;
use App\Modules\Wording\UiTranslationCatalog;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class WordingController extends Controller
{
    public function __construct(
        private readonly WordingPagePresenter $presenter,
        private readonly UiTranslationCatalog $catalog,
    ) {}

    public function index(WordingIndexRequest $request): Response
    {
        return Inertia::render(
            'admin/wording/index',
            $this->presenter->present($request),
        );
    }

    public function update(SaveWordingRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $this->catalog->save(
            $data['group'],
            $data['key'],
            $data['english'],
            $data['arabic'],
        );

        return back()->with('success', trans('app.wording.saved'));
    }

    public function destroy(ResetWordingRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $this->catalog->reset($data['group'], $data['key']);

        return back()->with('success', trans('app.wording.reset_complete'));
    }
}
