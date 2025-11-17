<?php

namespace App\Models;

use App\Enums\LeaveRequestStatus;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LeaveRequest extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'start_at' => 'datetime',
        'end_at' => 'datetime',
    ];

    public function leaveBalance(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(LeaveBalance::class);
    }

    public function getDurationAttribute(): string
    {
        $weekend = $this->leaveBalance->contract->position->center->weekends;
        $weekends = explode(',',$weekend);

Carbon::setWeekend($weekends);

        if($this->type === 'hour'){

            $duration = $this->start_at->floatDiffInRealHours($this->end_at);
            return  $duration . ' ' . trans_choice('general.hour',$duration);
        }else{

            $duration = $this->start_at->diffInWeekdays($this->end_at) + 1;
            return  $duration . ' ' . trans_choice('general.day',$duration);
        }
    }

    public function scopePending($q)
    {
        return $q->where('status', LeaveRequestStatus::Pending);
    }

// Numeric duration (no unit suffix)
    public function getDurationValueAttribute(): float
    {
        $weekend = $this->leaveBalance->contract->position->center->weekends;
        dd($weekend);
        if ($this->type === 'hour') {
            return $this->start_at->floatDiffInRealHours($this->end_at);
        }

        // Weekdays only (excludes Sat/Sun)
        return $this->start_at->diffInWeekdays($this->end_at);
    }
}
