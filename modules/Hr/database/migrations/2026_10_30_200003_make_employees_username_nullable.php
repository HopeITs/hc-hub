<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * `employees.username` is optional (decided 2026-10-30): staff without a
 * login may have none (hc-hr holds 11 such rows). The unique index stays;
 * MySQL and SQLite both allow any number of NULLs in it. A blank username is
 * stored as NULL (Employee::username()), so lookups by username never match ''.
 *
 * On SQLite, change() rebuilds the table, and dropping `employees` fires the
 * ON DELETE CASCADE of every child table. It is only done there while the
 * table is empty (the test database), so no row can be lost.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('employees')->where('username', '')->update(['username' => null]);

        if (! $this->changeIsSafe()) {
            return;
        }

        Schema::table('employees', function (Blueprint $table) {
            $table->string('username')->nullable()->change();
        });
    }

    public function down(): void
    {
        if (DB::table('employees')->whereNull('username')->exists()) {
            throw new RuntimeException('Employees without a username exist; give them one before rolling back.');
        }

        if (! $this->changeIsSafe()) {
            return;
        }

        Schema::table('employees', function (Blueprint $table) {
            $table->string('username')->nullable(false)->change();
        });
    }

    private function changeIsSafe(): bool
    {
        return DB::connection()->getDriverName() !== 'sqlite' || ! DB::table('employees')->exists();
    }
};
