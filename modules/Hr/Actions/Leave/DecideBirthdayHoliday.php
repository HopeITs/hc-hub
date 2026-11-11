<?php

declare(strict_types=1);

namespace Modules\Hr\Actions\Leave;

use Illuminate\Support\Facades\Gate;
use Modules\Core\Models\User;
use Modules\Hr\Models\BirthdayHolidayChoice;
use Modules\Hr\Support\Leave\BirthdayHoliday;

/**
 * The direct manager approves or declines the day a volunteer asked for
 * instead of their birthday (H-4). Only an undecided request can be decided.
 */
final class DecideBirthdayHoliday
{
    public function __construct(private readonly BirthdayHoliday $birthdays) {}

    public function handle(User $user, int $choiceId, bool $approve): BirthdayHolidayChoice
    {
        $choice = BirthdayHolidayChoice::query()->findOrFail($choiceId);

        Gate::forUser($user)->authorize('decide', $choice);

        $this->birthdays->decide($user, $choice, $approve);

        return $choice;
    }
}
