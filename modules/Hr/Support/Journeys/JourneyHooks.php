<?php

declare(strict_types=1);

namespace Modules\Hr\Support\Journeys;

use Modules\Core\Models\User;
use Modules\Hr\Enums\ContractType as ContractTypeEnum;
use Modules\Hr\Models\Contract;
use Modules\Hr\Models\Employee;
use Modules\Hr\Models\OffboardingJourney;
use Modules\Hr\Support\Checklists\ChecklistWorkflow;

/**
 * What a contract write means for the journeys (the journey parts of the old
 * ContractObserver and EmployeeActivation), registered on the Contract model
 * by HrServiceProvider so every write path counts:
 *
 *  - a first real, active contract starts the onboarding journey;
 *  - auto-renew switched from on to off starts the equipment returns;
 *  - stopping auto-renew (E1) or terminating (E4) the volunteer's last
 *    contract starts their exit journey.
 *
 * Bulk imports write with the query builder and fire none of this.
 */
final class JourneyHooks
{
    public static function register(): void
    {
        Contract::saved(static function (Contract $contract): void {
            self::startOnboarding($contract);
        });

        Contract::updated(static function (Contract $contract): void {
            self::afterUpdate($contract);
        });
    }

    private static function startOnboarding(Contract $contract): void
    {
        $attributes = $contract->getAttributes();

        // A write on a partly selected row (a toggle) says nothing about this.
        if (! array_key_exists('type', $attributes) || ! array_key_exists('is_active', $attributes) || ! array_key_exists('employee_id', $attributes)) {
            return;
        }

        if ((int) $attributes['type'] === ContractTypeEnum::Draft->value || ! $contract->is_active) {
            return;
        }

        if (! $contract->wasRecentlyCreated && ! $contract->wasChanged(['type', 'is_active'])) {
            return;
        }

        $employee = Employee::query()->find($contract->employee_id);

        if ($employee === null || $employee->isDraft()) {
            return;
        }

        app(OnboardingJourneys::class)->startForContract($employee, $contract);
    }

    private static function afterUpdate(Contract $contract): void
    {
        // Read what changed from this instance (it may be a partly selected
        // row), then work on the full row.
        $autoRenewStopped = $contract->wasChanged('auto_renew')
            && (bool) $contract->getOriginal('auto_renew')
            && ! $contract->getAttributes()['auto_renew'];
        $terminatedNow = $contract->wasChanged(['terminated_at', 'termination_reason_id'])
            && blank($contract->getOriginal('terminated_at'))
            && blank($contract->getOriginal('termination_reason_id'));

        if (! $autoRenewStopped && ! $terminatedNow) {
            return;
        }

        $full = Contract::query()->find($contract->getKey());

        if ($full === null) {
            return;
        }

        if ($autoRenewStopped) {
            $employee = Employee::query()->find($full->employee_id);

            if ($employee !== null) {
                app(ChecklistWorkflow::class)->requestBelongingsReturn($employee);
            }
        }

        $trigger = match (true) {
            $terminatedNow && $full->isTerminated() => OffboardingJourney::TRIGGER_TERMINATED,
            $autoRenewStopped => OffboardingJourney::TRIGGER_AUTO_RENEW_STOPPED,
            default => null,
        };

        if ($trigger !== null) {
            $actor = auth()->user();
            app(OffboardingJourneys::class)->startForDeparture($full, $trigger, $actor instanceof User ? $actor : null);
        }
    }
}
