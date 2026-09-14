<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * `type` (full/part/freelance) and `place` (onsite/online) describe one
     * specific contract, not the category it belongs to — two contracts of
     * the same contract_type (e.g. "Trial") can differ on both. Keeping them
     * on contract_types forced a separate row per type×place combination
     * instead of letting each contract state its own.
     */
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->unsignedTinyInteger('type')->default(1)->after('contract_type_id');
            $table->unsignedTinyInteger('place')->default(1)->after('type');
        });

        DB::statement('
            UPDATE contracts
            INNER JOIN contract_types ON contract_types.id = contracts.contract_type_id
            SET contracts.type = contract_types.type,
                contracts.place = contract_types.place
        ');

        Schema::table('contract_types', function (Blueprint $table) {
            $table->dropColumn(['type', 'place']);
        });
    }

    public function down(): void
    {
        Schema::table('contract_types', function (Blueprint $table) {
            $table->unsignedTinyInteger('place')->default(1);
            $table->unsignedTinyInteger('type')->default(1);
        });

        // Best-effort backfill: each contract_type row takes the type/place of
        // whichever contract currently points at it, so reverting the schema
        // does not leave every row stuck at the default.
        DB::statement('
            UPDATE contract_types
            INNER JOIN contracts ON contracts.contract_type_id = contract_types.id
            SET contract_types.type = contracts.type,
                contract_types.place = contracts.place
        ');

        Schema::table('contracts', function (Blueprint $table) {
            $table->dropColumn(['type', 'place']);
        });
    }
};
