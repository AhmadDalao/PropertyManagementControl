<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Modules\Authentication\Actions\ResetUserPassword;
use App\Modules\Authentication\Requests\ResetPasswordRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ResetPasswordController extends Controller
{
    public function __construct(
        private readonly ResetUserPassword $passwords,
    ) {}

    public function create(Request $request, string $token): Response
    {
        return Inertia::render('auth/reset-password', [
            'email' => (string) $request->query('email', ''),
            'token' => $token,
        ]);
    }

    public function store(ResetPasswordRequest $request): RedirectResponse
    {
        $status = $this->passwords->execute($request->resetData());

        return to_route('login')->with('status', $status);
    }
}
