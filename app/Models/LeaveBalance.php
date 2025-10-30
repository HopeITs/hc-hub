<?php

namespace App\Models;

use Carbon\Carbon;
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
    public function getDeservedFormattedAttribute(): string
    {
        $hours = $this->deserved;
        $workDaysCountForWeek = $this->contract->weekly_hours / 5;
        $days = intdiv($hours, $workDaysCountForWeek);
        $remaining = $hours % $workDaysCountForWeek;

        $text = $days . " " . trans_choice('general.day',$days) . " ";

        if ($remaining > 0) {
            $text .=  $remaining . " " .  trans_choice('general.hour',$remaining);
        }

        return $text;
    }

    public function eligibleAnnualLeaveHours(?Carbon $contractStart = null, ?Carbon $contractEnd = null): int
    {
        $contract = $this->contract;
        if (!$contract) {
            return 0;
        }

        $start = Carbon::parse($contract->start_date);
        $end = Carbon::parse($contract->end_date);
        $now = now();

        // If contract hasn’t started yet, no leave
        if ($now->lt($start)) {
            return 0;
        }

        $totalHours = $this->deserved ?? 0; // e.g. 96 hours
        if ($totalHours <= 0) {
            return 0;
        }

        // Contract duration in months
        $contractMonths = max(1, $start->diffInMonths($end));
        // Divide into 3 equal sections
        $sectionMonths = ceil($contractMonths / 3);
        $hoursPerSection = $totalHours / 3;

        // Figure out how many sections have passed
        $monthsPassed = $start->diffInMonths($now);
        $sectionsPassed = floor($monthsPassed / $sectionMonths) + 1;

        // Cap at total
        return  round(min($sectionsPassed * $hoursPerSection, $totalHours));
    }


    /**
     * Whether employee can request specific number of hours right now.
     */
    public function canRequestAnnualLeave(float $requestedHours): bool
    {
        $eligible = $this->eligibleAnnualLeaveHours();

        $used = $this->used ?? 0;

        $remaining = $eligible - $used;
dd($remaining , $requestedHours);
        return $requestedHours <= $remaining;
    }

    public function isAnnual(): bool
    {
        return $this->leaveType->id === \App\Enums\LeaveTypeCases::Annual;
    }


    public function getRemainingAttribute(): string{
        $remaining = $this->deserved - $this->used;
        $workDaysCountForWeek = $this->contract->weekly_hours / 5;
        $days = intdiv($remaining, $workDaysCountForWeek);
        $remaining = $remaining % $workDaysCountForWeek;

        $text = $days . " " . trans_choice('general.day',$days) . " ";

        if ($remaining > 0) {
            $text .=  $remaining . " " .  trans_choice('general.hour',$remaining);
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

