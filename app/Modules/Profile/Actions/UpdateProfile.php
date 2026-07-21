<?php

namespace App\Modules\Profile\Actions;

use App\Models\User;

class UpdateProfile
{
    /** @param array{name:string, phone?:string|null, preferred_locale:string} $data */
    public function execute(User $user, array $data): void
    {
        $user->update([
            'name' => $data['name'],
            'phone' => $data['phone'] ?? null,
            'preferred_locale' => $data['preferred_locale'],
        ]);
    }
}
