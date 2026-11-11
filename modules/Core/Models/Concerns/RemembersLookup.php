<?php

declare(strict_types=1);

namespace Modules\Core\Models\Concerns;

use Closure;
use Illuminate\Database\Eloquent\Model;

/**
 * Keeps a small whole-table lookup (every city's name by id, every center's
 * options...) in memory, so pickers and labels cost one query per app
 * instance instead of one per row or per request component.
 *
 * Same contract as App\Support\Concerns\RemembersTableLookup: the lookup belongs
 * to the running application instance and is reloaded after a minute, so a
 * long-lived worker never serves old data for long. Saving or deleting a row
 * through its model forgets it at once.
 *
 * @phpstan-require-extends Model
 */
trait RemembersLookup
{
    /**
     * @var array<string, array{app: int, loaded_at: float, value: mixed}>
     */
    private static array $lookups = [];

    public static function bootRemembersLookup(): void
    {
        static::saved(fn () => static::forgetLookups());
        static::deleted(fn () => static::forgetLookups());
    }

    /**
     * Drop every remembered lookup so the next read reloads it.
     */
    public static function forgetLookups(): void
    {
        self::$lookups = [];
    }

    /**
     * The remembered value for $key, loaded by $load when missing or stale.
     *
     * @template TValue
     *
     * @param  Closure(): TValue  $load
     * @return TValue
     */
    protected static function rememberLookup(string $key, Closure $load): mixed
    {
        $app = spl_object_id(app());
        $entry = self::$lookups[$key] ?? null;

        if ($entry === null || $entry['app'] !== $app || microtime(true) - $entry['loaded_at'] > 60) {
            $entry = self::$lookups[$key] = ['app' => $app, 'loaded_at' => microtime(true), 'value' => $load()];
        }

        return $entry['value'];
    }
}
