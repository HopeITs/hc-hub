<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * The HR screens list and filter cities, centers and programs by
     * is_active and sort them by name; index both so those lookups stay
     * index-only as the tables grow.
     */
    public function up(): void
    {
        Schema::table('cities', function (Blueprint $table) {
            $table->index(['is_active', 'name_en']);
        });

        Schema::table('centers', function (Blueprint $table) {
            $table->index(['is_active', 'name_en']);
        });

        Schema::table('programs', function (Blueprint $table) {
            $table->index(['is_active', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cities', function (Blueprint $table) {
            $table->dropIndex(['is_active', 'name_en']);
        });

        Schema::table('centers', function (Blueprint $table) {
            $table->dropIndex(['is_active', 'name_en']);
        });

        Schema::table('programs', function (Blueprint $table) {
            $table->dropIndex(['is_active', 'name']);
        });
    }
};
