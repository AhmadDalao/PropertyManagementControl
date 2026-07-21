<?php

namespace App\Http\Controllers;

use App\Modules\Search\Queries\GlobalSearchQuery;
use App\Modules\Search\Requests\GlobalSearchRequest;
use Illuminate\Http\JsonResponse;

class GlobalSearchController extends Controller
{
    public function __construct(private readonly GlobalSearchQuery $search) {}

    public function __invoke(GlobalSearchRequest $request): JsonResponse
    {
        return response()->json(
            $this->search->handle(
                $this->actor($request),
                $request->queryText(),
            ),
        );
    }
}
