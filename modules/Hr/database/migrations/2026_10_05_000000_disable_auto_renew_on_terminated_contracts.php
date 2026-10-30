<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Contracts terminated before ContractObserver::saving() started forcing
     * auto_renew off still carry the column default of true, which would let
     * ContractAutoRenewalService roll them over once their end_date passes.
     *
     * A query-builder update on purpose: going through the model would fire
     * ContractObserver::updated() and open a belongings-return checklist for
     * every one of these long-gone employees.
     *
     * No down(): which of these rows were true before cannot be recovered,
     * and true was never a valid state for them.
     */
    public function up(): void
    {
        DB::table('contracts')
            ->where('auto_renew', true)
            ->where(fn ($q) => $q->whereNotNull('terminated_at')->orWhereNotNull('termination_reason_id'))
            ->update(['auto_renew' => false]);
    }

    public function down(): void {}
};
