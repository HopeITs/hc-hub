<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * HR contracts tables, as they stood in the HR app (hchr_ prefix dropped).
 * Foreign keys are added together in the add_hr_foreign_keys migration so
 * the tables can be created in any order.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contract_types', function (Blueprint $table): void {
            $table->id();
            $table->string('name_en');
            $table->string('name_ar');
            $table->timestamps();
        });

        Schema::create('contracts', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('employee_id');
            $table->unsignedBigInteger('position_id');
            $table->unsignedBigInteger('contract_type_id');
            $table->unsignedTinyInteger('type')->default(1);
            $table->unsignedTinyInteger('place')->default(1);
            $table->unsignedTinyInteger('schedule_mode')->default(1);
            $table->dateTime('start_date')->nullable();
            $table->dateTime('end_date')->nullable();
            $table->unsignedBigInteger('termination_reason_id')->nullable();
            $table->dateTime('terminated_at')->nullable();
            $table->text('reason_of_termination')->nullable();
            $table->text('salary')->nullable();
            $table->unsignedTinyInteger('currency')->nullable();
            $table->unsignedTinyInteger('salary_per')->default(0)->comment('0=Unknown, 1=Month, 2=Hour, 3=Session');
            $table->unsignedTinyInteger('pay_mode')->default(1);
            $table->unsignedTinyInteger('session_duration')->nullable();
            $table->time('work_hours_from')->nullable()->default('09:30:00');
            $table->time('work_hours_to')->nullable()->default('17:00:00');
            $table->time('work_hours_2_from')->nullable();
            $table->time('work_hours_2_to')->nullable();
            $table->unsignedTinyInteger('work_day_from')->nullable();
            $table->unsignedTinyInteger('work_day_to')->nullable();
            $table->boolean('show_days_per_week')->default(false);
            $table->boolean('show_hours_per_week')->default(false);
            $table->boolean('show_hours_per_day')->default(false);
            $table->boolean('show_day_range')->default(false);
            $table->unsignedTinyInteger('flex_days_per_week')->nullable();
            $table->unsignedSmallInteger('flex_hours_per_week')->nullable();
            $table->unsignedSmallInteger('flex_hours_per_month')->nullable();
            $table->boolean('flex_varies_by_requirements')->default(false);
            $table->boolean('flex_coordinated_with_manager')->default(false);
            $table->string('flex_custom_en')->nullable();
            $table->string('flex_custom_ar')->nullable();
            $table->string('work_days_en')->nullable();
            $table->string('work_days_ar')->nullable();
            $table->integer('weekly_hours')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('auto_renew')->default(true);
            $table->timestamps();
        });

        Schema::create('contract_pay_tiers', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('contract_id');
            $table->string('condition_en');
            $table->string('condition_ar');
            $table->text('amount')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('contract_amendments', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('contract_id');
            $table->unsignedSmallInteger('number');
            $table->unsignedTinyInteger('status')->default(0);
            $table->date('effective_from');
            $table->text('reason')->nullable();
            $table->json('terms_before');
            $table->json('terms_after');
            $table->text('salary_before')->nullable();
            $table->text('salary_after')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('applied_at')->nullable();
            $table->timestamps();
            $table->unique(['contract_id', 'number']);
            $table->index(['status', 'effective_from']);
        });

        Schema::create('contract_amendment_pay_tiers', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('contract_amendment_id');
            $table->unsignedTinyInteger('side');
            $table->string('condition_en');
            $table->string('condition_ar');
            $table->text('amount')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('default_deliverables', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('employee_id');
            $table->unsignedBigInteger('city_id');
            $table->unsignedBigInteger('item_id');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('default_deliverables');
        Schema::dropIfExists('contract_amendment_pay_tiers');
        Schema::dropIfExists('contract_amendments');
        Schema::dropIfExists('contract_pay_tiers');
        Schema::dropIfExists('contracts');
        Schema::dropIfExists('contract_types');
    }
};
