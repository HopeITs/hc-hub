<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A donor requested from the proposal form is pending until the General
     * Manager approves it (D-57). Every donor that exists today is approved.
     */
    public function up(): void
    {
        foreach (['approval_status', 'requested_by', 'approved_by', 'approved_at'] as $column) {
            if (Schema::hasColumn('donors', $column)) {
                throw new RuntimeException("donors.{$column} already exists; nothing was changed.");
            }
        }

        Schema::table('donors', function (Blueprint $table) {
            $table->string('approval_status', 10)->default('approved');
            $table->foreignId('requested_by')->nullable()->constrained('users', 'id', 'donors_requested_by_foreign')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users', 'id', 'donors_approved_by_foreign')->nullOnDelete();
            $table->dateTime('approved_at')->nullable();
            $table->index('approval_status', 'donors_approval_status_index');
        });

        DB::table('donors')->whereNull('approval_status')->update(['approval_status' => 'approved']);
    }

    public function down(): void
    {
        Schema::table('donors', function (Blueprint $table) {
            $table->dropForeign('donors_requested_by_foreign');
            $table->dropForeign('donors_approved_by_foreign');
            $table->dropIndex('donors_approval_status_index');
            $table->dropColumn(['approval_status', 'requested_by', 'approved_by', 'approved_at']);
        });
    }
};
