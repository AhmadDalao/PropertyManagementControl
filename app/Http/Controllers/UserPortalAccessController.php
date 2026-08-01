<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Modules\Users\Actions\CreatePortalAccessLink;
use App\Modules\Users\Presenters\PortalAccessPresenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class UserPortalAccessController extends Controller
{
    public function __construct(
        private readonly PortalAccessPresenter $presenter,
        private readonly CreatePortalAccessLink $links,
    ) {}

    public function show(Request $request, User $user): Response
    {
        return Inertia::render('admin/users/portal-access', [
            'portalAccess' => $this->presenter->present(
                $this->actor($request),
                $user,
                $request->string('origin')->toString(),
            ),
        ]);
    }

    public function store(Request $request, User $user): JsonResponse
    {
        return response()->json(
            $this->links->handle($this->actor($request), $user),
        );
    }
}
