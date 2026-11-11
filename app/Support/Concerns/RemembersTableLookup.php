<?php

declare(strict_types=1);

namespace App\Support\Concerns;

use Closure;

/**
 * Keeps a whole-table lookup (e.g. every employee's current position) in memory, so
 * a page can label many rows without one query per row.
 *
 * The lookup belongs to the running application instance and is reloaded
 * after a minute, so a long-lived worker does not keep serving old data.
 * An id missing from it triggers one reload, which picks up a row added
 * since; an id still missing after that is remembered as absent.
 */
trait RemembersTableLookup
{
    /**
     * @var array{app: int|null, loaded_at: float, entries: array<int, mixed>, absent: array<int, true>}
     */
    private static array $tableLookup = ['app' => null, 'loaded_at' => 0.0, 'entries' => [], 'absent' => []];

    /**
     * Drop the remembered lookup so the next read reloads it: for a
     * test that changes the data mid-request, or code that just wrote to it.
     */
    public static function forgetRememberedLookup(): void
    {
        self::$tableLookup = ['app' => null, 'loaded_at' => 0.0, 'entries' => [], 'absent' => []];
    }

    /**
     * Every entry of the lookup, keyed by id.
     *
     * @param  Closure(): array<int, mixed>  $load
     * @return array<int, mixed>
     */
    protected static function rememberedLookup(Closure $load): array
    {
        $app = spl_object_id(app());

        if (self::$tableLookup['app'] !== $app || microtime(true) - self::$tableLookup['loaded_at'] > 60) {
            self::$tableLookup = ['app' => $app, 'loaded_at' => microtime(true), 'entries' => $load(), 'absent' => []];
        }

        return self::$tableLookup['entries'];
    }

    /**
     * One entry of the lookup, or null when there is no such row.
     *
     * @param  Closure(): array<int, mixed>  $load
     */
    protected static function rememberedLookupEntry(int $id, Closure $load): mixed
    {
        $entries = self::rememberedLookup($load);

        if (! array_key_exists($id, $entries) && ! isset(self::$tableLookup['absent'][$id])) {
            self::$tableLookup['entries'] = $entries = $load();
            self::$tableLookup['loaded_at'] = microtime(true);

            if (! array_key_exists($id, $entries)) {
                self::$tableLookup['absent'][$id] = true;
            }
        }

        return $entries[$id] ?? null;
    }
}
