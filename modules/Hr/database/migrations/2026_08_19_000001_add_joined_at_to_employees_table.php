<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The date the person joined the organisation. This is deliberately NOT
     * the first contract's start_date: contracts were imported from Excel and
     * often start long after the volunteer actually joined, so the real
     * joining date has to be stored on the employee itself.
     */
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->date('joined_at')->nullable()->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn('joined_at');
        });
    }
};
