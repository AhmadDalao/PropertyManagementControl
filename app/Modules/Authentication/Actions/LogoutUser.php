<?php

namespace App\Modules\Authentication\Actions;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

final class LogoutUser
{
    public function execute(Request $request): void
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }
}
