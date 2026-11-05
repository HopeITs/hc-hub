<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\ReviewerSlot;
use App\Notifications\Alerts\NotificationSettings;
use InvalidArgumentException;

/**
 * The one resolver for the named organisational slots (contracts.md §9):
 * MEAL Manager, Finance Manager, Projects & Fundraising Manager, CEO and,
 * since W-34a, the HR liaison who hears about program requests.
 *
 * A slot stores an HR position, never a person: the reviewer slots in
 * notification_settings `proposal_default_reviewers`, the HR liaison in
 * `hr_liaison_position`; the person is whoever holds that position now.
 */
final class Slots
{
    public const HrLiaison = 'hr_liaison';

    /**
     * The HR employee id holding the slot right now, or null when it is
     * vacant: no position configured, no active contract on it, or its holder
     * is inactive in HR. Every caller (reviewers, the D-09a reopen fallback,
     * MEAL routing, program requests) reads vacancy the same way, and none
     * falls back to Super Admin (D-20a).
     *
     * @param  string  $slot  meal_manager | finance_manager | fundraising_manager | ceo | hr_liaison
     *
     * @throws InvalidArgumentException for any other slot
     */
    public static function holderOf(string $slot): ?int
    {
        $positionId = self::positionIdOf($slot);
        $holder = $positionId === null ? null : EmployeeProfile::holderOfPosition($positionId);

        if ($holder === null || ! (EmployeeProfile::for($holder)['is_active'] ?? false)) {
            return null;
        }

        return $holder;
    }

    /**
     * The HR position configured for one slot, null when unset.
     *
     * @throws InvalidArgumentException for a name that is not a position slot
     */
    private static function positionIdOf(string $slot): ?int
    {
        if ($slot === self::HrLiaison) {
            $value = app(NotificationSettings::class)->get('hr_liaison_position');

            return is_numeric($value) ? (int) $value : null;
        }

        $case = ReviewerSlot::tryFrom($slot);

        if ($case === null || ! $case->isDefault()) {
            throw new InvalidArgumentException("[{$slot}] is not a position slot.");
        }

        return self::positionIds()[$slot] ?? null;
    }

    /**
     * The configured HR position of each reviewer slot, null when unset.
     *
     * @return array<string, int|null>
     */
    public static function positionIds(): array
    {
        $stored = app(NotificationSettings::class)->get('proposal_default_reviewers');
        $stored = is_array($stored) ? $stored : [];
        $ids = [];

        foreach (ReviewerSlot::defaultSlots() as $slot) {
            $value = $stored[$slot->value] ?? null;
            $ids[$slot->value] = is_numeric($value) ? (int) $value : null;
        }

        return $ids;
    }
}
