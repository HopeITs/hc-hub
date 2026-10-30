<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Control tables of `php artisan hub:import` (plan 2b, decision C6).
 *
 * Both keep the Core_DB control-table names and shape, so the hub's ETL output
 * can be loaded into the Core unchanged:
 * - `entity_xref`: one row per imported source row — where it came from
 *   (system, table with its real prefix, id) and what it became (morph alias,
 *   hub id, public uuid where the table has one).
 * - `review_decisions`: every cleaning choice the importer applied to a source
 *   value (trimmed, replaced, deliberately kept), with the reason.
 *
 * Neither ever holds a pay figure.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('entity_xref', function (Blueprint $table) {
            $table->id();
            $table->string('source_system', 32);
            $table->string('source_table', 64);
            $table->string('source_id', 64);
            $table->string('entity_type', 64);
            $table->string('entity_id', 64);
            $table->char('uuid', 36)->nullable();
            $table->timestamps();

            $table->unique(['source_system', 'source_table', 'source_id']);
            $table->index(['entity_type', 'entity_id']);
        });

        Schema::create('review_decisions', function (Blueprint $table) {
            $table->id();
            $table->string('source_system', 32);
            $table->string('source_table', 64);
            $table->string('source_id', 64);
            $table->string('column_name', 64)->default('');
            $table->string('decision', 32);
            $table->text('old_value')->nullable();
            $table->text('new_value')->nullable();
            $table->text('reason');
            $table->timestamps();

            $table->unique(['source_system', 'source_table', 'source_id', 'column_name'], 'review_decisions_source_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('review_decisions');
        Schema::dropIfExists('entity_xref');
    }
};
