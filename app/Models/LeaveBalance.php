<?php

namespace App\Models;

use App\Enums\LeaveTypeCases;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class LeaveBalance extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = ['contract_id', 'type_id', 'deserved', 'used'];

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class, 'contract_id');
    }

    public function getIntTotalAttribute(): int
    {
        [$days] = $this->splitIntoDays($this->deserved ?? 0);

        return (int) $days;
    }

    public function getDeservedFormattedAttribute(): string
    {
        [$days, $remaining] = $this->splitIntoDays($this->deserved ?? 0);

        return $this->getFormattedLabel($days, $remaining);
    }

    public function getUsedFormattedAttribute(): string
    {
        [$days, $remaining] = $this->splitIntoDays($this->used ?? 0);

        return $this->getFormattedLabel($days, $remaining);
    }

    public function eligibleAnnualLeaveHours(?Carbon $contractStart = null, ?Carbon $contractEnd = null): int
    {
        $contract = $this->contract;
        if (! $contract) {
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
        return $this->leaveType->id === LeaveTypeCases::Annual;
    }

    public function getIntRemainingAttribute(): int
    {
        [$days] = $this->splitIntoDays(($this->deserved ?? 0) - ($this->used ?? 0));

        return (int) $days;
    }

    public function getRemainingAttribute(): string
    {
        [$days, $remaining] = $this->splitIntoDays(($this->deserved ?? 0) - ($this->used ?? 0));

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
            $text = trans_choice('general.count.days', $days, ['count' => '<strong class="text-secondary-dark">'.$days.'</strong>']).' ';
        }

        $result = $text;

        // -------------------------------------------
        // Now handle hours + minutes
        // -------------------------------------------
        if ($hours > 0 || $minutes > 0) {

            $timeText = [];

            if ($hours > 0) {
                $timeText[] = trans_choice('general.count.hours', $hours, ['count' => '<strong class="text-secondary-dark">'.$hours.'</strong>']);
            }

            if ($minutes > 0) {
                $timeText[] = trans_choice('general.count.minutes', $minutes, ['count' => '<strong class="text-secondary-dark">'.$minutes.'</strong>']);
            }

            $finalTime = join_counts($timeText);

            if ($text !== '') {
                $result = __('general.and', [
                    'attr1' => $text,
                    'attr2' => $finalTime,
                ]);
            } else {
                $result = $finalTime;
            }
        }

        // Original fallback behavior
        if ($text === '' && $hours === 0 && $minutes === 0) {
            $result = trans_choice('general.count.days', 0, ['count' => '<strong class="text-secondary-dark">0</strong>']);
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

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('leave_balance')
            ->logOnly([
                'contract_id',
                'type_id',
                'deserved',
                'used',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Daily work hours of the contract, guarded against zero so the
     * day/hour breakdown never divides by zero (e.g. imported contracts
     * with weekly_hours = 0). Falls back to null when unknown.
     */
    private function workHoursPerDay(): ?float
    {
        $perDay = ($this->contract->weekly_hours ?? 0) / 5;

        return $perDay > 0 ? $perDay : null;
    }

    /**
     * Split an amount of hours into [whole days, remaining hours].
     * When the daily work hours are unknown, everything stays as hours.
     */
    private function splitIntoDays(float $hours): array
    {
        $perDay = $this->workHoursPerDay();

        if ($perDay === null) {
            return [0, $hours];
        }

        $days = floor($hours / $perDay);

        return [$days, $hours - ($days * $perDay)];
    }
}
