<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Core-ready master data (plan 2b, decision C2, task 0.5).
 *
 * The organisation's shared Core_DB identifies a row by a stable `uuid`, not
 * by an auto-increment id that differs per database. HC-Hub is the phase-1
 * writer of these six tables, so each gets a `uuid` now: added nullable,
 * backfilled for existing rows, then made NOT NULL. Primary keys and route
 * keys stay the integer ids. New rows get theirs from App\Traits\HasPublicUuid.
 *
 * One hub-wide migration rather than one per owner: this is a single
 * platform rule (like the morph-alias data migration) and `employees` is the
 * only HR table in the set. Tracking's `donors` and `projects` get the same
 * column when they are ported.
 */
return new class extends Migration
{
    /**
     * @var list<string>
     */
    public const TABLES = ['users', 'employees', 'cities', 'centers', 'programs', 'departments'];

    public function up(): void
    {
        foreach (self::TABLES as $table) {
            if (! Schema::hasColumn($table, 'uuid')) {
                Schema::table($table, function (Blueprint $blueprint) {
                    $blueprint->char('uuid', 36)->nullable()->after('id')->unique();
                });
            }

            $this->backfill($table);

            if ($this->notNullIsSafe()) {
                Schema::table($table, function (Blueprint $blueprint) {
                    $blueprint->char('uuid', 36)->nullable(false)->change();
                });
            }
        }
    }

    public function down(): void
    {
        foreach (self::TABLES as $table) {
            if (Schema::hasColumn($table, 'uuid')) {
                Schema::table($table, function (Blueprint $blueprint) {
                    $blueprint->dropUnique(['uuid']);
                    $blueprint->dropColumn('uuid');
                });
            }
        }
    }

    /**
     * Give every row without one a fresh UUIDv7, chunked by id so a large
     * table never sits in memory at once.
     */
    private function backfill(string $table): void
    {
        DB::table($table)
            ->whereNull('uuid')
            ->select('id')
            ->chunkById(500, function ($rows) use ($table) {
                foreach ($rows as $row) {
                    DB::table($table)->where('id', $row->id)->update(['uuid' => (string) Str::uuid7()]);
                }
            });
    }

    /**
     * Can the column be tightened to NOT NULL here?
     *
     * Not on SQLite (the test connection): it has no ALTER COLUMN, so change()
     * rebuilds the table, and dropping `users` or `employees` inside the
     * migration transaction fires the ON DELETE CASCADE of every child table
     * (see the same note in encrypt_contract_pay_figures). The column stays
     * nullable there; HasPublicUuid fills it on every Eloquent insert anyway.
     * MySQL, which dev and production use, gets the real constraint.
     */
    private function notNullIsSafe(): bool
    {
        return DB::connection()->getDriverName() !== 'sqlite';
    }
};
