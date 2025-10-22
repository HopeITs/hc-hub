<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        //basics
        Schema::create('cities', function (Blueprint $table) {
            $table->id();
            $table->string('name_en');
            $table->string('name_ar');
            $table->char('slug')->unique();
            $table->char('code')->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::create('programs', function (Blueprint $table) {
            $table->id();
            $table->text('name');
            $table->text('description_en')->nullable();
            $table->text('description_ar')->nullable();
            $table->char('code');
            $table->char('slug')->unique();
            $table->dateTime('start_date')->nullable();
            $table->dateTime('end_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::create('centers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('weekends')->nullable();
            $table->foreignId('city_id')->nullable()->constrained()->onDelete('restrict');
            $table->text('address_en')->nullable();
            $table->text('address_ar')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::create('contract_types', function (Blueprint $table) {
            $table->id();
            $table->string('name_en');
            $table->string('name_ar');
            $table->unsignedTinyInteger('place')->default(1);
            $table->unsignedTinyInteger('type')->default(1);
            $table->timestamps();
        });
        Schema::create('leave_types', function (Blueprint $table) {
            $table->id();
            $table->string('name_en');
            $table->string('name_ar');
            $table->integer('base_days')->default(0);
            $table->unsignedTinyInteger('allowed_gender')->nullable();
            $table->unsignedTinyInteger('marital_status')->nullable();
            $table->timestamps();
        });
        Schema::create('holidays', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable(); // اسم العطلة
            $table->date('start_date');
            $table->date('end_date');
            $table->morphs('holidayable'); // center, city, program, إلخ
            $table->boolean('is_recurring')->default(false); //for the holidays used always
            $table->timestamps();
        });
        Schema::create('checklist_items', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->integer('order');
            $table->timestamps();
        });
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description_en')->nullable();
            $table->text('description_ar')->nullable();
            $table->string('email')->unique()->nullable();
            $table->timestamps();
        });
        Schema::create('job_levels', function (Blueprint $table) {
            $table->id();
            $table->string('name_en');
            $table->string('name_ar');
            $table->text('description')->nullable();
            $table->integer('grade')->nullable();
            $table->string('email')->nullable();
            $table->timestamps();
        });
        Schema::create('positions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_id')->constrained()->onDelete('cascade');
            $table->foreignId('center_id')->nullable()->constrained()->onDelete('restrict');
            $table->foreignId('city_id')->default(2)->constrained()->onDelete('restrict');
            $table->foreignId('job_level_id')->constrained()->onDelete('restrict');
            $table->foreignId('department_id')->constrained()->onDelete('restrict');
            $table->foreignId('direct_manager_id')->nullable()->constrained('positions')->onDelete('set null');
            $table->foreignId('tech_manager_id')->nullable()->constrained('positions')->onDelete('set null');
            $table->string('title_en');
            $table->string('title_ar');
            $table->text('qualifications')->nullable();
            $table->text('responsibilities')->nullable();
            $table->integer('max_emp')->default(1);
            $table->integer('exist_emp')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::create('annual_leave_rules', function (Blueprint $table) {
            $table->id();
            $table->integer('days')->nullable();
            $table->float('day_per_month')->nullable();
            $table->integer('years_of_volunteering')->nullable();
            $table->timestamps();
        });

        //employee
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('city_id')->nullable()->constrained('cities')->onDelete('set null');
            $table->string('name_en');
            $table->string('name_ar');
            $table->string('username')->unique();
            $table->string('actual_job')->nullable();
            $table->double('years_of_service')->nullable();
            $table->char('national_number')->unique()->nullable();
            $table->dateTime('actual_dob')->nullable();
            $table->dateTime('dob')->nullable();
            $table->string('place_and_birth_date')->nullable();
            $table->string('civil_registry_office')->nullable();
            $table->string('civil_registry_number')->nullable();
            $table->string('place_of_registration')->nullable();
            $table->unsignedTinyInteger('gender')->nullable();
            $table->string('father_name_en')->nullable();
            $table->string('father_name_ar')->nullable();
            $table->string('mother_name_en')->nullable();
            $table->string('mother_name_ar')->nullable();
            $table->tinyInteger('nationality')->nullable();
            $table->unsignedTinyInteger('marital_status')->nullable();
            $table->integer('children')->default(0)->nullable();
            $table->string('work_phone')->nullable();
            $table->string('personal_phone')->nullable();
            $table->string('personal_email')->nullable();
            $table->text('address_en')->nullable();
            $table->text('address_ar')->nullable();
            $table->string('skills')->nullable();
            $table->string('interests')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_board_member')->default(false);
            $table->dateTime('board_member_started_at')->nullable();
            $table->dateTime('board_member_end_at')->nullable();
            $table->timestamps();
        });
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->string('issuing_organization');
            $table->dateTime('issue_date')->nullable();
            $table->boolean('is_approved')->default(false);
            $table->timestamps();
        });
        Schema::create('educations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->string('university');
            $table->unsignedTinyInteger('type')->nullable()->comment('0 = Unknown, 1 = Primary_School, 2 = High_School, 3 = Institute, 4 = University, 5 = Master, 6 = PHD');
            $table->year('graduation_year')->nullable();
            $table->timestamps();
        });
        Schema::create('experiences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->onDelete('cascade');
            $table->string('job_title');
            $table->string('company');
            $table->dateTime('start_date');
            $table->dateTime('end_date')->nullable();
            $table->tinyInteger('place')->nullable();
            $table->tinyInteger('type')->nullable();
            $table->timestamps();
        });
        Schema::create('relatives', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('relation');
            $table->timestamps();
        });
        Schema::create('checklists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->nullable()->onDelete('restrict');
            $table->foreignId('employee_id')->constrained()->onDelete('cascade');
            $table->foreignId('delivered_id')->constrained('employees')->onDelete('cascade');
            $table->string('brand')->nullable();
            $table->string('model')->nullable();
            $table->string('serial')->nullable();
            $table->dateTime('delivered_at')->nullable();
            $table->string('delivered_status')->nullable();
            $table->dateTime('returned_at')->nullable();
            $table->string('returned_status')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
        Schema::create('default_deliverables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->foreignId('city_id')->constrained('cities')->onDelete('restrict');
            $table->foreignId('item_id')->constrained('checklist_items')->onDelete('restrict');
            $table->timestamps();
        });

        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->onDelete('cascade');
            $table->foreignId('position_id')->constrained()->onDelete('cascade');
            $table->foreignId('contract_type_id')->constrained()->onDelete('restrict');
            $table->dateTime('start_date');
            $table->dateTime('end_date');
            $table->dateTime('terminated_at')->nullable();
            $table->text('reason_of_termination')->nullable();
            $table->decimal('salary', 10, 2);
            $table->unsignedTinyInteger('currency')->comment('0 = Unknown, 1 = SYP, 2 = USD, 3 = EUR');
            $table->unsignedTinyInteger('salary_per')->comment('0=Unknown, 1=Month, 2=Hour, 3=Session')->default(0);
            $table->json('work_days');
            $table->json('work_hours');
            $table->integer('weekly_hours');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::create('evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained('contracts')->onDelete('restrict');
            $table->dateTime('date');
            $table->foreignId('current_manager_id')->nullable()->constrained('employees')->onDelete('set null');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
        Schema::create('badges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->onDelete('cascade');
            $table->foreignId('evaluation_id')->nullable()->constrained()->onDelete('restrict');
            $table->unsignedTinyInteger('currency')->comment('0 = Unknown, 1 = SYP, 2 = USD, 3 = EUR')->nullable();
            $table->string('type');
            $table->dateTime('date');
            $table->text('reason')->nullable();
            $table->decimal('current_salary', 10, 2)->nullable();
            $table->decimal('new_salary', 10, 2)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
        Schema::create('leave_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained()->onDelete('cascade');
            $table->foreignId('type_id')->constrained('leave_types')->onDelete('restrict');
            $table->integer('deserved');
            $table->integer('used')->default(0);
            $table->timestamps();
        });
        Schema::create('leave_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('leave_balance_id')->constrained()->onDelete('restrict');
            $table->enum('type' , ['day', 'hour'])->default('day');
            $table->dateTime('start_at')->default(today());
            $table->dateTime('end_at')->default(today());
            $table->text('reason')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('employees')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->boolean('hr_approved')->default(false);
            $table->tinyInteger('status')->default(0)->comment('0:pending, 1:approved , 2:rejected');
            $table->foreignId('alternative_employee')->nullable()->constrained('employees')->onDelete('set null');
            $table->timestamps();
        });

        Schema::create('attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('employee_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->onDelete('cascade');
            $table->string('item');
            $table->string('brand')->nullable();
            $table->string('model')->nullable();
            $table->string('serial_number')->nullable();
            $table->string('status')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('employee_notes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id');
            $table->string('type', 20);
            $table->text('content')->nullable();
            $table->boolean('is_done')->default(false);
            $table->string('color', 9)->default('#111827');
            $table->unsignedInteger('order')->default(0);
            $table->timestamps();
            $table->index(['employee_id', 'order']);
            $table->foreign('employee_id')->references('id')->on('employees')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cities');
        Schema::dropIfExists('programs');
        Schema::dropIfExists('centers');
        Schema::dropIfExists('holidays');
        Schema::dropIfExists('contract_types');
        Schema::dropIfExists('leave_types');
        Schema::dropIfExists('checklist_items');
        Schema::dropIfExists('departments');
        Schema::dropIfExists('job_levels');
        Schema::dropIfExists('positions');
        Schema::dropIfExists('annual_leave_rules');

        Schema::dropIfExists('employees');
        Schema::dropIfExists('courses');
        Schema::dropIfExists('educations');
        Schema::dropIfExists('experiences');
        Schema::dropIfExists('relatives');
        Schema::dropIfExists('checklist');
        Schema::dropIfExists('default_deliverables');

        Schema::dropIfExists('contracts');
        Schema::dropIfExists('evaluations');
        Schema::dropIfExists('badges');
        Schema::dropIfExists('leave_balances');
        Schema::dropIfExists('leave_requests');
        Schema::dropIfExists('attachments');
        Schema::dropIfExists('employee_items');
        Schema::dropIfExists('employee_notes');

    }
};
