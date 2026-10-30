<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('badges', function (Blueprint $table) {
            $table->string('subtype')->nullable();
            $table->decimal('amount', 12, 2)->nullable();
            $table->string('new_position')->nullable();
            $table->string('leave_unit')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('badges', function (Blueprint $table) {
            $table->dropColumn([
                'subtype',
                'amount',
                'new_position',
                'leave_unit',
            ]);
        });
    }
};
