<?php

namespace App\Modules\Authentication\Presenters;

use App\Models\User;
use App\Modules\Portfolios\Support\PortfolioModules;

final class AuthenticatedUserPresenter
{
    /** @return array<string, mixed>|null */
    public function present(?User $user): ?array
    {
        if (! $user) {
            return null;
        }

        $user->loadMissing('portfolio');

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'portfolio_id' => $user->portfolio_id,
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
                'module_settings' => PortfolioModules::normalize(
                    $user->portfolio->module_settings,
                ),
            ] : null,
        ];
    }
}
