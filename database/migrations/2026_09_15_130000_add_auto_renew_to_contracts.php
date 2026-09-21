<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Whether a contract renews automatically at term end. Defaults true —
     * right for the volunteer and service categories — except trial/
     * probation contracts, which default false: a trial is meant to end and
     * be evaluated, not roll over silently. New contracts get the correct
     * default from ContractObserver::creating() (keyed off the contract
     * type's category, same source as config('contracts.map_by_id')); this
     * migration only backfills existing rows, since ADD COLUMN ... DEFAULT
     * true already gives every row `true` and trial rows need flipping.
     */
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->boolean('auto_renew')->default(true)->after('is_active');
        });

        $trialTypeIds = collect(config('contracts.map_by_id', []))
            ->filter(fn ($category) => $category === 'trial')
            ->keys();

        if ($trialTypeIds->isNotEmpty()) {
            DB::table('contracts')->whereIn('contract_type_id', $trialTypeIds)->update(['auto_renew' => false]);
        }
    }

    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropColumn('auto_renew');
        });
    }
};
