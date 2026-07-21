<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function index(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();

        $user->loadMissing(['portfolio', 'tenantProfile']);

        return Inertia::render('admin/profile/index', [
            'profile' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'preferred_locale' => $user->preferred_locale,
                'status' => $user->status,
                'force_password_reset' => $user->force_password_reset,
                'last_login_at' => $user->last_login_at?->toIso8601String(),
                'roles' => $user->getRoleNames()->values()->all(),
                'portfolio' => $user->portfolio ? [
                    'id' => $user->portfolio->id,
                    'name_en' => $user->portfolio->name_en,
                    'name_ar' => $user->portfolio->name_ar,
                    'code' => $user->portfolio->code,
                    'status' => $user->portfolio->status,
                ] : null,
                'tenant_profile' => $user->tenantProfile ? [
                    'id' => $user->tenantProfile->id,
                    'profile_type' => $user->tenantProfile->profile_type,
                    'status' => $user->tenantProfile->status,
                ] : null,
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'preferred_locale' => ['required', 'in:en,ar'],
        ]);

        $user->update($data);
        $request->session()->put('locale', $data['preferred_locale']);

        return to_route('profile.index')->with('success', trans('app.messages.profile_updated'));
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $data = $request->validate([
            'current_password' => [$user->force_password_reset ? 'nullable' : 'required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed', PasswordRule::defaults()],
        ]);

        if (! $user->force_password_reset && ! Hash::check($data['current_password'], $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => trans('validation.current_password'),
            ]);
        }

        $user->update([
            'password' => Hash::make($data['password']),
            'force_password_reset' => false,
        ]);

        return to_route('profile.index')->with('success', trans('app.messages.password_updated'));
    }
}
