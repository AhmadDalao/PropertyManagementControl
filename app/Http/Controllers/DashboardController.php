<?php

namespace App\Http\Controllers;

use App\Modules\Dashboard\DashboardPresenter;
use App\Modules\Dashboard\Requests\DashboardIndexRequest;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(DashboardIndexRequest $request, DashboardPresenter $dashboard): Response
    {
        return Inertia::render(
            'dashboard',
            $dashboard->forUser(
                $this->actor($request),
                $request->propertyId(),
                $request->period(),
            ),
        );
    }
}
