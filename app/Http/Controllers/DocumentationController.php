<?php

namespace App\Http\Controllers;

use App\Modules\Documentation\Presenters\DocumentationGuidePresenter;
use App\Modules\Documentation\Presenters\DocumentationIndexPresenter;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DocumentationController extends Controller
{
    public function __construct(
        private readonly DocumentationIndexPresenter $index,
        private readonly DocumentationGuidePresenter $guides,
    ) {}

    public function index(Request $request): Response
    {
        return Inertia::render(
            'admin/documentation/index',
            $this->index->present($this->actor($request)),
        );
    }

    public function show(Request $request, string $guide): Response
    {
        $payload = $this->guides->present($this->actor($request), $guide);
        abort_if($payload === null, 404);

        return Inertia::render('admin/documentation/show', $payload);
    }
}
