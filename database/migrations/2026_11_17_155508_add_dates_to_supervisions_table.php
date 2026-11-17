<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Supervisions are ended, not deleted (roadmap 2.5, D4): starts_on, ends_on
 * and ended_by keep the history. Rows from before have no known start, so
 * starts_on stays null for them (created_at still says when they were
 * entered). `is_open` is 1 while ends_on is null and NULL once it is set, so
 * the pair index now refuses a duplicate or an A↔B loop among open rows only
 * and lets the same pair come back after it ended.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('supervisions', function (Blueprint $table): void {
            $table->date('starts_on')->nullable()->after('supervisee_employee_id');
            $table->date('ends_on')->nullable()->after('starts_on');
            $table->foreignId('ended_by')->nullable()->after('created_by')->constrained('users', indexName: 'supervisions_ended_by_foreign')->nullOnDelete();
            $table->unsignedTinyInteger('is_open')->nullable()->storedAs('IF(`ends_on` IS NULL, 1, NULL)');
        });

        Schema::table('supervisions', function (Blueprint $table): void {
            $table->unique(['pair_low', 'pair_high', 'is_open'], 'supervisions_open_pair_unique');
            $table->dropUnique('supervisions_pair_unique');
        });
    }

    /**
     * Back to "a removed supervision is a deleted row": ended rows are
     * dropped first, since the old pair index allows one row per pair.
     */
    public function down(): void
    {
        DB::table('supervisions')->whereNotNull('ends_on')->delete();

        Schema::table('supervisions', function (Blueprint $table): void {
            $table->unique(['pair_low', 'pair_high'], 'supervisions_pair_unique');
            $table->dropUnique('supervisions_open_pair_unique');
        });

        Schema::table('supervisions', function (Blueprint $table): void {
            $table->dropColumn('is_open');
            $table->dropConstrainedForeignId('ended_by');
            $table->dropColumn(['starts_on', 'ends_on']);
        });
    }
};
