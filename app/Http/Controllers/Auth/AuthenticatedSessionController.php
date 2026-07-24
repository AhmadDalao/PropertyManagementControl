<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Modules\Authentication\Actions\AuthenticateUser;
use App\Modules\Authentication\Actions\LogoutUser;
use App\Modules\Authentication\Requests\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    public function __construct(
        private readonly AuthenticateUser $authenticate,
        private readonly LogoutUser $logout,
    ) {}

    public function create(Request $request): Response|RedirectResponse
    {
        if ($request->user()) {
            return to_route('dashboard');
        }

        return Inertia::render('auth/login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $this->authenticate->execute($request);
        $request->session()->regenerate();

        return to_route('dashboard')->with('success', trans('app.messages.welcome_back'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        $this->logout->execute($request);

        return to_route('home')->with('success', trans('app.messages.logged_out'));
    }
}
