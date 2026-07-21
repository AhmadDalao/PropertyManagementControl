<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Modules\Users\Actions\ManageUsers;
use App\Modules\Users\Presenters\UserDetailPresenter;
use App\Modules\Users\Presenters\UserFormPresenter;
use App\Modules\Users\Queries\UserIndexQuery;
use App\Modules\Users\Requests\StoreUserRequest;
use App\Modules\Users\Requests\UpdateUserRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function __construct(
        private readonly UserIndexQuery $indexQuery,
        private readonly UserFormPresenter $formPresenter,
        private readonly UserDetailPresenter $detailPresenter,
        private readonly ManageUsers $users,
    ) {}

    public function index(Request $request): Response
    {
        return Inertia::render(
            'admin/users/index',
            $this->indexQuery->handle($request, $this->actor($request)),
        );
    }

    public function create(Request $request): Response
    {
        return Inertia::render('admin/resource-form', [
            'formPage' => $this->formPresenter->present(
                $this->actor($request),
                defaults: $request->only('portfolio_id'),
            ),
        ]);
    }

    public function show(Request $request, User $user): Response
    {
        return Inertia::render('admin/resource-show', [
            'detailPage' => $this->detailPresenter->present($user, $this->actor($request)),
        ]);
    }

    public function edit(Request $request, User $user): Response
    {
        return Inertia::render('admin/resource-form', [
            'formPage' => $this->formPresenter->present($this->actor($request), $user),
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $user = $this->users->create($this->actor($request), $request->validated());

        return to_route('users.show', $user)
            ->with('success', trans('app.messages.user_created', ['name' => $user->name]));
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $this->users->update($this->actor($request), $user, $request->validated());

        return to_route('users.show', $user)
            ->with('success', trans('app.messages.user_updated', ['name' => $user->name]));
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        $blockingReason = $this->users->suspend($this->actor($request), $user);

        if ($blockingReason !== null) {
            return back()->with('error', $blockingReason);
        }

        return to_route('users.index')
            ->with('success', trans('app.messages.user_archived', ['name' => $user->name]));
    }
}
