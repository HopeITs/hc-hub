<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * The sidebar's personal zone: the pages each person pinned, as a list of
     * route names (["transfers.index", ...]), and the records they opened last,
     * as [{"route": "projects.show", "key": "gaza-wash", "label": "...", "kind": "project"}, ...].
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->json('nav_pins')->nullable()->after('page_panels');
            $table->json('nav_recent')->nullable()->after('nav_pins');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['nav_pins', 'nav_recent']);
        });
    }
};
