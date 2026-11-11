<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * HR leave tables, as they stood in the HR app (hchr_ prefix dropped).
 * Foreign keys are added together in the add_hr_foreign_keys migration so
 * the tables can be created in any order.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('annual_leave_rules', function (Blueprint $table): void {
            $table->id();
            $table->integer('days')->nullable();
            $table->double('day_per_month')->nullable();
            $table->integer('years_of_volunteering')->nullable();
            $table->timestamps();
        });

        Schema::create('leave_types', function (Blueprint $table): void {
            $table->id();
            $table->string('name_en');
            $table->string('name_ar');
            $table->integer('base_days')->default(0);
            $table->unsignedTinyInteger('allowed_gender')->nullable();
            $table->unsignedTinyInteger('marital_status')->nullable();
            $table->timestamps();
        });

        Schema::create('leave_balances', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('contract_id');
            $table->unsignedBigInteger('type_id');
            $table->integer('deserved');
            $table->double('used')->default('0');
            $table->double('amendment_offset')->default('0');
            $table->timestamps();
        });

        Schema::create('leave_requests', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('leave_balance_id');
            $table->enum('type', ['day', 'hour'])->default('day');
            $table->dateTime('start_at')->default('2026-06-09 00:00:00');
            $table->dateTime('end_at')->default('2026-06-09 00:00:00');
            $table->decimal('computed_hours', 8, 2)->nullable();
            $table->string('rule_version', 32)->nullable();
            $table->boolean('is_exceptional')->default(false);
            $table->text('reason')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->string('decision_basis', 16)->nullable();
            $table->unsignedBigInteger('decided_on_behalf_of')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->boolean('hr_approved')->default(false);
            $table->text('reject_reason')->nullable();
            $table->tinyInteger('status')->default(0)->comment('0:pending, 1:approved , 2:rejected');
            $table->unsignedBigInteger('alternative_employee_id')->nullable();
            $table->timestamps();
        });

        Schema::create('leave_request_notes', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('leave_request_id');
            $table->unsignedBigInteger('author_employee_id')->nullable();
            $table->string('author_role', 32)->nullable();
            $table->string('kind', 16);
            $table->text('body');
            $table->timestamps();
            $table->index(['leave_request_id', 'created_at']);
        });

        Schema::create('holidays', function (Blueprint $table): void {
            $table->id();
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('is_recurring')->default(false);
            $table->timestamps();
            $table->string('name_en')->default('');
            $table->string('name_ar')->default('');
            $table->string('scope', 16)->default('national');
            $table->string('kind', 16)->default('official');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->index('kind');
            $table->index('scope');
        });

        Schema::create('holiday_project', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('holiday_id');
            $table->unsignedBigInteger('program_id');
            $table->unsignedBigInteger('city_id');
            $table->unique(['holiday_id', 'program_id', 'city_id']);
            $table->index(['program_id', 'city_id']);
        });

        Schema::create('birthday_holiday_choices', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('holiday_id');
            $table->unsignedBigInteger('employee_id');
            $table->unsignedSmallInteger('year');
            $table->date('chosen_date');
            $table->unsignedTinyInteger('status')->default(0);
            $table->unsignedBigInteger('decided_by')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();
            $table->unique(['holiday_id', 'employee_id', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('birthday_holiday_choices');
        Schema::dropIfExists('holiday_project');
        Schema::dropIfExists('holidays');
        Schema::dropIfExists('leave_request_notes');
        Schema::dropIfExists('leave_requests');
        Schema::dropIfExists('leave_balances');
        Schema::dropIfExists('leave_types');
        Schema::dropIfExists('annual_leave_rules');
    }
};
