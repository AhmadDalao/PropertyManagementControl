<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DocumentationController extends Controller
{
    public function index(Request $request): Response
    {
        $actor = $this->actor($request);

        return Inertia::render('admin/documentation/index', [
            'audience' => $actor->getRoleNames()->first() ?? 'user',
            'guides' => config('property_docs.guides', []),
            'quickStarts' => config('property_docs.quick_starts', []),
            'roleGuides' => config('property_docs.role_guides', []),
        ]);
    }
}
