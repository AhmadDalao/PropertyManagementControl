<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Modules\Reports\Presenters\PropertyReportPresenter;
use App\Modules\Reports\Requests\PropertyReportRequest;
use Inertia\Inertia;
use Inertia\Response;

final class PropertyReportController extends Controller
{
    public function __construct(
        private readonly PropertyReportPresenter $presenter,
    ) {}

    public function __invoke(PropertyReportRequest $request, Asset $asset): Response
    {
        return Inertia::render(
            'admin/reports/property',
            $this->presenter->present(
                $this->actor($request),
                $asset,
                $request->filters($asset),
            ),
        );
    }
}
