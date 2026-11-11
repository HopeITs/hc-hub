<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * HR organisation tables, as they stood in the HR app (hchr_ prefix dropped).
 * Foreign keys are added together in the add_hr_foreign_keys migration so
 * the tables can be created in any order.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('departments', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('code');
            $table->text('description_en')->nullable();
            $table->text('description_ar')->nullable();
            $table->string('email')->nullable();
            $table->timestamps();
            $table->unique('code');
            $table->unique('email');
        });

        Schema::create('job_levels', function (Blueprint $table): void {
            $table->id();
            $table->string('name_en');
            $table->string('name_ar');
            $table->text('description')->nullable();
            $table->integer('grade')->nullable();
            $table->string('email')->nullable();
            $table->timestamps();
        });

        Schema::create('positions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('program_id');
            $table->unsignedBigInteger('center_id')->nullable();
            $table->unsignedBigInteger('city_id')->default(2);
            $table->unsignedBigInteger('job_level_id');
            $table->unsignedBigInteger('department_id');
            $table->unsignedBigInteger('direct_manager_id')->nullable();
            $table->unsignedBigInteger('tech_manager_id')->nullable();
            $table->string('title_en');
            $table->string('title_ar');
            $table->text('qualifications')->nullable();
            $table->text('responsibilities')->nullable();
            $table->integer('max_emp')->default(1);
            $table->integer('exist_emp')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('employees', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('city_id')->nullable();
            $table->string('first_name_en');
            $table->string('first_name_ar');
            $table->string('last_name_en');
            $table->string('last_name_ar');
            $table->string('father_name_en')->nullable();
            $table->string('father_name_ar')->nullable();
            $table->string('mother_name_en')->nullable();
            $table->string('mother_name_ar')->nullable();
            $table->string('username')->nullable();
            $table->char('national_number', 255)->nullable();
            $table->dateTime('actual_dob')->nullable()->comment('real birthday');
            $table->dateTime('dob')->nullable()->comment('date of birth in ID');
            $table->string('pob_en')->nullable()->comment('place of birth');
            $table->string('pob_ar')->nullable()->comment('place of birth');
            $table->string('civil_registry_office')->nullable();
            $table->string('civil_registry_number')->nullable();
            $table->unsignedTinyInteger('gender')->nullable();
            $table->tinyInteger('nationality')->nullable();
            $table->unsignedTinyInteger('marital_status')->nullable();
            $table->integer('children')->nullable()->default(0);
            $table->string('work_phone')->nullable();
            $table->string('personal_phone')->nullable();
            $table->string('personal_email')->nullable();
            $table->string('whatsapp_work')->nullable();
            $table->string('telegram_work')->nullable();
            $table->string('facebook')->nullable();
            $table->string('instagram')->nullable();
            $table->string('linkedin')->nullable();
            $table->text('address_en')->nullable();
            $table->text('address_ar')->nullable();
            $table->string('skills')->nullable();
            $table->string('interests')->nullable();
            $table->boolean('is_active')->default(true);
            $table->date('joined_at')->nullable();
            $table->boolean('is_board_member')->default(false);
            $table->dateTime('board_member_started_at')->nullable();
            $table->dateTime('board_member_end_at')->nullable();
            $table->timestamps();
            $table->string('digital_id_token', 64)->nullable();
            $table->boolean('digital_id_enabled')->default(true);
            $table->timestamp('digital_id_rotated_at')->nullable();
            $table->timestamp('digital_id_revoked_at')->nullable();
            $table->unsignedSmallInteger('digital_id_card_index')->default(0);
            $table->string('record_status', 16)->default('complete');
            $table->unsignedBigInteger('last_saved_by')->nullable();
            $table->unique('digital_id_token');
            $table->unique('national_number');
            $table->index('record_status');
            $table->unique('username');
        });

        Schema::create('education_fields', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('educations', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('employee_id');
            $table->string('name');
            $table->string('specialization');
            $table->unsignedTinyInteger('degree')->nullable();
            $table->unsignedTinyInteger('status')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('experiences', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('employee_id');
            $table->string('job_title');
            $table->string('company');
            $table->dateTime('start_date');
            $table->dateTime('end_date')->nullable();
            $table->tinyInteger('place')->nullable();
            $table->tinyInteger('type')->nullable();
            $table->timestamps();
        });

        Schema::create('course_categories', function (Blueprint $table): void {
            $table->id();
            $table->string('name_en');
            $table->string('name_ar');
            $table->timestamps();
        });

        Schema::create('courses', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('employee_id');
            $table->unsignedBigInteger('category_id')->nullable();
            $table->string('title');
            $table->string('organization')->nullable();
            $table->unsignedTinyInteger('place')->nullable()->comment('1:online, 2:onsite');
            $table->string('granted_by')->nullable();
            $table->text('notes')->nullable();
            $table->dateTime('start_date')->nullable();
            $table->dateTime('end_date')->nullable();
            $table->boolean('has_certificate')->default(false);
            $table->string('duration')->nullable();
            $table->decimal('cost', 10, 2)->nullable();
            $table->unsignedTinyInteger('status')->default(1)->comment('1:pending, 2:approved, 3:rejected, 4:in progress, 5:finished');
            $table->timestamps();
        });

        Schema::create('relatives', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('employee_id');
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('relation');
            $table->timestamps();
        });

        Schema::create('employee_items', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('employee_id');
            $table->string('item');
            $table->string('brand')->nullable();
            $table->string('model')->nullable();
            $table->string('serial_number')->nullable();
            $table->string('status')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('employee_notes', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('employee_id');
            $table->string('type', 20);
            $table->text('content')->nullable();
            $table->boolean('is_done')->default(false);
            $table->string('color', 9)->default('#111827');
            $table->unsignedInteger('order')->default(0);
            $table->timestamps();
            $table->index(['employee_id', 'order']);
        });

        Schema::create('evaluations', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('contract_id');
            $table->dateTime('date');
            $table->unsignedBigInteger('current_manager_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('badges', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('employee_id');
            $table->unsignedBigInteger('evaluation_id')->nullable();
            $table->unsignedTinyInteger('currency')->nullable()->comment('0 = Unknown, 1 = SYP, 2 = USD, 3 = EUR');
            $table->string('type');
            $table->dateTime('date');
            $table->text('reason')->nullable();
            $table->decimal('current_salary', 10, 2)->nullable();
            $table->decimal('new_salary', 10, 2)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->string('subtype')->nullable();
            $table->decimal('amount', 12, 2)->nullable();
            $table->string('new_position')->nullable();
            $table->string('leave_unit')->nullable();
        });

        Schema::create('policy_documents', function (Blueprint $table): void {
            $table->id();
            $table->string('source', 16)->default('drive')->comment('drive | panel');
            $table->string('external_id')->nullable()->comment('Google Drive file id');
            $table->string('title');
            $table->string('mime_type')->nullable();
            $table->string('source_url')->nullable();
            $table->longText('content')->nullable();
            $table->timestamp('source_modified_at')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();
            $table->unique('external_id');
        });

        Schema::create('termination_reasons', function (Blueprint $table): void {
            $table->id();
            $table->text('title_ar');
            $table->text('title_en')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('termination_reasons');
        Schema::dropIfExists('policy_documents');
        Schema::dropIfExists('badges');
        Schema::dropIfExists('evaluations');
        Schema::dropIfExists('employee_notes');
        Schema::dropIfExists('employee_items');
        Schema::dropIfExists('relatives');
        Schema::dropIfExists('courses');
        Schema::dropIfExists('course_categories');
        Schema::dropIfExists('experiences');
        Schema::dropIfExists('educations');
        Schema::dropIfExists('education_fields');
        Schema::dropIfExists('employees');
        Schema::dropIfExists('positions');
        Schema::dropIfExists('job_levels');
        Schema::dropIfExists('departments');
    }
};
