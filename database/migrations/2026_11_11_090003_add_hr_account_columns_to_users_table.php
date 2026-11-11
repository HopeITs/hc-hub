<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * The HR account columns, so users becomes the one shared (Core) account
     * table: login email, password hash and last activity. Nullable for now:
     * until the hub stops reading accounts from the HR database (step A2),
     * tracking still creates rows without them, and `hub:import-hr` fills
     * them from HR. users.id is aligned to the HR user id by that import.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->nullable()->unique()->after('employee_id');
            $table->string('password')->nullable()->after('email');
            $table->timestamp('last_seen_at')->nullable()->index()->after('remember_token');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['email']);
            $table->dropIndex(['last_seen_at']);
            $table->dropColumn(['email', 'password', 'last_seen_at']);
        });
    }
};
