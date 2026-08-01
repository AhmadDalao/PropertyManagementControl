<?php

namespace App\Modules\Users\Actions;

use App\Models\User;
use App\Modules\Users\Support\UserAccess;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

final class CreatePortalAccessLink
{
    public function __construct(private readonly UserAccess $access) {}

    /** @return array{url:string,expires_at:string,expires_in_minutes:int} */
    public function handle(User $actor, User $target): array
    {
        $this->access->ensureCanManage($actor, $target);

        if ($target->status !== 'active') {
            throw ValidationException::withMessages([
                'account' => trans('app.users.portal_access_inactive_error'),
            ]);
        }

        $broker = Password::broker();
        $token = $broker->createToken($target);
        $minutes = max(1, (int) config('auth.passwords.users.expire', 60));
        $expiresAt = now()->addMinutes($minutes);

        activity('users')
            ->causedBy($actor)
            ->performedOn($target)
            ->event('portal_access_link_created')
            ->withProperties([
                'delivery' => 'manual_secure_link',
                'expires_at' => $expiresAt->toIso8601String(),
            ])
            ->log('portal_access_link_created');

        return [
            'url' => route('password.reset', [
                'token' => $token,
                'email' => $target->getEmailForPasswordReset(),
                'locale' => $target->preferred_locale,
            ]),
            'expires_at' => $expiresAt->toIso8601String(),
            'expires_in_minutes' => $minutes,
        ];
    }
}
