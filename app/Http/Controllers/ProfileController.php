<?php

namespace App\Http\Controllers;

use App\Modules\Profile\Actions\UpdateProfile;
use App\Modules\Profile\Actions\UpdateProfilePassword;
use App\Modules\Profile\Presenters\ProfilePresenter;
use App\Modules\Profile\Requests\UpdateProfilePasswordRequest;
use App\Modules\Profile\Requests\UpdateProfileRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function __construct(
        private readonly ProfilePresenter $profiles,
        private readonly UpdateProfile $updateProfile,
        private readonly UpdateProfilePassword $updatePassword,
    ) {}

    public function index(Request $request): Response
    {
        return Inertia::render('admin/profile/index', [
            'profile' => $this->profiles->present($this->actor($request)),
        ]);
    }

    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $data = $request->profileData();
        $this->updateProfile->execute($this->actor($request), $data);
        $request->session()->put('locale', $data['preferred_locale']);
        app()->setLocale($data['preferred_locale']);

        return to_route('profile.index')->with('success', trans('app.messages.profile_updated'));
    }

    public function updatePassword(UpdateProfilePasswordRequest $request): RedirectResponse
    {
        $this->updatePassword->execute(
            $this->actor($request),
            (string) $request->validated('password'),
        );

        return to_route('profile.index')->with('success', trans('app.messages.password_updated'));
    }
}
