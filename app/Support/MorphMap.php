<?php

namespace App\Support;

use App\Models\AnnualLeaveRule;
use App\Models\Attachment;
use App\Models\Badge;
use App\Models\BirthdayHolidayChoice;
use App\Models\Center;
use App\Models\Checklist;
use App\Models\ChecklistItem;
use App\Models\City;
use App\Models\Contract;
use App\Models\ContractPayTier;
use App\Models\ContractType;
use App\Models\Course;
use App\Models\CourseCategory;
use App\Models\DefaultDeliverable;
use App\Models\Department;
use App\Models\Education;
use App\Models\Employee;
use App\Models\EmployeeItem;
use App\Models\EmployeeNote;
use App\Models\Evaluation;
use App\Models\Experience;
use App\Models\Folder;
use App\Models\Holiday;
use App\Models\HolidayProject;
use App\Models\InAppFeedback;
use App\Models\JobLevel;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveRequestNote;
use App\Models\LeaveType;
use App\Models\News;
use App\Models\Permission;
use App\Models\Position;
use App\Models\ProfileChangeRequest;
use App\Models\Program;
use App\Models\Relative;
use App\Models\Role;
use App\Models\SavedTableView;
use App\Models\TaskAttempt;
use App\Models\TerminationReason;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * The hub's global, enforced morph map (Relation::enforceMorphMap).
 *
 * Every `*_type` column (model_has_roles, model_has_permissions, media,
 * activity_log subject/causer, notifications, news.newsable) stores the alias,
 * never the class name, so models can move between namespaces without
 * breaking stored rows.
 *
 * Enforcement is global: calling getMorphClass() on a model that is not listed
 * throws ClassMorphViolationException. Every HR model is listed, not just the
 * ones polymorphic today, so adding LogsActivity/HasMedia to a model later
 * cannot break it. A NEW model (any module) must get an alias here.
 *
 * Aliases are the snake_case class basename (tracking's existing aliases use
 * the same style, so its stored rows need no rewrite). A module model whose
 * basename collides with an existing alias takes a module prefix instead
 * (e.g. `media_folder`). Aliases are stored data: never rename one without a
 * data migration.
 */
final class MorphMap
{
    /** @var array<string, class-string<Model>> */
    public const MAP = [
        // Core / identity
        'user' => User::class,
        'role' => Role::class,
        'permission' => Permission::class,

        // HR master data
        'city' => City::class,
        'center' => Center::class,
        'program' => Program::class,
        'department' => Department::class,
        'position' => Position::class,
        'job_level' => JobLevel::class,

        // HR employees
        'employee' => Employee::class,
        'employee_item' => EmployeeItem::class,
        'employee_note' => EmployeeNote::class,
        'relative' => Relative::class,
        'education' => Education::class,
        'course' => Course::class,
        'course_category' => CourseCategory::class,
        'experience' => Experience::class,
        'evaluation' => Evaluation::class,
        'attachment' => Attachment::class,
        'profile_change_request' => ProfileChangeRequest::class,

        // HR contracts
        'contract' => Contract::class,
        'contract_type' => ContractType::class,
        'contract_pay_tier' => ContractPayTier::class,
        'badge' => Badge::class,
        'termination_reason' => TerminationReason::class,

        // HR leaves and holidays
        'leave_type' => LeaveType::class,
        'leave_balance' => LeaveBalance::class,
        'leave_request' => LeaveRequest::class,
        'leave_request_note' => LeaveRequestNote::class,
        'annual_leave_rule' => AnnualLeaveRule::class,
        'holiday' => Holiday::class,
        'holiday_project' => HolidayProject::class,
        'birthday_holiday_choice' => BirthdayHolidayChoice::class,

        // HR checklists, archive, news, misc
        'checklist' => Checklist::class,
        'checklist_item' => ChecklistItem::class,
        'default_deliverable' => DefaultDeliverable::class,
        'task_attempt' => TaskAttempt::class,
        'folder' => Folder::class,
        'news' => News::class,
        'in_app_feedback' => InAppFeedback::class,
        'saved_table_view' => SavedTableView::class,
    ];
}
