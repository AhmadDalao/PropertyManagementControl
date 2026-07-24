<?php

namespace App\Http\Controllers;

use App\Modules\LeaseRenewals\Queries\LeaseRenewalIndexQuery;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class LeaseRenewalController extends Controller
{
    public function __construct(private readonly LeaseRenewalIndexQuery $renewals) {}

    public function index(Request $request): Response
    {
        return Inertia::render(
            'admin/lease-renewals/index',
            $this->renewals->handle($request, $this->actor($request)),
        );
    }
}
