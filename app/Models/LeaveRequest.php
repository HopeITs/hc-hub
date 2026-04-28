<?php

namespace App\Models;

use App\Enums\LeaveRequestStatus;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class LeaveRequest extends Model
{
    use HasFactory, LogsActivity;

    protected $guarded = ['id'];

    protected $casts = [
        'start_at' => 'datetime',
        'end_at' => 'datetime',
    ];

    public function leaveBalance(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(LeaveBalance::class);
    }
    public function leaveType(): \Illuminate\Database\Eloquent\Relations\HasOneThrough|LeaveRequest
    {
        return $this->hasOneThrough(
            LeaveType::class,      // final model you want
            LeaveBalance::class,   // intermediate model
            'id',                  // LeaveBalance.id
            'id',                  // LeaveType.id
            'leave_balance_id',    // LeaveRequest.leave_balance_id
            'type_id'        // LeaveBalance.leave_type_id
        );
    }

    public function alternativeEmployee(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Employee::class, 'alternative_employee_id');
    }


    public function getDurationAttribute(): string
    {
        $weekends = array_map('intval', explode(',', $this->leaveBalance->contract->position->center->weekends));
        Carbon::setWeekendDays($weekends);

        // Hour-based leave: show hours + minutes
        if ($this->type === 'hour') {
            $floatHours = $this->start_at->floatDiffInRealHours($this->end_at);

            $hours   = (int) $floatHours;
            $minutes = (int) round(($floatHours - $hours) * 60);

            // 60 minutes rolls into an extra hour
            if ($minutes >= 60) {
                $hours   += intdiv($minutes, 60);
                $minutes = $minutes % 60;
            }

            $parts = [];

            if ($hours > 0) {
                $parts[] = $hours.' '.trans_choice('general.hour', $hours);
            }

            if ($minutes > 0) {
                $parts[] = $minutes.' '.trans_choice('general.minute', $minutes);
            }

            // Fallback if both are zero (unlikely but safe)
            return implode(' ', $parts) ?: '0 '.trans_choice('general.minute', 2);
        }

        // Day-based leave (unchanged)
        $start = $this->start_at;
        $end   = $this->end_at;

        // Include the end date
        $days = $start->diffInWeekDays($end) + 1;

        return $days.' '.trans_choice('general.day', $days);
    }

    public function getDurationInHoursAttribute(): float
    {
        // Determine weekend days from the center
        $weekends = array_map('intval', explode(',', $this->leaveBalance->contract->position->center->weekends));
        Carbon::setWeekendDays($weekends);

        // For hour-type leave, we already have precise decimal hours
        if ($this->type === 'hour') {
            return $this->start_at->floatDiffInRealHours($this->end_at);
        }

        // For day-type leave: calculate days
        $start = $this->start_at;
        $end   = $this->end_at;

        // Include the end date
        $days = $start->diffInWeekDays($end) + 1;
        $contract = $this->leaveBalance->contract;

        // Get weekly hours from contract
        $weeklyHours = (float) $contract->weekly_hours;

        // Hours per workday
        $hoursPerDay = $weeklyHours / 5;

        // Total hours for this leave
        return $days * $hoursPerDay;
    }


    public function scopePending($q)
    {
        return $q->where('status', LeaveRequestStatus::Pending);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('leave_request')
            ->logOnly([
                'leave_balance_id',
                'start_at',
                'end_at',
                'type',
                'status',
                'alternative_employee_id',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

}
