<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Modules\Shared\Authorization\ActorAccess;
use Illuminate\Http\Request;

abstract class Controller
{
    protected function actor(Request $request): User
    {
        return app(ActorAccess::class)->from($request);
    }
}
