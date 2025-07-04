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
            $table->json('full_name');
            $table->double('years_of_service')->default(0);
            $table->foreignId('direct_manager_id')->nullable()->constrained('employees')->onDelete('set null');
            $table->foreignId('tech_manager_id')->nullable()->constrained('employees')->onDelete('set null');
            $table->char('national_number')->unique();
            $table->date('dob');
            $table->date('actual_dob')->nullable();
            $table->string('civil_registry_office')->nullable();
            $table->string('civil_registry_number')->nullable();
            $table->string('place_of_registration')->nullable();
            $table->enum('gender', ['male', 'female']);
            $table->json('father_name');
            $table->json('mother_name');
            $table->enum('nationality', ['syrian']);
            $table->enum('marital_status', ['annual', 'sick', 'maternity', 'unpaid', 'hourly', 'death', 'marriage', 'paternity'])->nullable();
            $table->integer('children')->default(0);
            $table->string('work_phone');
            $table->string('personal_phone');
            $table->string('personal_email');
            $table->string('work_email');
            $table->json('address');
            $table->string('skills')->nullable();
            $table->string('interests')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_board_member');
            $table->boolean('board_member_started_at');
            $table->boolean('board_member_end_at');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('employees');
    }
};
