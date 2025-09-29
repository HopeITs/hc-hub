<?php

namespace App\Http\Middleware;

use Closure;

class RequireActiveRole
{
    public function handle($request, Closure $next, ...$roles)
    {
        if (!auth()->check() || !auth()->user()->activeRoleIs(...$roles)) {
            abort(403);
        }
        return $next($request);
    }
}
