<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Rewrites stored class names in every polymorphic `*_type` column to the
 * aliases of the hub's enforced morph map (App\Support\MorphMap).
 *
 * HR wrote full class names (`App\Models\Employee`) before the map existed.
 * With enforceMorphMap() active, relations look up and write the alias
 * (`employee`), so without this rewrite every existing role assignment,
 * media item, activity entry, notification and targeted news item would stop
 * resolving.
 *
 * The map is frozen here on purpose: a migration must keep meaning the same
 * thing even after App\Support\MorphMap grows.
 */
return new class extends Migration
{
    /** @var array<string, string> alias => class name as HR stored it */
    private const MAP = [
        'user' => 'App\\Models\\User',
        'role' => 'App\\Models\\Role',
        'permission' => 'App\\Models\\Permission',
        'city' => 'App\\Models\\City',
        'center' => 'App\\Models\\Center',
        'program' => 'App\\Models\\Program',
        'department' => 'App\\Models\\Department',
        'position' => 'App\\Models\\Position',
        'job_level' => 'App\\Models\\JobLevel',
        'employee' => 'App\\Models\\Employee',
        'employee_item' => 'App\\Models\\EmployeeItem',
        'employee_note' => 'App\\Models\\EmployeeNote',
        'relative' => 'App\\Models\\Relative',
        'education' => 'App\\Models\\Education',
        'course' => 'App\\Models\\Course',
        'course_category' => 'App\\Models\\CourseCategory',
        'experience' => 'App\\Models\\Experience',
        'evaluation' => 'App\\Models\\Evaluation',
        'attachment' => 'App\\Models\\Attachment',
        'profile_change_request' => 'App\\Models\\ProfileChangeRequest',
        'contract' => 'App\\Models\\Contract',
        'contract_type' => 'App\\Models\\ContractType',
        'contract_pay_tier' => 'App\\Models\\ContractPayTier',
        'badge' => 'App\\Models\\Badge',
        'termination_reason' => 'App\\Models\\TerminationReason',
        'leave_type' => 'App\\Models\\LeaveType',
        'leave_balance' => 'App\\Models\\LeaveBalance',
        'leave_request' => 'App\\Models\\LeaveRequest',
        'leave_request_note' => 'App\\Models\\LeaveRequestNote',
        'annual_leave_rule' => 'App\\Models\\AnnualLeaveRule',
        'holiday' => 'App\\Models\\Holiday',
        'holiday_project' => 'App\\Models\\HolidayProject',
        'birthday_holiday_choice' => 'App\\Models\\BirthdayHolidayChoice',
        'checklist' => 'App\\Models\\Checklist',
        'checklist_item' => 'App\\Models\\ChecklistItem',
        'default_deliverable' => 'App\\Models\\DefaultDeliverable',
        'task_attempt' => 'App\\Models\\TaskAttempt',
        'folder' => 'App\\Models\\Folder',
        'news' => 'App\\Models\\News',
        'in_app_feedback' => 'App\\Models\\InAppFeedback',
        'saved_table_view' => 'App\\Models\\SavedTableView',
    ];

    /** @var array<string, list<string>> table => morph type columns */
    private const COLUMNS = [
        'model_has_roles' => ['model_type'],
        'model_has_permissions' => ['model_type'],
        'media' => ['model_type'],
        'activity_log' => ['subject_type', 'causer_type'],
        'notifications' => ['notifiable_type'],
        'news' => ['newsable_type'],
    ];

    public function up(): void
    {
        foreach (self::MAP as $alias => $class) {
            $this->rewrite($class, $alias);
        }
    }

    public function down(): void
    {
        foreach (self::MAP as $alias => $class) {
            $this->rewrite($alias, $class);
        }
    }

    private function rewrite(string $from, string $to): void
    {
        foreach (self::COLUMNS as $table => $columns) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            foreach ($columns as $column) {
                if (! Schema::hasColumn($table, $column)) {
                    continue;
                }

                DB::table($table)->where($column, $from)->update([$column => $to]);
            }
        }
    }
};
