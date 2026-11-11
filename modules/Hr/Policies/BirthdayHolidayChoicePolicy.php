<?php

declare(strict_types=1);

namespace Modules\Hr\Policies;

use Modules\Core\Models\User;
use Modules\Hr\Models\BirthdayHolidayChoice;
use Modules\Hr\Support\Leave\BirthdayHoliday;

/**
 * The day taken instead of a birthday that falls on a weekend or holiday
 * (H-4): the volunteer asks, only their direct manager decides.
 */
class BirthdayHolidayChoicePolicy
{
    public function decide(User $user, BirthdayHolidayChoice $choice): bool
    {
        return $choice->status === BirthdayHolidayChoice::PENDING
            && app(BirthdayHoliday::class)->canDecide($user, $choice);
    }
}
