<?php

namespace App\Http\Controllers;

use App\Modules\Assets\Presenters\PropertyExplorerPresenter;
use App\Modules\Assets\Requests\PropertyExplorerRequest;
use Inertia\Inertia;
use Inertia\Response;

final class PropertyExplorerController extends Controller
{
    public function __construct(
        private readonly PropertyExplorerPresenter $presenter,
    ) {}

    public function __invoke(PropertyExplorerRequest $request): Response
    {
        return Inertia::render('admin/assets/explorer', [
            'explorer' => $this->presenter->present(
                $this->actor($request),
                $request->filters(),
            ),
        ]);
    }
}
