<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveBalance extends Model
{
    use HasFactory;

    protected $fillable = [
        'contract_id',
        'type_id',
        'deserved',
        'used'
    ];

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class, 'contract_id');
    }

    public function getIntTotalAttribute(): int
    {
        $hours = $this->deserved;
        $workDaysCountForWeek = $this->contract->weekly_hours / 5;
        $days = intdiv($hours, $workDaysCountForWeek);

        return round($days);
    }

    public function getDeservedFormattedAttribute(): string
    {
        $hours = $this->deserved;
        $workDaysCountForWeek = $this->contract->weekly_hours / 5;
        $days = intdiv($hours, $workDaysCountForWeek);
        $remaining = $hours % $workDaysCountForWeek;

        return $this->getFormattedLabel($days, $remaining);
    }

    public function getUsedFormattedAttribute(): string
    {
        $hours = $this->used;
        $workDaysCountForWeek = $this->contract->weekly_hours / 5;
        $days = intdiv($hours, $workDaysCountForWeek);
        $remaining = $hours % $workDaysCountForWeek;

        return $this->getFormattedLabel($days, $remaining);
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
        return round(min($sectionsPassed * $hoursPerSection, $totalHours));
    }


    /**
     * Whether employee can request specific number of hours right now.
     */
    public function canRequestAnnualLeave($requestedHours): bool
    {
        $eligible = $this->eligibleAnnualLeaveHours();

        $used = $this->used ?? 0;

        $remaining = $eligible - $used;

        return $requestedHours <= $remaining;
    }

    public function isAnnual(): bool
    {
        return $this->leaveType->id === \App\Enums\LeaveTypeCases::Annual;
    }

    public function getIntRemainingAttribute(): int
    {
        $remaining = $this->deserved - $this->used;
        $workDaysCountForWeek = $this->contract->weekly_hours / 5;
        $days = intdiv($remaining, $workDaysCountForWeek);

        return round($days);
    }

    public function getRemainingAttribute(): string
    {
        $remaining = $this->deserved - $this->used;
        $workDaysCountForWeek = $this->contract->weekly_hours / 5;
        $days = intdiv($remaining, $workDaysCountForWeek);
        $remaining = $remaining % $workDaysCountForWeek;
        return $this->getFormattedLabel($days, $remaining);

    }

    public function getFormattedLabel($days, $remaining): string
    {
        $text = '';
        if ($days > 0) {
            $text = '<strong class="text-secondary-dark">' . $days . '</strong>' . " " . trans_choice('general.day', $days) . " ";
        }
        $result = $text;

        if ($remaining > 0) {
            if ($text !== '')
                $result = __('general.and', ['attr1' => $text, 'attr2' => '<strong class="text-secondary-dark">' . $remaining . '</strong>' . " " . trans_choice('general.hour', $remaining)]);
            else
                $result = '<strong class="text-secondary-dark">' . $remaining . '</strong>' . " " . trans_choice('general.hour', $remaining);
        }
        if($text === ''){
            $result = '<strong class="text-secondary-dark">0</strong>'. ' ' . trans_choice('general.day', $days);
        }

        return $result;
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class, 'type_id');
    }


    public function leaveRequests()
    {
        return $this->hasMany(LeaveRequest::class);
    }

}

