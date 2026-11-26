<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Tracking\Support\Locations\MealCoveragePlaceBackfill;

/**
 * Copy each project-scoped MEAL officer onto the reporting places of the
 * project's location rows; a place claimed by two officers stops the run.
 */
return new class extends Migration
{
    public function up(): void
    {
        $backfill = app(MealCoveragePlaceBackfill::class);
        $preview = $backfill->run();
        if ($preview['conflicts'] !== []) {
            throw new RuntimeException('Conflicting MEAL coverage assignments: '.json_encode($preview['conflicts'], JSON_THROW_ON_ERROR));
        }

        Schema::table('meal_coverages', function (Blueprint $table): void {
            $table->unique('scope_key', 'meal_coverages_scope_key_unique');
            // MySQL needs another index for the city foreign key.
            $table->index('city_id', 'meal_coverages_city_id_index');
            $table->dropUnique('meal_coverages_city_id_unique');
        });

        $backfill->run(apply: true);

        // MySQL needs another index for the existing project foreign key.
        Schema::table('meal_coverages', function (Blueprint $table): void {
            $table->index('project_id', 'meal_coverages_legacy_project_index');
            $table->dropUnique('meal_coverages_project_id_unique');
        });
    }

    public function down(): void
    {
        DB::table('meal_coverages')->where('scope_type', 'place')->delete();
        DB::table('meal_coverages')->where('scope_type', 'city')->update(['scope_key' => null]);

        Schema::table('meal_coverages', function (Blueprint $table): void {
            $table->unique('city_id', 'meal_coverages_city_id_unique');
            $table->unique('project_id', 'meal_coverages_project_id_unique');
            $table->dropIndex('meal_coverages_legacy_project_index');
            $table->dropIndex('meal_coverages_city_id_index');
            $table->dropUnique('meal_coverages_scope_key_unique');
        });
    }
};
