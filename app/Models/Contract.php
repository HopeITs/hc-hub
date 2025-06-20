<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Contract extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'employee_id',
        'position_id',
        'contract_type_id',
        'start_date',
        'end_date',
        'terminated_at',
        'reason_of_termination',
        'yearly_leave_days',
        'salary',
        'work_days',
        'work_hours',
        'is_active'
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function position()
    {
        return $this->belongsTo(Position::class);
    }

    public function contractType()
    {
        return $this->belongsTo(ContractType::class);
    }

    public function leaveBalances()
    {
        return $this->hasMany(LeaveBalance::class);
    }
}
