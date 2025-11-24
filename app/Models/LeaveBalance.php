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
        $workHoursPerDay = $this->contract->weekly_hours / 5;

        $days = floor($hours / $workHoursPerDay);
        $remaining = $hours - ($days * $workHoursPerDay);

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
        $remainingHours = $this->deserved - $this->used;
        $workHoursPerDay = $this->contract->weekly_hours / 5;

        // Whole days
        $days = floor($remainingHours / $workHoursPerDay);

        // Remaining decimal hours
        $remaining = $remainingHours - ($days * $workHoursPerDay);

        return $this->getFormattedLabel($days, $remaining);
    }


    public function getFormattedLabel($days, $remaining): string
    {
        // Split remaining into hours + minutes
        $hours = (int) $remaining;
        $minutes = (int) round(($remaining - $hours) * 60);

        // Normalize: 60 minutes → +1 hour
        if ($minutes >= 60) {
            $hours += intdiv($minutes, 60);
            $minutes = $minutes % 60;
        }

        // -------------------------------------------
        // Build the original text for days
        // -------------------------------------------
        $text = '';
        if ($days > 0) {
            $text = '<strong class="text-secondary-dark">'.$days.'</strong>' . ' ' .
                trans_choice('general.day', $days) . ' ';
        }

        $result = $text;

        // -------------------------------------------
        // Now handle hours + minutes
        // -------------------------------------------
        if ($hours > 0 || $minutes > 0) {

            $timeText = [];

            if ($hours > 0) {
                $timeText[] = '<strong class="text-secondary-dark">'.$hours.'</strong>' . ' ' .
                    trans_choice('general.hour', $hours);
            }

            if ($minutes > 0) {
                $timeText[] = '<strong class="text-secondary-dark">'.$minutes.'</strong>' . ' ' .
                    trans_choice('general.minute', $minutes);
            }

            $finalTime = implode(' ', $timeText);

            if ($text !== '') {
                $result = __('general.and', [
                    'attr1' => $text,
                    'attr2' => $finalTime
                ]);
            } else {
                $result = $finalTime;
            }
        }

        // Original fallback behavior
        if ($text === '' && $hours === 0 && $minutes === 0) {
            $result = '<strong class="text-secondary-dark">0</strong>' . ' ' .
                trans_choice('general.day', $days);
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

