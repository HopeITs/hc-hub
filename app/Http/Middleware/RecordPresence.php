<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Modules\Core\Models\User;
use Symfony\Component\HttpFoundation\Response;

/**
 * Stamps the signed-in account as seen (users.last_seen_at), for who is
 * online on the HR clock card (ClockCard: seen in the last five minutes).
 * Ported from hc-hr.
 *
 * At most once a minute per account: the row the guard already loaded
 * answers most requests without a query, and the same condition sits in the
 * WHERE so two requests racing each other write once. Written through the
 * query builder (no model events, no activity, no updated_at). A failed write
 * never fails the request it rode on.
 */
final class RecordPresence
{
    public const int WriteEverySeconds = 60;

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user instanceof User) {
            rescue(fn () => $this->touch($user), report: false);
        }

        return $next($request);
    }

    private function touch(User $user): void
    {
        $now = Carbon::now();
        $threshold = $now->copy()->subSeconds(self::WriteEverySeconds);

        $seen = $user->getAttributes()['last_seen_at'] ?? null;

        if ($seen !== null && Carbon::parse($seen)->greaterThan($threshold)) {
            return;
        }

        User::query()->whereKey($user->getKey())->toBase()
            ->where(fn ($query) => $query->whereNull('last_seen_at')->orWhere('last_seen_at', '<=', $threshold))
            ->update(['last_seen_at' => $now]);

        $user->setRawAttributes([...$user->getAttributes(), 'last_seen_at' => $now->toDateTimeString()], true);
    }
}
