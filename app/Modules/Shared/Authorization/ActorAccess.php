<?php

namespace App\Modules\Shared\Authorization;

use App\Models\User;
use Illuminate\Http\Request;

final class ActorAccess
{
    public function from(Request $request): User
    {
        $actor = $request->user();

        abort_unless($actor instanceof User, 401);

        return $actor;
    }
}
