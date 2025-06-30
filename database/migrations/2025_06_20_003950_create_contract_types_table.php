<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('contract_types', function (Blueprint $table) {
            $table->id();
            $table->json('type');
            $table->json('place');
            $table->integer('weekly_hours');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('contract_types');
    }
};
