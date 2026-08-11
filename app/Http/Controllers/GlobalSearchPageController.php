<?php

namespace App\Http\Controllers;

use App\Modules\Search\Queries\GlobalSearchQuery;
use App\Modules\Search\Requests\GlobalSearchRequest;
use Inertia\Inertia;
use Inertia\Response;

final class GlobalSearchPageController extends Controller
{
    public function __construct(private readonly GlobalSearchQuery $search) {}

    public function __invoke(GlobalSearchRequest $request): Response
    {
        return Inertia::render('admin/search/index', [
            'search' => $this->search->handle($this->actor($request), $request->queryText()),
        ]);
    }
}
