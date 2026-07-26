<?php

namespace App\Http\Controllers;

use App\Modules\PortfolioControl\Queries\PortfolioControlIndexQuery;
use App\Modules\PortfolioControl\Requests\PortfolioControlIndexRequest;
use Inertia\Inertia;
use Inertia\Response;

final class PortfolioControlController extends Controller
{
    public function __invoke(
        PortfolioControlIndexRequest $request,
        PortfolioControlIndexQuery $workspace,
    ): Response {
        return Inertia::render(
            'admin/portfolio-control/index',
            $workspace->handle($this->actor($request), $request->filters()),
        );
    }
}
