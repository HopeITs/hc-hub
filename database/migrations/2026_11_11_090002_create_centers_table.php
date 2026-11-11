<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * The organisation's centers (Core), ids kept from HR's hchr_centers.
     * A center belongs to one governorate, which cannot be deleted while it
     * has centers.
     */
    public function up(): void
    {
        Schema::create('centers', function (Blueprint $table) {
            $table->id();
            $table->string('name_en');
            $table->string('name_ar');
            $table->string('weekends')->nullable();
            $table->time('open_at')->nullable()->default('09:30:00');
            $table->time('close_at')->nullable()->default('17:00:00');
            $table->foreignId('city_id')->nullable()->constrained()->restrictOnDelete();
            $table->text('address_en')->nullable();
            $table->text('address_ar')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('centers');
    }
};
