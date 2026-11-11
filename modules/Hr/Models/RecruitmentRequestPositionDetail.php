<?php

declare(strict_types=1);

namespace Modules\Hr\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\Center;
use Modules\Core\Models\Program;

/**
 * @property int $id
 * @property int $request_version_id
 * @property int|null $position_id
 * @property int|null $program_id
 * @property int|null $center_id
 * @property int|null $department_id
 * @property int|null $job_level_id
 * @property int|null $contract_type_id
 * @property int|null $direct_supervisor_position_id
 * @property int|null $current_headcount
 * @property int|null $requested_headcount
 * @property string|null $recruitment_reason
 * @property string|null $other_reason
 * @property string|null $role_title_en
 * @property string|null $role_title_ar
 * @property CarbonImmutable|null $volunteer_period_start_date
 * @property CarbonImmutable|null $volunteer_period_end_date
 * @property CarbonImmutable|null $expected_start_date
 * @property int|null $weekly_hours
 * @property string|null $priority
 * @property string|null $volunteer_type
 * @property int|null $salary_amount
 * @property int|null $currency
 * @property int|null $salary_per
 * @property string|null $main_duties
 * @property string|null $required_qualifications
 * @property int|null $required_experience_years
 * @property string|null $required_skills
 * @property string|null $detailed_explanation
 * @property bool $role_description_ready
 * @property bool $finance_consulted
 * @property bool $org_chart_updated
 * @property string|null $reason
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
class RecruitmentRequestPositionDetail extends Model
{
    protected $guarded = ['id'];

    /**
     * @return BelongsTo<RecruitmentRequestVersion, $this>
     */
    public function requestVersion(): BelongsTo
    {
        return $this->belongsTo(RecruitmentRequestVersion::class, 'request_version_id');
    }

    /**
     * @return BelongsTo<Position, $this>
     */
    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    /**
     * @return BelongsTo<Program, $this>
     */
    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    /**
     * @return BelongsTo<Center, $this>
     */
    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class);
    }

    /**
     * @return BelongsTo<Department, $this>
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * @return BelongsTo<JobLevel, $this>
     */
    public function jobLevel(): BelongsTo
    {
        return $this->belongsTo(JobLevel::class);
    }

    /**
     * @return BelongsTo<ContractType, $this>
     */
    public function contractType(): BelongsTo
    {
        return $this->belongsTo(ContractType::class);
    }

    /**
     * @return BelongsTo<Position, $this>
     */
    public function directSupervisorPosition(): BelongsTo
    {
        return $this->belongsTo(Position::class, 'direct_supervisor_position_id');
    }

    protected function casts(): array
    {
        return [
            'current_headcount' => 'integer',
            'requested_headcount' => 'integer',
            'weekly_hours' => 'integer',
            'salary_amount' => 'integer',
            'currency' => 'integer',
            'salary_per' => 'integer',
            'required_experience_years' => 'integer',
            'role_description_ready' => 'boolean',
            'finance_consulted' => 'boolean',
            'org_chart_updated' => 'boolean',
            'volunteer_period_start_date' => 'date',
            'volunteer_period_end_date' => 'date',
            'expected_start_date' => 'date',
        ];
    }
}
