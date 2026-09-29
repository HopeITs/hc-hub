<?php

namespace App\Enums;

/**
 * Where an interview stands (Phase 2 Task 7).
 *
 * Scheduled and Rescheduled are the two open states: the interview is still
 * going to happen, reminders go out, and it can be moved or cancelled.
 * Cancelled, Completed and NoShow are final — an interview is never deleted,
 * and one that ended is never reopened; a new interview is scheduled instead.
 */
final class RecruitmentInterviewStatus
{
    public const Scheduled = 'scheduled';

    public const Rescheduled = 'rescheduled';

    public const Cancelled = 'cancelled';

    public const Completed = 'completed';

    public const NoShow = 'no_show';

    /** @var array<string, array<int, string>> */
    private const TRANSITIONS = [
        self::Scheduled => [self::Rescheduled, self::Cancelled, self::Completed, self::NoShow],
        self::Rescheduled => [self::Rescheduled, self::Cancelled, self::Completed, self::NoShow],
    ];

    /** @return array<int, string> */
    public static function values(): array
    {
        return [self::Scheduled, self::Rescheduled, self::Cancelled, self::Completed, self::NoShow];
    }

    /** @return array<int, string> */
    public static function open(): array
    {
        return [self::Scheduled, self::Rescheduled];
    }

    public static function isOpen(?string $status): bool
    {
        return in_array($status, self::open(), true);
    }

    public static function canMove(?string $from, string $to): bool
    {
        return in_array($to, self::TRANSITIONS[$from] ?? [], true);
    }

    /** Tailwind classes for the status badge. */
    public static function classes(?string $status): string
    {
        return match ($status) {
            self::Scheduled => 'bg-blue-50 text-blue-700',
            self::Rescheduled => 'bg-amber-50 text-amber-700',
            self::Completed => 'bg-green-50 text-green-700',
            self::NoShow => 'bg-red-50 text-red-700',
            default => 'bg-neutral-100 text-secondary-dark',
        };
    }
}
