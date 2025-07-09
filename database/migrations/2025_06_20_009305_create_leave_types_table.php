<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('leave_types', function (Blueprint $table) {
            $table->id();
            $table->json('name');
            $table->integer('base_days')->default(0);
            $table->unsignedTinyInteger('allowed_gender')->nullable()->comment('0 = Unknown, 1 = Male, 2 = Female');
            $table->unsignedTinyInteger('marital_status')->nullable()->comment('0 = Unknown, 1 = Single, 2 = Married');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('leave_types');
    }
};
