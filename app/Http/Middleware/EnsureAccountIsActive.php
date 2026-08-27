<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/**
 * Rejects an authenticated request whose account is no longer active.
 *
 * AccessRevoker deletes database sessions when an employee is deactivated, but
 * that only covers the database session driver and only covers deactivations
 * that go through the application. This middleware is the unconditional check:
 * every authenticated request re-reads the employee's active flag, so a
 * deactivation applied directly in the database — or on a cache/file session
 * driver — still takes effect on the next request.
 *
 * Runs on the whole web group rather than per route, because "which routes are
 * sensitive" is exactly the judgement call that goes stale.
 */
class EnsureAccountIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        // Read straight from the employees table rather than through
        // $user->profile. If anything earlier in the request has already
        // loaded that relation, the cached copy can be stale — and "stale" here
        // means an account that was revoked mid-request stays usable. One
        // indexed lookup per request is the right price for that guarantee.
        //
        // A user with no linked employee record is a configuration error, not
        // a valid session; treat it the same as a deactivated one.
        $isActive = $user->employee_id !== null && DB::table('employees')
            ->where('id', $user->employee_id)
            ->value('is_active');

        if ($isActive) {
            return $next($request);
        }

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->hasHeader('X-Livewire')) {
            // Livewire follows this rather than rendering the redirect body
            // into the current page.
            abort(Response::HTTP_UNAUTHORIZED, __('auth.account_inactive'));
        }

        return redirect()
            ->route('login')
            ->withErrors(['email' => __('auth.account_inactive')]);
    }
}
