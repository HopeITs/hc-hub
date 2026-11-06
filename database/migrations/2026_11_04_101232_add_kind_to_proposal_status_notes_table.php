<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tell transitions, corrections and negotiation notes apart (W-07, D-59).
     *
     * W-06 adds the same column, because it needed `on_hold` / `off_hold`
     * first, with this definition exactly (contracts.md §7). On a fresh
     * database this migration runs first and W-06's returns early; where W-06
     * was migrated from its own branch before this one, the column is already
     * here and there is nothing left to do. Returning rather than throwing is
     * what makes that second order survivable: a throw here stopped every
     * later migration on such a database.
     */
    public function up(): void
    {
        if (Schema::hasColumn('proposal_status_notes', 'kind')) {
            return;
        }

        Schema::table('proposal_status_notes', function (Blueprint $table) {
            $table->string('kind', 20)->default('transition');
        });

        $this->backfillKinds();
    }

    /**
     * Notes written by a status correction become `correction`.
     */
    public function backfillKinds(): void
    {
        DB::table('proposal_status_notes')->where('is_correction', true)->update(['kind' => 'correction']);
    }

    /**
     * Guarded for the same reason: whichever of the two migrations rolls back
     * first takes the column, and the other must not fail on its absence.
     */
    public function down(): void
    {
        if (! Schema::hasColumn('proposal_status_notes', 'kind')) {
            return;
        }

        Schema::table('proposal_status_notes', function (Blueprint $table) {
            $table->dropColumn('kind');
        });
    }
};
