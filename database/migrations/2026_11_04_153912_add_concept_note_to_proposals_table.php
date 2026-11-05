<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The concept note a proposal starts from (W-24, D-62): the text itself,
     * beside the free `notes` it used to be squeezed into. The uploaded
     * document is a `concept_note` media row, so it needs no column.
     */
    public function up(): void
    {
        if (Schema::hasColumn('proposals', 'concept_note')) {
            throw new RuntimeException('proposals.concept_note already exists; nothing was changed.');
        }

        Schema::table('proposals', function (Blueprint $table) {
            $table->text('concept_note')->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('proposals', function (Blueprint $table) {
            $table->dropColumn('concept_note');
        });
    }
};
