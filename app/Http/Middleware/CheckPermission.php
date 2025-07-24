<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $permission
     * @return \Symfony\Component\HttpFoundation\Response
     */
    // public function handle(Request $request, Closure $next, $permission): Response
    // {
    //     if (!auth()->check()) {
    //         return redirect()->route('login')->withErrors(['message' => 'Please login first.']);
    //     }

    //     $user = auth()->user();

    //     if (!$user->hasPermission($permission)) {
    //         return redirect()->back()->with('error', 'You do not have permission to access this page.');
    //     }

    //     return $next($request);
    // }
}
