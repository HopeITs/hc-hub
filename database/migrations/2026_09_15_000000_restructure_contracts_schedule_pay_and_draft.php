<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * contract_types ids 2, 4 and 6 (Partial Annual / Trial Partial / the
     * second service-agreement row) are redundant now that `type` and
     * `place` live on contracts itself (see the 2026_09_14 migration this
     * one follows): every contract pointing at one of them is remapped to
     * its category-mate (1, 3, 5) before the redundant rows are dropped,
     * and the three survivors are renamed to the category names the
     * composer now uses everywhere.
     *
     * Consolidating destroys which of the two original rows a contract used
     * to point at, so down() cannot restore the exact original assignment —
     * it recreates 2, 4 and 6 as best-effort duplicates of 1, 3 and 5 (same
     * names) without reassigning any contract back to them, the same
     * best-effort tradeoff the 2026_09_14 migration already accepted.
     */
    private const MERGES = [2 => 1, 4 => 3, 6 => 5];

    private const FINAL_NAMES = [
        1 => ['name_en' => 'Volunteer Contract', 'name_ar' => 'عقد تطوّع'],
        3 => ['name_en' => 'Trial Volunteer Contract', 'name_ar' => 'عقد تطوّع تجريبي'],
        5 => ['name_en' => 'Specific Voluntary Service Contract', 'name_ar' => 'عقد تقديم خدمة تطوعية محددة'],
    ];

    public function up(): void
    {
        foreach (self::MERGES as $from => $to) {
            DB::table('contracts')->where('contract_type_id', $from)->update(['contract_type_id' => $to]);
        }

        DB::table('contract_types')->whereIn('id', array_keys(self::MERGES))->delete();

        foreach (self::FINAL_NAMES as $id => $names) {
            DB::table('contract_types')->where('id', $id)->update($names);
        }
    }

    public function down(): void
    {
        foreach (self::MERGES as $droppedId => $survivorId) {
            $survivor = DB::table('contract_types')->where('id', $survivorId)->first();

            if ($survivor) {
                DB::table('contract_types')->insert([
                    'id' => $droppedId,
                    'name_en' => $survivor->name_en,
                    'name_ar' => $survivor->name_ar,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
};
