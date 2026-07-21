<?php

namespace App\Http\Controllers;

use App\Modules\Audit\Actions\AuditWorkbookExport;
use App\Modules\Audit\Queries\AuditLogQuery;
use App\Modules\Audit\Requests\AuditIndexRequest;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AuditLogController extends Controller
{
    public function __construct(
        private readonly AuditLogQuery $auditLogs,
        private readonly AuditWorkbookExport $workbook,
    ) {}

    public function index(AuditIndexRequest $request): Response
    {
        return Inertia::render(
            'admin/audit/index',
            $this->auditLogs->handle($request, $this->actor($request)),
        );
    }

    public function export(AuditIndexRequest $request): BinaryFileResponse
    {
        return $this->workbook->download(
            $this->actor($request),
            $request->filters(),
        );
    }
}
