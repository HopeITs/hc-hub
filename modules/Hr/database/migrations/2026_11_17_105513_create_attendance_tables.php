<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Attendance from the device report, as the HR app keeps it:
 *  - attendance_imports: one row per uploaded device report;
 *  - attendance_punches: every punch once (device number + minute is unique), so the same or an
 *    overlapping file can be uploaded again; punches of a locked month are not taken in;
 *  - attendance_device_links: a device number tied to an employee by hand;
 *  - attendance_day_decisions: the answer for a working day with no record, or one punch;
 *  - attendance_months: one person's month (confirmed, approved, locked). The signed paper is an
 *    hr_media row of model type App\Models\AttendanceMonth, as in the HR app.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_imports', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('file_name');
            $table->date('period_start')->nullable();
            $table->date('period_end')->nullable();
            $table->unsignedInteger('punches_total')->default(0);
            $table->unsignedInteger('punches_new')->default(0);
            $table->unsignedInteger('punches_locked')->default(0);
            $table->unsignedInteger('device_numbers')->default(0);
            $table->timestamps();
        });

        Schema::create('attendance_punches', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('import_id')->constrained('attendance_imports')->cascadeOnDelete();
            $table->string('device_no', 32);
            $table->foreignId('employee_id')->nullable()->constrained()->nullOnDelete();
            $table->dateTime('punched_at');
            $table->timestamp('created_at')->nullable();

            $table->unique(['device_no', 'punched_at']);
            $table->index(['employee_id', 'punched_at']);
        });

        Schema::create('attendance_device_links', function (Blueprint $table): void {
            $table->id();
            $table->string('device_no', 32)->unique();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('attendance_day_decisions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->date('work_date');
            $table->string('kind', 16);
            $table->string('note')->nullable();
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['employee_id', 'work_date']);
        });

        Schema::create('attendance_months', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->date('month');
            $table->timestamp('employee_confirmed_at')->nullable();
            $table->foreignId('employee_confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('overtime_hours_approved', 6, 2)->default(0);
            $table->timestamp('locked_at')->nullable();
            $table->foreignId('locked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['employee_id', 'month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_months');
        Schema::dropIfExists('attendance_day_decisions');
        Schema::dropIfExists('attendance_device_links');
        Schema::dropIfExists('attendance_punches');
        Schema::dropIfExists('attendance_imports');
    }
};
