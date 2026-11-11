<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * The governorates every module shares (Core), ids kept from HR's
     * hchr_cities. Id 1 is the pseudo-governorate "Syria" (organisation-wide
     * positions). `slug` is the 3-letter code HR roles are scoped by
     * (HR-ALP => ALP); `code` is the zero-padded number used on digital IDs.
     */
    public function up(): void
    {
        Schema::create('cities', function (Blueprint $table) {
            $table->id();
            $table->string('name_en');
            $table->string('name_ar');
            $table->string('slug')->unique();
            $table->string('code')->unique();
            $table->string('hr_hotline', 32)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cities');
    }
};
