<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->onDelete('cascade');
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->foreignId('position_id')->constrained()->onDelete('restrict');
            $table->foreignId('contract_type_id')->constrained()->onDelete('restrict');
            $table->foreignId('department_id')->constrained()->onDelete('restrict');
            $table->foreignId('job_level_id')->constrained()->onDelete('restrict');
            $table->date('start_date');
            $table->date('end_date');
            $table->date('terminated_at')->nullable();
            $table->text('reason_of_termination')->nullable();
            $table->integer('yearly_leave_days')->default(12);
            $table->decimal('salary', 10, 2);
            $table->unsignedTinyInteger('currency')->comment('0 = Unknown, 1 = SYP, 2 = USD, 3 = EUR');
            $table->unsignedTinyInteger('salary_per')->comment('0=Unknown, 1=Month, 2=Hour, 3=Session')->default(0);
            $table->json('work_days');
            $table->json('work_hours');
            $table->integer('weekly_hours');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('contracts');
    }
};
