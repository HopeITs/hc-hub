<?php

declare(strict_types=1);

namespace Modules\Hr\Support\Recruitment;

use Modules\Hr\Enums\ContractType as ContractTypeEnum;
use Modules\Hr\Models\Contract;
use Modules\Hr\Models\Employee;
use Modules\Hr\Models\OnboardingJourney;

/**
 * Starts a hire's onboarding journey: the old OnboardingJourneys::startForContract(),
 * the one step of it that hiring needs. Bound as 'hr.recruitment.onboarding'
 * (HiringHandoff) until the journeys area has its own service; the rest of the
 * journey (orientation, card hand-over) belongs to that area.
 *
 * Only a first real contract starts a journey, and only once per employee.
 */
final class OnboardingStarter
{
    /** The old OnboardingJourneys::ORIENTATION_DAYS (DEC-09). */
    public const ORIENTATION_DAYS = 14;

    public function __construct(private readonly ActivityLogger $activity) {}

    public function __invoke(Employee $employee, Contract $contract): ?OnboardingJourney
    {
        if ((int) $contract->type === ContractTypeEnum::Draft->value) {
            return null;
        }

        if ($employee->realContracts()->whereKeyNot($contract->getKey())->exists()) {
            return null;
        }

        $existing = OnboardingJourney::query()->where('employee_id', $employee->getKey())->first();

        if ($existing !== null) {
            return $existing;
        }

        $start = $contract->start_date ?? now(config('app.timezone'));

        $journey = new OnboardingJourney;
        $journey->forceFill([
            'employee_id' => $employee->getKey(),
            'contract_id' => $contract->getKey(),
            'started_at' => now(),
            'orientation_due_on' => $start->copy()->addDays(self::ORIENTATION_DAYS)->toDateString(),
        ])->save();

        $this->activity->log('onboarding_started', $journey, properties: [
            'employee_id' => $employee->getKey(),
            'contract_id' => $contract->getKey(),
        ], logName: 'onboarding');

        return $journey;
    }
}
