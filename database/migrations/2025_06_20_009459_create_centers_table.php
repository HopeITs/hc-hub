<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('centers', function (Blueprint $table) {
            $table->id();
            $table->json('name');
            $table->foreignId('city_id')->constrained()->onDelete('restrict');
            $table->foreignId('project_id')->constrained()->onDelete('restrict');
            $table->json('address')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('centers');
    }
};
