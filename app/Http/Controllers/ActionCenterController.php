<?php

namespace App\Http\Controllers;

use App\Modules\ActionCenter\Actions\ActionCenterWorkbookExport;
use App\Modules\ActionCenter\Queries\ActionCenterIndexQuery;
use App\Modules\ActionCenter\Requests\ActionCenterIndexRequest;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class ActionCenterController extends Controller
{
    public function index(
        ActionCenterIndexRequest $request,
        ActionCenterIndexQuery $actions,
    ): Response {
        return Inertia::render(
            'admin/action-center/index',
            $actions->handle($this->actor($request), $request->filters()),
        );
    }

    public function export(
        ActionCenterIndexRequest $request,
        ActionCenterWorkbookExport $workbook,
    ): BinaryFileResponse {
        return $workbook->download(
            $this->actor($request),
            $request->filters(),
        );
    }
}
