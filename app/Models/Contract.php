<?php

namespace App\Models;

use App\Enums\SalaryPer;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Contract extends Model
{
    use HasFactory ;

    protected $guarded = ['id'];


    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'terminated_at' => 'date',
        'is_active' => 'boolean',
        'weekly_hours' => 'integer',
        'currency' => 'integer',
        'salary' => 'decimal:2',
        'salary_per' => SalaryPer::class,

    ];
    protected function work_days(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->getTranslatableAttribute('work_days'),
            set: fn($value) => $this->setTranslatableAttribute('work_days', $value)
        );
    }


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


    public function leaveBalances()
    {
        return $this->hasMany(LeaveBalance::class, 'contract_id');
    }

    public function getDurationAttribute(){
        $start_date = Carbon::parse($this->start_date); // start date
        $end_date = Carbon::parse($this->end_date); // end date

        return round( $start_date->diffInMonths($end_date),1);
    }

    public function getWorkHourPerDayAttribute(): int
    {
        return $this->weekly_hours / 5;
    }


}
