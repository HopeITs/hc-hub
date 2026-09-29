<?php

namespace App\Enums;

/**
 * A reviewer's own recommendation after an interview (Phase 2 Task 8).
 *
 * Advisory only: it is one participant's opinion and never the hiring
 * decision, which is made — and recorded — in the decision panel
 * (RecruitmentApplicationStatusService). Nothing reads this to move an
 * application.
 */
final class RecruitmentEvaluationRecommendation
{
    /**
     * Retired scale points. They are no longer offered or accepted, but
     * evaluation history written while they existed still carries them, so
     * they keep a label and a colour.
     */
    public const StrongYes = 'strong_yes';

    public const StrongNo = 'strong_no';

    public const Yes = 'yes';

    public const Maybe = 'maybe';

    public const No = 'no';

    /** @return array<int, string> in favour first */
    public static function values(): array
    {
        return [self::Yes, self::Maybe, self::No];
    }

    public static function classes(?string $value): string
    {
        return match ($value) {
            self::StrongYes => 'bg-green-100 text-green-800',
            self::Yes => 'bg-green-50 text-green-700',
            self::Maybe => 'bg-amber-50 text-amber-700',
            self::No => 'bg-red-50 text-red-700',
            self::StrongNo => 'bg-red-100 text-red-800',
            default => 'bg-neutral-100 text-secondary-dark',
        };
    }
}
