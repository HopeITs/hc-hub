<?php

declare(strict_types=1);

namespace Modules\Hr\Support\Recruitment;

use Illuminate\Validation\ValidationException;
use Modules\Hr\Models\Contract;
use Modules\Hr\Models\Employee;

/**
 * What must happen to a contract that hiring has just written: its leave
 * balances and, for a first contract, the onboarding journey.
 *
 * Both belong to other HR areas (leave, journeys). Each area registers its
 * part in the container:
 *
 *   $this->app->bind('hr.recruitment.leave-balances', fn () => fn (Contract $contract) => ...);
 *   $this->app->bind('hr.recruitment.onboarding', fn () => fn (Employee $employee, Contract $contract) => ...);
 *
 * Until both are bound a conversion is refused, so no employee is ever hired
 * with a contract that has no leave balance behind it.
 */
class HiringHandoff
{
    private const LEAVE = 'hr.recruitment.leave-balances';

    private const ONBOARDING = 'hr.recruitment.onboarding';

    public function ready(): bool
    {
        return app()->bound(self::LEAVE) && app()->bound(self::ONBOARDING);
    }

    public function contractCreated(Employee $employee, Contract $contract): void
    {
        if (! $this->ready()) {
            throw ValidationException::withMessages([
                'conversion' => __('hr_recruitment.hub.conversion_not_wired'),
            ]);
        }

        app(self::LEAVE)($contract);
        app(self::ONBOARDING)($employee, $contract);
    }
}
