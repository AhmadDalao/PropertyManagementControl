<?php

namespace App\Modules\Users\Support;

use App\Models\Portfolio;
use App\Models\User;
use Illuminate\Validation\ValidationException;

final class UserPortfolioOwnership
{
    public function claim(?Portfolio $portfolio, User $user, string $role, bool $claim = true): void
    {
        if ($role !== 'owner' || ! $portfolio || ! $claim) {
            return;
        }

        if ($portfolio->status !== 'active') {
            throw ValidationException::withMessages([
                'role' => trans('app.errors.user_portfolio_inactive'),
            ]);
        }

        if ($portfolio->owner_user_id !== null && $portfolio->owner_user_id !== $user->id) {
            throw ValidationException::withMessages([
                'role' => trans('app.errors.portfolio_owner_exists'),
            ]);
        }

        if ($portfolio->owner_user_id === null) {
            $portfolio->update(['owner_user_id' => $user->id]);
        }
    }
}
