<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Sub-programs (Core fix #10, decided 2026-10-30).
 *
 * The program codes HR used twice were sub-programs filed as programs: Family
 * to Family and Earthquake Assistance belong to Relief Programs (003), Sha8er
 * and the Entrepreneurship project to CYG (009). They keep their rows and ids
 * (positions and holiday_project point at them) and gain a `parent_id`.
 *
 * `code` is unique among top-level programs only; a sub-program may share its
 * parent's code. MySQL has no partial index, so the unique index sits on a
 * virtual column that is the code of a top-level program and NULL otherwise
 * (the same column on SQLite).
 *
 * The parent mapping is frozen here and set on rows that already exist (the
 * hub copy of HR's programs); `hub:import` reads it from this file for the
 * rows it imports.
 */
return new class extends Migration
{
    /**
     * Sub-program id => parent program id.
     *
     * @return array<int, int>
     */
    public static function parents(): array
    {
        return [4 => 3, 5 => 3, 11 => 14, 12 => 14];
    }

    public function up(): void
    {
        Schema::table('programs', function (Blueprint $table) {
            // SQLite cannot add a foreign key without rebuilding the table, and
            // dropping `programs` there fires positions' ON DELETE CASCADE.
            $column = $table->foreignId('parent_id')->nullable()->after('id');

            if (DB::connection()->getDriverName() !== 'sqlite') {
                $column->constrained('programs')->restrictOnDelete();
            }
        });

        foreach (self::parents() as $childId => $parentId) {
            if (DB::table('programs')->where('id', $parentId)->exists()) {
                DB::table('programs')->where('id', $childId)->update(['parent_id' => $parentId]);
            }
        }

        Schema::table('programs', function (Blueprint $table) {
            $table->string('top_level_code')->nullable()->virtualAs('case when parent_id is null then code end');
            $table->unique('top_level_code');
        });
    }

    public function down(): void
    {
        Schema::table('programs', function (Blueprint $table) {
            $table->dropUnique(['top_level_code']);
            $table->dropColumn('top_level_code');
        });

        Schema::table('programs', function (Blueprint $table) {
            if (DB::connection()->getDriverName() !== 'sqlite') {
                $table->dropForeign(['parent_id']);
            }

            $table->dropColumn('parent_id');
        });
    }
};
