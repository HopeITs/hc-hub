<?php

declare(strict_types=1);

namespace Modules\Hr\Actions\Leave;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Modules\Core\Models\User;
use Modules\Hr\Models\BirthdayHolidayChoice;
use Modules\Hr\Models\Holiday;
use Modules\Hr\Support\Leave\BirthdayHoliday;

/**
 * The volunteer asks for a working day in the same week instead of a
 * birthday that falls on a weekend or holiday (H-4). Their own running
 * contract decides the week and the working days.
 */
final class ChooseBirthdayHoliday
{
    public function __construct(private readonly BirthdayHoliday $birthdays) {}

    /**
     * @throws ValidationException
     */
    public function handle(User $user, int $holidayId, int $year, mixed $date): BirthdayHolidayChoice
    {
        Validator::make(['chosen_date' => $date], ['chosen_date' => ['required', 'date_format:Y-m-d']])->validate();

        $contract = $this->birthdays->contractOfUser($user);
        abort_if($contract === null, 403);

        $holiday = Holiday::query()->where('kind', Holiday::KIND_BIRTHDAY)->findOrFail($holidayId);

        return $this->birthdays->choose($contract, $holiday, $year, CarbonImmutable::createFromFormat('!Y-m-d', (string) $date));
    }
}
