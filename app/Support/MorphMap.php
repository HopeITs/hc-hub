<?php

namespace App\Support;

use App\Models\Center;
use App\Models\City;
use App\Models\Department;
use App\Models\InAppFeedback;
use App\Models\KnownDevice;
use App\Models\NotificationMute;
use App\Models\Permission;
use App\Models\Program;
use App\Models\Role;
use App\Models\SavedTableView;
use App\Models\TaskAttempt;
use App\Models\TrustedDevice;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Database\Eloquent\Model;
use Laravel\Passkeys\Passkey;
use Modules\Hr\Models\AnnualLeaveRule;
use Modules\Hr\Models\Attachment;
use Modules\Hr\Models\Badge;
use Modules\Hr\Models\BirthdayHolidayChoice;
use Modules\Hr\Models\Checklist;
use Modules\Hr\Models\ChecklistItem;
use Modules\Hr\Models\Contract;
use Modules\Hr\Models\ContractPayTier;
use Modules\Hr\Models\ContractType;
use Modules\Hr\Models\Course;
use Modules\Hr\Models\CourseCategory;
use Modules\Hr\Models\DefaultDeliverable;
use Modules\Hr\Models\Education;
use Modules\Hr\Models\Employee;
use Modules\Hr\Models\EmployeeItem;
use Modules\Hr\Models\EmployeeNote;
use Modules\Hr\Models\Evaluation;
use Modules\Hr\Models\Experience;
use Modules\Hr\Models\Folder;
use Modules\Hr\Models\Holiday;
use Modules\Hr\Models\HolidayProject;
use Modules\Hr\Models\JobLevel;
use Modules\Hr\Models\LeaveBalance;
use Modules\Hr\Models\LeaveRequest;
use Modules\Hr\Models\LeaveRequestNote;
use Modules\Hr\Models\LeaveType;
use Modules\Hr\Models\News;
use Modules\Hr\Models\Position;
use Modules\Hr\Models\ProfileChangeRequest;
use Modules\Hr\Models\Relative;
use Modules\Hr\Models\TerminationReason;

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

        // Sign-in (task 1.1): Fortify's passkeys, trusted and known browsers
        'passkey' => Passkey::class,
        'trusted_device' => TrustedDevice::class,
        'known_device' => KnownDevice::class,

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

        // Hub inbox (notifications)
        'user_notification' => UserNotification::class,
        'notification_mute' => NotificationMute::class,
    ];
}
