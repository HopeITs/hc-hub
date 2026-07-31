<?php

namespace App\Support;

use Closure;
use Illuminate\Support\Facades\Cache;

/**
 * Caching facade for the dashboard / statistics aggregates.
 *
 * WHY THIS CLASS EXISTS RATHER THAN CALLING Cache::remember() DIRECTLY
 * -------------------------------------------------------------------
 * Two project-specific hazards make raw Cache::remember() calls unsafe here:
 *
 * 1. NO TAG SUPPORT. The default cache store is `database` (config/cache.php,
 *    CACHE_STORE=database). The database and file stores do NOT implement
 *    cache tags, so the usual `Cache::tags('stats')->flush()` invalidation
 *    pattern would throw at runtime. Instead we namespace every key with a
 *    monotonically increasing version number. Bumping the version orphans all
 *    previous keys at once; they then expire naturally via their own TTL.
 *    This is O(1) invalidation that works on every store.
 *
 * 2. CACHE POISONING ACROSS LOCALE AND TENANT. Almost every aggregate in this
 *    app is locale-dependent (labels resolve through the `{attr}_{locale}`
 *    column convention) and many are city-scoped, since roles are city-scoped
 *    (HR-SYR, HR-ALP, ...). A key that omits either dimension will happily
 *    serve Arabic labels to an English user, or — far worse — serve Aleppo's
 *    numbers to a Homs HR officer. `key()` forces both dimensions into every
 *    key so that class of bug is structurally impossible rather than merely
 *    avoided by convention.
 *
 * Invalidation is driven by ContractObserver and EmployeeObserver, which call
 * flush() whenever a record that feeds these aggregates changes.
 */
final class StatisticsCache
{
    /**
     * Default lifetime for an aggregate, in seconds.
     *
     * Ten minutes is a deliberate compromise: long enough that a dashboard
     * refresh or several users landing at 09:00 all hit cache, short enough
     * that a stale figure self-corrects within one coffee break even if an
     * observer-driven flush is somehow missed.
     */
    public const TTL = 600;

    /**
     * Holds the current cache generation. Never read this directly — go
     * through version(), which seeds it on first use.
     */
    private const VERSION_KEY = 'stats:version';

    /**
     * Remember an aggregate under a fully-scoped key.
     *
     * @param  string  $name  Stable identifier for the aggregate, e.g. 'employees.by-department'.
     * @param  Closure  $callback  Produces the value on a cache miss.
     * @param  int|null  $cityId  Pass the city when the figure is city-scoped; null means org-wide.
     * @param  int|null  $ttl  Override the default lifetime.
     */
    public static function remember(string $name, Closure $callback, ?int $cityId = null, ?int $ttl = null): mixed
    {
        return Cache::remember(
            self::key($name, $cityId),
            $ttl ?? self::TTL,
            $callback,
        );
    }

    /**
     * Invalidate every cached aggregate by starting a new generation.
     *
     * Cheap enough (a single increment) to call from a model observer on every
     * save. Existing entries are not deleted — they simply become unreachable
     * and are reclaimed when their TTL lapses.
     */
    public static function flush(): void
    {
        // Cache::increment() is a no-op when the key is absent on some stores,
        // so seed it first. version() guarantees the key exists.
        self::version();

        Cache::increment(self::VERSION_KEY);
    }

    /**
     * Build the fully-qualified cache key for an aggregate.
     *
     * Shape: stats:v{version}:{locale}:city-{id|all}:{name}
     */
    private static function key(string $name, ?int $cityId): string
    {
        return sprintf(
            'stats:v%d:%s:city-%s:%s',
            self::version(),
            app()->getLocale(),
            $cityId ?? 'all',
            $name,
        );
    }

    /**
     * Current cache generation, seeded to 1 on first access.
     *
     * Stored forever: the version must outlive the aggregates it namespaces,
     * otherwise its expiry would silently resurrect a previous generation's
     * stale entries.
     */
    private static function version(): int
    {
        return (int) Cache::rememberForever(self::VERSION_KEY, fn () => 1);
    }
}
