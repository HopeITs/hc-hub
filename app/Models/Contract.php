<?php

namespace App\Models;

use App\Enums\SalaryPer;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Contract extends Model
{
    use HasFactory, LogsActivity;

    protected $guarded = ['id'];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'terminated_at' => 'date',
        'is_active' => 'boolean',
        'weekly_hours' => 'integer',
        'currency' => 'integer',
        'salary' => 'decimal:2',

    ];

    //    protected function work_days(): Attribute
    //    {
    //        return Attribute::make(
    //            get: fn() => $this->getTranslatableAttribute('work_days'),
    //            set: fn($value) => $this->setTranslatableAttribute('work_days', $value)
    //        );
    //    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function position()
    {
        return $this->belongsTo(Position::class, 'position_id');
    }

    public function scopeActiveNow(Builder $query): Builder
    {
        return $query
            ->where('is_active', true)
            ->whereDate('start_date', '<=', now())
            ->whereDate('end_date', '>=', now());
    }

    public function contractType()
    {
        return $this->belongsTo(ContractType::class, 'contract_type_id');
    }

      public function terminationReason()
    {
        return $this->belongsTo(TerminationReason::class, 'termination_reason_id');
    }

    public function leaveBalances()
    {
        return $this->hasMany(LeaveBalance::class, 'contract_id');
    }

    public function getDurationAttribute()
    {
        $start_date = Carbon::parse($this->start_date);
        $end_date = Carbon::parse($this->end_date);

        return round($start_date->diffInMonths($end_date), 1);
    }

    public function getWorkHourPerDayAttribute(): int
    {
        return $this->weekly_hours / 5;
    }



    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('contract')
            ->logOnly([
                'employee_id',
                'position_id',
                'contract_type_id',
                'start_date',
                'end_date',
                'terminated_at',
                'is_active',
                'weekly_hours',
                'currency',
                'salary',
                'salary_per',
                'work_days_en',
                'work_days_ar',
                'attending_notes_en',
                'attending_notes_ar',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
