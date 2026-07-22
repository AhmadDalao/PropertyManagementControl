<?php

namespace App\Http\Controllers;

use App\Modules\PublicSite\Presenters\PublicPagePresenter;
use Inertia\Inertia;
use Inertia\Response;

class PublicSiteController extends Controller
{
    public function __construct(
        private readonly PublicPagePresenter $pages,
    ) {}

    public function home(): Response
    {
        return Inertia::render('public/home', [
            'page' => $this->pages->homepage(),
        ]);
    }

    public function show(string $slug): Response
    {
        return Inertia::render('public/page', [
            'page' => $this->pages->bySlug($slug),
        ]);
    }
}
