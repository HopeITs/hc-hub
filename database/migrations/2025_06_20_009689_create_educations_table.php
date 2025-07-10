<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('educations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->string('university');
            $table->unsignedTinyInteger('type')->nullable()->comment('0 = Unknown, 1 = Primary_School, 2 = High_School, 3 = Institute, 4 = University, 5 = Master, 6 = PHD');
            $table->year('graduation_year')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('educations');
    }
};
