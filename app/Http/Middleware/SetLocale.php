<?php

namespace App\Http\Middleware;

use App\Modules\Localization\Actions\ApplyRequestLocale;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function __construct(
        private readonly ApplyRequestLocale $locales,
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $this->locales->execute($request);

        return $next($request);
    }
}
