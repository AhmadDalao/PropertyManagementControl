<?php

namespace App\Http\Controllers;

use App\Modules\RentCollection\Queries\RentCollectionIndexQuery;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class RentCollectionController extends Controller
{
    public function __construct(private readonly RentCollectionIndexQuery $collections) {}

    public function index(Request $request): Response
    {
        return Inertia::render(
            'admin/rent-collection/index',
            $this->collections->handle($request, $this->actor($request)),
        );
    }
}
