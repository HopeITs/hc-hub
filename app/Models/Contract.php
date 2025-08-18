<?php

namespace App\Models;

use App\Enums\SalaryPer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Contract extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'position_id',
        'contract_type_id',
        'department_id',
        'job_level_id',
        'start_date',
        'end_date',
        'terminated_at',
        'reason_of_termination',
        'yearly_leave_days',
        'currency',
        'salary',
        'salary_per',
        'work_days',
        'work_hours',
        'weekly_hours',
        'is_active'
    ];

    protected $casts = [
        'start_date'        => 'date',
        'end_date'          => 'date',
        'terminated_at'     => 'date',
        'is_active'         => 'boolean',
        'yearly_leave_days' => 'integer',
        'weekly_hours'      => 'integer',
        'currency'          => 'integer',
        'salary'            => 'decimal:2',
        'salary_per'        => SalaryPer::class,
        'work_days'         => 'array',
        'work_hours'        => 'array',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }
    public function position()
    {
        return $this->belongsTo(Position::class, 'position_id');
    }
    public function contractType()
    {
        return $this->belongsTo(ContractType::class, 'contract_type_id');
    }
    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }
    public function jobLevel()
    {
        return $this->belongsTo(JobLevel::class, 'job_level_id');
    }

    public function leaveBalances()
    {
        return $this->hasMany(LeaveBalance::class, 'contract_id');
    }
}
