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
            $table->string('full_name');
            $table->integer('years_of_service')->default(0);
            $table->foreignId('department_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('direct_manager_id')->nullable()->constrained('employees')->onDelete('set null');
            $table->foreignId('tech_manager_id')->nullable()->constrained('employees')->onDelete('set null');
            $table->string('national_number')->unique();
            $table->date('dob');
            $table->string('actual_job')->nullable();
            $table->string('civil_registry_office')->nullable();
            $table->string('civil_registry_number')->nullable();
            $table->string('place_of_registration')->nullable();
            $table->string('gender');
            $table->string('father_name');
            $table->string('mother_name');
            $table->string('nationality');
            $table->string('material_status');
            $table->integer('children')->default(0);
            $table->string('work_phone');
            $table->string('personal_email');
            $table->string('work_email');
            $table->text('address');
            $table->text('skills')->nullable();
            $table->text('interests')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('employees');
    }
};
