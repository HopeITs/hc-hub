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
            $table->json('name');
            $table->unsignedTinyInteger('place')->default(1)->comment('0 = Unknown, 1 = Onsite, 2 = Online');
            $table->unsignedTinyInteger('type')->default(1)->comment('0 = Unknown, 1 = Full Time, 2 = Part Time, 3 = Freelance');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('contract_types');
    }
};
