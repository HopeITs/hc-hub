<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A project in a city with no HR center is hosted by another center (D-55,
 * W-01): `center_id` stays null and `host_center_id` names the HR center that
 * follows it up. The column holds an id from the HR database, so it has no
 * foreign key. No backfill: W-02 maps the unmapped rows.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('projects', 'host_center_id')) {
            return;
        }

        Schema::table('projects', function (Blueprint $table): void {
            $table->unsignedBigInteger('host_center_id')->nullable()->after('center_id');
            $table->index('host_center_id', 'projects_host_center_id_index');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('projects', 'host_center_id')) {
            return;
        }

        Schema::table('projects', function (Blueprint $table): void {
            $table->dropIndex('projects_host_center_id_index');
            $table->dropColumn('host_center_id');
        });
    }
};
