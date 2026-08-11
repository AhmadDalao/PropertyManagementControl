<?php

namespace App\Http\Controllers;

use App\Modules\TenantPortal\Queries\TenantDocumentPortalQuery;
use App\Modules\TenantPortal\Queries\TenantLeasePortalQuery;
use App\Modules\TenantPortal\Queries\TenantPaymentPortalQuery;
use App\Modules\TenantPortal\Requests\TenantPortalRequest;
use Inertia\Inertia;
use Inertia\Response;

final class TenantPortalController extends Controller
{
    public function lease(TenantPortalRequest $request, TenantLeasePortalQuery $query): Response
    {
        return Inertia::render('admin/tenant-portal/lease', $query->handle(
            $this->actor($request),
            $request->leaseId(),
        ));
    }

    public function payments(TenantPortalRequest $request, TenantPaymentPortalQuery $query): Response
    {
        return Inertia::render('admin/tenant-portal/payments', $query->handle(
            $this->actor($request),
            $request->filters(),
        ));
    }

    public function documents(TenantPortalRequest $request, TenantDocumentPortalQuery $query): Response
    {
        return Inertia::render('admin/tenant-portal/documents', $query->handle(
            $this->actor($request),
            $request->filters(),
        ));
    }
}
