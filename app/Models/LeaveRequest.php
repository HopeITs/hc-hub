<?php

namespace App\Models;

use App\Enums\LeaveRequestStatus;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Carbon;

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

//    public function getDurationAttribute(): string
//    {
//        $weekend = $this->leaveBalance->contract->position->center->weekends;
//
//        $weekends = array_map(function ($day) {
//            return Carbon::parse($day)->dayOfWeek;
//        }, explode(',', $weekend));
//
//
//        Carbon::setWeekendDays($weekends);
//
//        if($this->type === 'hour'){
//
//            $duration = $this->start_at->floatDiffInRealHours($this->end_at);
//            return  $duration . ' ' . trans_choice('general.hour',$duration);
//        }else{
//
//            $duration = $this->start_at->diffInWeekdays($this->end_at);
//            return  $duration . ' ' . trans_choice('general.day',$duration);
//        }
//    }

    public function getDurationAttribute(): string
    {
        $weekends = array_map('intval', explode(',', $this->leaveBalance->contract->position->center->weekends));

        Carbon::setWeekendDays($weekends);

        if ($this->type === 'hour') {
            $duration = $this->start_at->floatDiffInRealHours($this->end_at);
            return $duration.' '.trans_choice('general.hour', $duration);
        }

        $start = $this->start_at;
        $end   = $this->end_at;

        // Include the end date
        $duration = $start->diffInWeekDays($end) + 1 ;

        return $duration.' '.trans_choice('general.day', $duration);
    }


    public function scopePending($q)
    {
        return $q->where('status', LeaveRequestStatus::Pending);
    }


}
