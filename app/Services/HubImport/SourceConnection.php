<?php

namespace App\Services\HubImport;

use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Opens a source database of `hub:import` read-only.
 *
 * The session itself refuses writes (MySQL `SET SESSION TRANSACTION READ
 * ONLY`, SQLite `PRAGMA query_only`), so a bug in the importer, or a source
 * connection pointed at the wrong database, cannot change the source. The
 * importer only ever selects from it anyway; this makes that a guarantee.
 */
final class SourceConnection
{
    public static function open(string $name): Connection
    {
        $connection = DB::connection($name);

        match ($connection->getDriverName()) {
            'mysql', 'mariadb' => $connection->statement('SET SESSION TRANSACTION READ ONLY'),
            'sqlite' => $connection->statement('PRAGMA query_only = ON'),
            default => throw new RuntimeException("hub:import cannot make a {$connection->getDriverName()} source connection read-only."),
        };

        return $connection;
    }
}
