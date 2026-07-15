<?php

namespace App\Http\Controllers;

use App\Modules\Dashboard\DashboardPresenter;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request, DashboardPresenter $dashboard): Response
    {
        return Inertia::render(
            'dashboard',
            $dashboard->forUser($this->actor($request)),
        );
    }
}
