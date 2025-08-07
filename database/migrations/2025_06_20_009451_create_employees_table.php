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
            $table->string('actual_job')->nullable();
            $table->double('years_of_service')->default(0);
            $table->string('work_email')->nullable();
            $table->foreignId('direct_manager_id')->nullable()->constrained('employees')->onDelete('set null');
            $table->foreignId('tech_manager_id')->nullable()->constrained('employees')->onDelete('set null');
            $table->char('national_number')->unique();
            $table->date('dob')->nullable();
            $table->string('civil_registry_office')->nullable();
            $table->string('civil_registry_number')->nullable();
            $table->string('place_of_registration')->nullable();
            $table->unsignedTinyInteger('gender')->nullable()->comment('0 = Unknown, 1 = Male, 2 = Female');
            $table->json('father_name')->nullable();
            $table->json('mother_name')->nullable();
            $table->enum('nationality', ['syrian']);
            $table->unsignedTinyInteger('marital_status')->nullable()->comment('0 = Unknown, 1 = Single, 2 = Married');
            $table->integer('children')->default(0);
            $table->string('work_phone');
            $table->string('personal_phone');
            $table->string('personal_email');
            $table->json('address');
            $table->string('skills')->nullable();
            $table->string('interests')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_board_member')->default(false);
            $table->date('board_member_started_at')->nullable();
            $table->date('board_member_end_at')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('employees');
    }
};
