<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One login per employee (D2). Stops with the list when an employee still
     * has two logins: `hub:duplicate-accounts` shows them, and
     * `hub:cancel-duplicate-account` cancels the one a person chose. Nothing
     * is merged or deleted here.
     */
    public function up(): void
    {
        $duplicates = DB::table('users')
            ->whereNotNull('employee_id')
            ->groupBy('employee_id')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('employee_id')
            ->map(fn (mixed $id): string => '#'.$id)
            ->implode(', ');

        if ($duplicates !== '') {
            throw new RuntimeException("Employees with more than one login: {$duplicates}. Run `php artisan hub:duplicate-accounts`, then `hub:cancel-duplicate-account` for each, before migrating.");
        }

        Schema::table('users', function (Blueprint $table) {
            $table->unique('employee_id');
        });
    }

    /**
     * The plain index (users_employee_id_index) stays, so the foreign key keeps its index.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['employee_id']);
        });
    }
};
