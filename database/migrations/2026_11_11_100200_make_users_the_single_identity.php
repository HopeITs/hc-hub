<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * users is the hub's one account table: people sign in with its email and
     * password, and an account links to its employee by a real foreign key.
     * hr_user_id (the old link to HR's own accounts, equal to id since the
     * renumbering) goes. Every row already has an email and a password; the
     * migration stops rather than guess if one does not.
     */
    public function up(): void
    {
        $missing = DB::table('users')->whereNull('email')->orWhereNull('password')->count();

        if ($missing > 0) {
            throw new RuntimeException("{$missing} users have no email or password; fix them before making users the single identity.");
        }

        Schema::table('users', function (Blueprint $table): void {
            $table->string('email')->nullable(false)->change();
            $table->string('password')->nullable(false)->change();
            $table->foreign('employee_id')->references('id')->on('employees')->nullOnDelete();
        });

        if (Schema::hasColumn('users', 'hr_user_id')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->dropUnique('users_hr_user_id_unique');
                $table->dropColumn('hr_user_id');
            });
        }

        if (DB::getDriverName() === 'mysql') {
            $next = (int) DB::table('users')->max('id') + 1;
            DB::statement("ALTER TABLE users AUTO_INCREMENT = {$next}");
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropForeign(['employee_id']);
            $table->string('email')->nullable()->change();
            $table->string('password')->nullable()->change();
            $table->unsignedBigInteger('hr_user_id')->nullable()->after('id');
        });

        DB::table('users')->update(['hr_user_id' => DB::raw('id')]);

        Schema::table('users', function (Blueprint $table): void {
            $table->unique('hr_user_id');
        });
    }
};
