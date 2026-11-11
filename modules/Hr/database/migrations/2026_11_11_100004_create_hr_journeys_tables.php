<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * HR journeys tables, as they stood in the HR app (hchr_ prefix dropped).
 * Foreign keys are added together in the add_hr_foreign_keys migration so
 * the tables can be created in any order.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('onboarding_journeys', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('employee_id');
            $table->unsignedBigInteger('contract_id')->nullable();
            $table->timestamp('started_at');
            $table->date('orientation_due_on');
            $table->date('orientation_scheduled_for')->nullable();
            $table->unsignedBigInteger('orientation_scheduled_by')->nullable();
            $table->timestamp('orientation_completed_at')->nullable();
            $table->unsignedBigInteger('orientation_completed_by')->nullable();
            $table->timestamp('card_handed_over_at')->nullable();
            $table->unsignedBigInteger('card_handed_over_by')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->index('completed_at');
            $table->unique('employee_id');
        });

        Schema::create('offboarding_journeys', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('employee_id');
            $table->unsignedBigInteger('contract_id')->nullable();
            $table->string('trigger', 32);
            $table->timestamp('started_at');
            $table->unsignedBigInteger('started_by')->nullable();
            $table->timestamp('exit_interview_held_at')->nullable();
            $table->unsignedBigInteger('exit_interview_by')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->index('completed_at');
            $table->unique(['employee_id', 'contract_id']);
        });

        Schema::create('renewal_journeys', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('contract_id');
            $table->unsignedBigInteger('employee_id');
            $table->timestamp('started_at');
            $table->string('decision', 16)->nullable();
            $table->unsignedBigInteger('decided_by')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->boolean('decision_switched_auto_renew')->default(false);
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->index('completed_at');
            $table->unique('contract_id');
            $table->index(['employee_id', 'completed_at']);
        });

        Schema::create('profile_change_requests', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('employee_id');
            $table->string('section', 20);
            $table->json('proposed');
            $table->unsignedTinyInteger('status')->default(0);
            $table->unsignedBigInteger('requested_by')->nullable();
            $table->unsignedBigInteger('decided_by')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->text('reason')->nullable();
            $table->timestamps();
            $table->index(['employee_id', 'section', 'status']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profile_change_requests');
        Schema::dropIfExists('renewal_journeys');
        Schema::dropIfExists('offboarding_journeys');
        Schema::dropIfExists('onboarding_journeys');
    }
};
