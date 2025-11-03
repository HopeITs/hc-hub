<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hchr_news', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('category')->nullable();
            $table->string('photo')->nullable();
            $table->enum('display_duration', [
                '1_hour','2_hours','3_hours','4_hours','5_hours','6_hours','7_hours','8_hours',
                '1_day','2_days','3_days','4_days','5_days','6_days','1_week'
            ])->default('1_day');
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('news');
    }
};
