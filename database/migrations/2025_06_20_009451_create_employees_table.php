<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->json('name');
            $table->string('photo')->nullable();
            $table->string('actual_job')->nullable();
            $table->double('years_of_service')->nullable();
            $table->foreignId('direct_manager_id')->nullable()->constrained('employees')->onDelete('set null');
            $table->foreignId('tech_manager_id')->nullable()->constrained('employees')->onDelete('set null');
            $table->char('national_number')->unique()->nullable();
            $table->dateTime('dob')->nullable();
            $table->string('civil_registry_office')->nullable();
            $table->string('civil_registry_number')->nullable();
            $table->string('place_of_registration')->nullable();
            $table->unsignedTinyInteger('gender')->nullable()->comment('0 = Unknown, 1 = Male, 2 = Female');
            $table->json('father_name')->nullable();
            $table->json('mother_name')->nullable();
            $table->tinyInteger('nationality')->nullable()->comment('0 = Unknown, 1 = Syrian');
            $table->unsignedTinyInteger('marital_status')->nullable()->comment('0 = Unknown, 1 = Single, 2 = Married');
            $table->integer('children')->default(0)->nullable();
            $table->string('work_phone')->nullable();
            $table->string('personal_phone')->nullable();
            $table->string('personal_email')->nullable();
            $table->json('address')->nullable();
            $table->string('skills')->nullable();
            $table->string('interests')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_board_member')->default(false);
            $table->dateTime('board_member_started_at')->nullable();
            $table->dateTime('board_member_end_at')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('employees');
    }
};
