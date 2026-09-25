<?php

namespace App\Enums;

/**
 * What one matching indicator found.
 *
 * Unknown and NotApplicable are not misses: missing data, an older form
 * version that never asked the question, or a vacancy with no requirement
 * recorded are all left out of the count rather than held against anyone.
 * Info is for flags that must never count either way.
 */
final class RecruitmentIndicatorOutcome
{
    public const Met = 'met';

    public const Partial = 'partial';

    public const NotMet = 'not_met';

    public const Unknown = 'unknown';

    public const NotApplicable = 'not_applicable';

    public const Info = 'info';

    /** Outcomes that count towards "assessed". */
    public static function isAssessed(string $outcome): bool
    {
        return in_array($outcome, [self::Met, self::Partial, self::NotMet], true);
    }

    /** Whole class names, so Tailwind's scanner finds them. */
    public static function classes(string $outcome): string
    {
        return match ($outcome) {
            self::Met => 'bg-green-50 text-green-800',
            self::Partial => 'bg-amber-50 text-amber-800',
            self::NotMet => 'bg-red-50 text-red-800',
            self::Info => 'bg-blue-50 text-blue-800',
            default => 'bg-neutral-100 text-secondary-medium',
        };
    }
}
