<?php

namespace App\Http\Controllers;

use App\Models\NavigationItem;
use App\Modules\Cms\Actions\ManageNavigationItems;
use App\Modules\Cms\Presenters\NavigationFormPresenter;
use App\Modules\Cms\Requests\SaveNavigationItemRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NavigationItemController extends Controller
{
    public function __construct(
        private readonly NavigationFormPresenter $forms,
        private readonly ManageNavigationItems $navigation,
    ) {}

    public function create(Request $request): Response
    {
        return Inertia::render('admin/resource-form', [
            'formPage' => $this->forms->present($this->actor($request)),
        ]);
    }

    public function edit(Request $request, NavigationItem $navigationItem): Response
    {
        return Inertia::render('admin/resource-form', [
            'formPage' => $this->forms->present($this->actor($request), $navigationItem),
        ]);
    }

    public function store(SaveNavigationItemRequest $request): RedirectResponse
    {
        $this->navigation->create($this->actor($request), $request->validated());

        return to_route('cms.index')->with('success', trans('app.messages.navigation_created'));
    }

    public function update(SaveNavigationItemRequest $request, NavigationItem $navigationItem): RedirectResponse
    {
        $this->navigation->update($this->actor($request), $navigationItem, $request->validated());

        return to_route('cms.index')->with('success', trans('app.messages.navigation_updated'));
    }

    public function destroy(Request $request, NavigationItem $navigationItem): RedirectResponse
    {
        $this->navigation->delete($this->actor($request), $navigationItem);

        return to_route('cms.index')->with('success', trans('app.messages.navigation_deleted'));
    }
}
