<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Modules\Users\Actions\SyncManagerPropertyAssignments;
use App\Modules\Users\Queries\ManagerPropertyAssignmentQuery;
use App\Modules\Users\Requests\UpdateManagerPropertyAssignmentsRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class ManagerPropertyAssignmentController extends Controller
{
    public function edit(
        Request $request,
        User $user,
        ManagerPropertyAssignmentQuery $assignments,
    ): Response {
        return Inertia::render('admin/users/property-assignments', [
            'assignmentPage' => $assignments->get($user, $this->actor($request)),
        ]);
    }

    public function update(
        UpdateManagerPropertyAssignmentsRequest $request,
        User $user,
        SyncManagerPropertyAssignments $assignments,
    ): RedirectResponse {
        $assignments->handle(
            $this->actor($request),
            $user,
            $request->validated('asset_ids'),
        );

        return to_route('users.show', $user)
            ->with('success', trans('app.messages.manager_assignments_updated', [
                'name' => $user->name,
            ]));
    }
}
