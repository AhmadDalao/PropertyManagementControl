<?php

namespace App\Http\Controllers;

use App\Modules\CompanyControl\Actions\CompanyControlWorkbookExport;
use App\Modules\CompanyControl\Queries\CompanyControlIndexQuery;
use App\Modules\CompanyControl\Requests\CompanyControlIndexRequest;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class CompanyControlController extends Controller
{
    public function index(
        CompanyControlIndexRequest $request,
        CompanyControlIndexQuery $workspace,
    ): Response {
        return Inertia::render(
            'admin/company-control/index',
            $workspace->handle($this->actor($request), $request->filters()),
        );
    }

    public function export(
        CompanyControlIndexRequest $request,
        CompanyControlIndexQuery $workspace,
        CompanyControlWorkbookExport $export,
    ): BinaryFileResponse {
        return $export->download(
            $workspace->export($this->actor($request), $request->filters()),
        );
    }
}
