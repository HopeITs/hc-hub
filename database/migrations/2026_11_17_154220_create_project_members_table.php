<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Who takes part in a project, in what role and when (roadmap 2.2, D4). A
 * member is an employee (employees.id); added_by / ended_by are the logins
 * that acted. Ending a membership fills ends_on; rows are never deleted.
 *
 * `is_open` is 1 while ends_on is null and NULL once it is set, so the unique
 * index allows one open row per (project, employee, role) and any number of
 * ended ones: the same trick as supervisions.pair_low/pair_high. A null
 * starts_on means the start is not known (an inferred row), never "today".
 *
 * project_id cascades rather than restricts (02-target-design.md drew
 * restrict): projects are archived, not deleted, but deleting a program
 * cascades to its projects, and a restricting team row would block that.
 * employee_id restricts: employees are archived, so a member's history stays.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_members', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->restrictOnDelete();
            $table->string('role', 30);
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->foreignId('added_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('ended_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('source', 20);
            $table->timestamps();
            $table->unsignedTinyInteger('is_open')->nullable()->storedAs('IF(`ends_on` IS NULL, 1, NULL)');

            $table->unique(['project_id', 'employee_id', 'role', 'is_open'], 'project_members_open_unique');
            $table->index(['project_id', 'ends_on'], 'project_members_project_ends_idx');
            $table->index(['employee_id', 'ends_on'], 'project_members_employee_ends_idx');
        });

        DB::statement('ALTER TABLE `project_members` ADD CONSTRAINT `project_members_dates_check` CHECK (`ends_on` IS NULL OR `starts_on` IS NULL OR `ends_on` >= `starts_on`)');
    }

    public function down(): void
    {
        Schema::dropIfExists('project_members');
    }
};
