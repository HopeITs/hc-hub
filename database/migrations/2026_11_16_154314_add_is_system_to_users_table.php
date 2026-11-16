<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * A system account (no person behind it) is now said so explicitly
     * instead of being read from a null employee_id (D2). Every account with
     * no employee today is one, so they keep signing in; from now on a
     * non-system account with no employee is refused (User::hasLostAccess()).
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_system')->default(false)->after('employee_id');
        });

        DB::table('users')->whereNull('employee_id')->update(['is_system' => true]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_system');
        });
    }
};
