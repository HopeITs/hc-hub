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
            $table->string('name');
            $table->integer('base_days')->default(0);
            $table->enum('allowed_gender', ['male', 'female'])->nullable();
            $table->enum('marital_status', ['annual', 'sick', 'maternity', 'unpaid', 'hourly', 'death', 'marriage', 'paternity'])->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('leave_types');
    }
};
