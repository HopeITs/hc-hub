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
        Schema::create('annual_leave_rules', function (Blueprint $table) {
            $table->id();
            $table->integer('max')->nullable();
            $table->integer('days')->nullable();
            $table->integer('day_per_month')->nullable();
            $table->integer('years_of_volunteering')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('annual_leave_rules');
    }
};
