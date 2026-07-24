<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Modules\Authentication\Actions\SendPasswordResetLink;
use App\Modules\Authentication\Requests\ForgotPasswordRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ForgotPasswordController extends Controller
{
    public function __construct(
        private readonly SendPasswordResetLink $passwordLinks,
    ) {}

    public function create(Request $request): Response
    {
        return Inertia::render('auth/forgot-password', [
            'status' => $request->session()->get('status'),
        ]);
    }

    public function store(ForgotPasswordRequest $request): RedirectResponse
    {
        return back()->with(
            'status',
            $this->passwordLinks->execute($request->email()),
        );
    }
}
