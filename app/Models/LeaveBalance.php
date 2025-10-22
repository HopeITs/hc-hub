<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LeaveBalance extends Model
{
    use HasFactory;

    protected $fillable = [
        'leaveBalance_id',
        'contract_id',
        'type_id',
        'deserved',
        'used'
    ];

    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }
    public function getDeservedAttribute()
    {
        $hours = $this->deserved;
        $workDaysCountForWeek = $this->contract->weekly_hours / 5;
        $days = intdiv($hours, $workDaysCountForWeek);
        $remaining = $hours % $workDaysCountForWeek;

        $text = $days . " " . trans_choice('general.day',$days);

        if ($remaining > 0) {
            $text .=  $remaining .  trans_choice('general.day',$remaining);
        }

        return $text;
    }
    public function leaveType()
    {
        return $this->belongsTo(LeaveType::class,'type_id');
    }

    public function leaveRequests()
    {
        return $this->hasMany(LeaveRequest::class);
    }

}

