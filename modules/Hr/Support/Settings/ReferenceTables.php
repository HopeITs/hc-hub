<?php

declare(strict_types=1);

namespace Modules\Hr\Support\Settings;

use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use Modules\Hr\Enums\ChecklistItemOrder;
use Modules\Hr\Enums\LeaveType as LeaveTypeRules;
use Modules\Hr\Models\AnnualLeaveRule;
use Modules\Hr\Models\ChecklistItem;
use Modules\Hr\Models\ContractType;
use Modules\Hr\Models\CourseCategory;
use Modules\Hr\Models\Department;
use Modules\Hr\Models\JobLevel;
use Modules\Hr\Models\LeaveType;
use Modules\Hr\Models\TerminationReason;

/**
 * The small reference tables HR settings manages, described once: their
 * fields, validation, list columns, search columns and the tables that hold
 * a row in use. The settings tab page, SaveReferenceRow and
 * DeleteReferenceRow all read this, so a table is added here and nowhere
 * else. (Cities are Core and have their own tab; holidays their own page.)
 *
 * Field types: text, textarea, email, integer, decimal, select.
 * Labels live in lang hr_settings.fields.* and option labels in
 * hr_settings.options.<field>.<value>.
 *
 * @phpstan-type Field array{type: string, rules: list<mixed>, unique?: bool, options?: list<int>, dir?: string}
 * @phpstan-type Definition array{model: class-string<Model>, label: string, order: array{0: string, 1: string}, search: list<string>, columns: list<string>, fields: array<string, Field>, in_use: list<array{0: string, 1: string}>, paginate?: int}
 */
final class ReferenceTables
{
    /** @return list<string> */
    public static function keys(): array
    {
        return array_keys(self::all());
    }

    public static function has(string $key): bool
    {
        return array_key_exists($key, self::all());
    }

    /** @return Definition */
    public static function get(string $key): array
    {
        return self::all()[$key] ?? throw new InvalidArgumentException("Unknown HR reference table [{$key}].");
    }

    /**
     * @return array<string, Definition>
     */
    public static function all(): array
    {
        $name = ['type' => 'text', 'rules' => ['required', 'string', 'max:255']];

        return [
            'contract-types' => [
                'model' => ContractType::class,
                'label' => 'name_en',
                'order' => ['id', 'asc'],
                'search' => ['name_en', 'name_ar'],
                'columns' => ['name_ar', 'name_en'],
                'fields' => ['name_ar' => $name + ['dir' => 'rtl'], 'name_en' => $name + ['dir' => 'ltr']],
                'in_use' => [['contracts', 'contract_type_id']],
            ],
            'leave-types' => [
                'model' => LeaveType::class,
                'label' => 'name_en',
                'order' => ['id', 'asc'],
                'search' => ['name_en', 'name_ar'],
                'columns' => ['name_ar', 'name_en', 'base_days', 'allowed_gender', 'marital_status'],
                'fields' => [
                    'name_ar' => $name + ['dir' => 'rtl'],
                    'name_en' => $name + ['dir' => 'ltr'],
                    'base_days' => ['type' => 'integer', 'rules' => ['required', 'integer', 'min:0', 'max:366']],
                    'allowed_gender' => [
                        'type' => 'select',
                        'rules' => ['required', 'integer'],
                        'options' => [LeaveTypeRules::GENDER_ANY, LeaveTypeRules::GENDER_MALE, LeaveTypeRules::GENDER_FEMALE],
                    ],
                    'marital_status' => [
                        'type' => 'select',
                        'rules' => ['required', 'integer'],
                        'options' => [LeaveTypeRules::MARITAL_STATUS_ANY, LeaveTypeRules::MARITAL_STATUS_SINGLE, LeaveTypeRules::MARITAL_STATUS_MARRIED],
                    ],
                ],
                'in_use' => [['leave_balances', 'type_id']],
            ],
            'annual-leave-rules' => [
                'model' => AnnualLeaveRule::class,
                'label' => 'years_of_volunteering',
                'order' => ['years_of_volunteering', 'asc'],
                'search' => [],
                'columns' => ['years_of_volunteering', 'days', 'day_per_month'],
                'fields' => [
                    'years_of_volunteering' => ['type' => 'integer', 'rules' => ['required', 'integer', 'min:0', 'max:100']],
                    'days' => ['type' => 'integer', 'rules' => ['required', 'integer', 'min:0', 'max:366']],
                    'day_per_month' => ['type' => 'decimal', 'rules' => ['required', 'numeric', 'min:0', 'max:31']],
                ],
                'in_use' => [],
            ],
            'termination-reasons' => [
                'model' => TerminationReason::class,
                'label' => 'title_en',
                'order' => ['id', 'asc'],
                'search' => ['title_en', 'title_ar'],
                'columns' => ['title_ar', 'title_en'],
                'fields' => ['title_ar' => $name + ['dir' => 'rtl'], 'title_en' => $name + ['dir' => 'ltr']],
                'in_use' => [['contracts', 'termination_reason_id']],
            ],
            'checklist-items' => [
                'model' => ChecklistItem::class,
                'label' => 'name',
                'order' => ['order', 'asc'],
                'search' => ['name'],
                'columns' => ['name', 'order'],
                'fields' => [
                    'name' => $name,
                    'order' => [
                        'type' => 'select',
                        'rules' => ['required', 'integer'],
                        'options' => [
                            ChecklistItemOrder::ProbationContract->value,
                            ChecklistItemOrder::AnnualContract->value,
                            ChecklistItemOrder::Resignation->value,
                        ],
                    ],
                ],
                'in_use' => [['checklists', 'item_id'], ['default_deliverables', 'item_id']],
            ],
            'job-levels' => [
                'model' => JobLevel::class,
                'label' => 'name_en',
                'order' => ['grade', 'asc'],
                'search' => ['name_en', 'name_ar', 'email'],
                'columns' => ['name_ar', 'name_en', 'grade', 'email'],
                'fields' => [
                    'name_ar' => $name + ['dir' => 'rtl'],
                    'name_en' => $name + ['dir' => 'ltr'],
                    'grade' => ['type' => 'integer', 'rules' => ['nullable', 'integer', 'min:0', 'max:99']],
                    'email' => ['type' => 'email', 'rules' => ['nullable', 'email', 'max:255'], 'unique' => true],
                    'description' => ['type' => 'textarea', 'rules' => ['nullable', 'string', 'max:2000']],
                ],
                'in_use' => [['positions', 'job_level_id']],
            ],
            'departments' => [
                'model' => Department::class,
                'label' => 'name',
                'order' => ['name', 'asc'],
                'search' => ['name', 'code', 'email'],
                'columns' => ['name', 'code', 'email'],
                'fields' => [
                    'name' => $name,
                    'code' => ['type' => 'text', 'rules' => ['required', 'string', 'max:5'], 'unique' => true, 'dir' => 'ltr'],
                    'email' => ['type' => 'email', 'rules' => ['nullable', 'email', 'max:255']],
                    'description_ar' => ['type' => 'textarea', 'rules' => ['nullable', 'string', 'max:2000'], 'dir' => 'rtl'],
                    'description_en' => ['type' => 'textarea', 'rules' => ['nullable', 'string', 'max:2000'], 'dir' => 'ltr'],
                ],
                'in_use' => [['positions', 'department_id']],
            ],
            'course-categories' => [
                'model' => CourseCategory::class,
                'label' => 'name_en',
                'order' => ['name_en', 'asc'],
                'search' => ['name_en', 'name_ar'],
                'columns' => ['name_ar', 'name_en'],
                'fields' => ['name_ar' => $name + ['dir' => 'rtl'], 'name_en' => $name + ['dir' => 'ltr']],
                'in_use' => [['courses', 'category_id']],
                'paginate' => 25,
            ],
        ];
    }

    /**
     * The words a select field shows for a stored value.
     */
    public static function optionLabel(string $field, mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        $key = "hr_settings.options.{$field}.".(int) $value;
        $label = __($key);

        return $label === $key ? (string) $value : (string) $label;
    }
}
