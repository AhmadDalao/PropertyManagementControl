<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\InteractsWithPortfolioScope;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;

abstract class Controller
{
    use AuthorizesRequests;
    use InteractsWithPortfolioScope;
    use ValidatesRequests;
}
