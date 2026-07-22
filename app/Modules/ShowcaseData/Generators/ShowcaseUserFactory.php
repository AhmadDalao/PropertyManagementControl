<?php

namespace App\Modules\ShowcaseData\Generators;

use App\Models\Portfolio;
use App\Models\ShowcaseDataset;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ShowcaseUserFactory
{
    public function make(
        ShowcaseDataset $dataset,
        Portfolio $portfolio,
        string $email,
        string $name,
        string $role,
        int $phoneSuffix,
    ): User {
        $user = User::query()->updateOrCreate(
            ['email' => $email],
            [
                'showcase_dataset_id' => $dataset->id,
                'portfolio_id' => $portfolio->id,
                'name' => $name,
                'phone' => '+96655'.str_pad((string) $phoneSuffix, 7, '0', STR_PAD_LEFT),
                'preferred_locale' => $phoneSuffix % 2 === 0 ? 'ar' : 'en',
                'status' => 'inactive',
                'force_password_reset' => false,
                'email_verified_at' => null,
                'password' => Hash::make(Str::password(40)),
            ],
        );
        $user->syncRoles([$role]);

        return $user;
    }
}
