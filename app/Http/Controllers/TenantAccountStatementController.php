<?php

namespace App\Http\Controllers;

use App\Models\TenantProfile;
use App\Modules\Tenants\Actions\TenantStatementPdfExport;
use App\Modules\Tenants\Actions\TenantStatementWordExport;
use App\Modules\Tenants\Actions\TenantStatementWorkbookExport;
use App\Modules\Tenants\Queries\TenantAccountStatementQuery;
use App\Modules\Tenants\Requests\TenantAccountStatementRequest;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class TenantAccountStatementController extends Controller
{
    public function __construct(
        private readonly TenantAccountStatementQuery $statements,
        private readonly TenantStatementPdfExport $pdfExport,
        private readonly TenantStatementWordExport $wordExport,
        private readonly TenantStatementWorkbookExport $workbookExport,
    ) {}

    public function show(TenantAccountStatementRequest $request, TenantProfile $tenant): Response
    {
        return Inertia::render(
            'admin/tenants/statement',
            $this->statement($request, $tenant),
        );
    }

    public function pdf(TenantAccountStatementRequest $request, TenantProfile $tenant): StreamedResponse
    {
        return $this->pdfExport->download($this->statement($request, $tenant));
    }

    public function word(TenantAccountStatementRequest $request, TenantProfile $tenant): StreamedResponse
    {
        return $this->wordExport->download($this->statement($request, $tenant));
    }

    public function workbook(
        TenantAccountStatementRequest $request,
        TenantProfile $tenant,
    ): BinaryFileResponse {
        return $this->workbookExport->download($this->statement($request, $tenant));
    }

    /** @return array<string, mixed> */
    private function statement(
        TenantAccountStatementRequest $request,
        TenantProfile $tenant,
    ): array {
        return $this->statements->handle(
            $this->actor($request),
            $tenant,
            $request->filters(),
        );
    }
}
