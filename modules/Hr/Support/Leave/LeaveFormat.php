<?php

declare(strict_types=1);

namespace Modules\Hr\Support\Leave;

use Modules\Hr\Enums\LeaveRequestStatus;
use Modules\Hr\Models\LeaveRequest;

/**
 * Leave amounts in words: hours worded as days + hours + minutes with the
 * contract's day length (the old LeaveBalance::getFormattedLabel and
 * LeaveRequest::duration), plain text only.
 */
final class LeaveFormat
{
    public static function hours(float $hours, ?float $hoursPerDay): string
    {
        $negative = $hours < 0;
        [$days, $rest] = LeaveRequestPricing::splitDays(abs($hours), $hoursPerDay);
        $wholeHours = (int) floor($rest + 1e-9);
        $minutes = (int) round(($rest - $wholeHours) * 60);

        if ($minutes >= 60) {
            $wholeHours += intdiv($minutes, 60);
            $minutes %= 60;
        }

        $parts = [];

        if ($days > 0) {
            $parts[] = trans_choice('hr_leave.units.days', $days, ['count' => $days]);
        }

        if ($wholeHours > 0) {
            $parts[] = trans_choice('hr_leave.units.hours', $wholeHours, ['count' => $wholeHours]);
        }

        if ($minutes > 0) {
            $parts[] = trans_choice('hr_leave.units.minutes', $minutes, ['count' => $minutes]);
        }

        if ($parts === []) {
            return trans_choice('hr_leave.units.days', 0, ['count' => 0]);
        }

        $text = implode(__('hr_leave.units.and'), $parts);

        return $negative ? '−'.$text : $text;
    }

    /** How long a stored request is (its snapshot, else the legacy rule). */
    public static function duration(LeaveRequest $leaveRequest, ?float $hoursPerDay): string
    {
        $hours = app(LeaveRequestPricing::class)->deductionHours($leaveRequest);

        return $leaveRequest->type === LeaveWindow::HOUR ? self::hours($hours, null) : self::hours($hours, $hoursPerDay);
    }

    /** "25-10-2026 → 26-10-2026" / "25-10-2026, 09:00 → 11:00". */
    public static function period(LeaveRequest $leaveRequest): string
    {
        return LeaveRequestDraft::describePeriod($leaveRequest);
    }

    public static function status(LeaveRequest $leaveRequest): LeaveRequestStatus
    {
        return LeaveRequestStatus::of($leaveRequest->status);
    }
}
