<?php

declare(strict_types=1);

namespace Modules\Hr\Support\Journeys;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Core\Models\User;
use Modules\Hr\Enums\ContractType as ContractTypeEnum;
use Modules\Hr\Models\Checklist;
use Modules\Hr\Models\Contract;
use Modules\Hr\Models\Employee;
use Modules\Hr\Models\OffboardingJourney;
use Modules\Hr\Support\Recruitment\ActivityLogger;

/**
 * The exit journey of a departing volunteer (the old App\Services\OffboardingJourneys,
 * DEC-09 increment 2):
 *
 *     equipment returned -> contract ended -> deactivated -> access and card closed
 *                                                  (+ optional exit interview)
 *
 * Each step is read from its own record: checklists (equipment), the contract
 * the volunteer leaves on, employees.is_active, the login (refused by the hub
 * for an inactive employee) and employees.digital_id_enabled. The journey row
 * only stores the exit interview.
 *
 * It also owns the departure rule `hr:deactivate-departed` applies: who has
 * left (no current, future or pending auto-renewed real contract), when the
 * last contract ended, and when they are due to be deactivated
 * (config('journeys.exit_grace_days') later).
 */
class OffboardingJourneys
{
    public const STEPS = ['equipment', 'contract', 'deactivated', 'access', 'interview'];

    /** Steps that must be done for the journey to be complete; the interview is optional. */
    public const REQUIRED_STEPS = ['equipment', 'contract', 'deactivated', 'access'];

    public const STATE_DONE = 'done';

    public const STATE_TODO = 'todo';

    public const STATE_WAITING = 'waiting';

    public const STATE_PROBLEM = 'problem';

    public function __construct(private readonly ActivityLogger $activity) {}

    public function graceDays(): int
    {
        return max(0, (int) config('journeys.exit_grace_days', 7));
    }

    public function today(): CarbonInterface
    {
        return now(config('app.timezone'))->startOfDay();
    }

    /**
     * Has $employee left? Null while any real contract is current, starts in
     * the future, or has ended but is still due to auto-renew. Otherwise the
     * contract they left on, the day it ended and the day they are due to be
     * deactivated.
     *
     * @param  Collection<int, Contract>|null  $contracts  the employee's real contracts, if already loaded
     * @return array{contract: Contract, ended_on: CarbonInterface, deactivate_on: CarbonInterface}|null
     */
    public function departure(Employee $employee, ?CarbonInterface $today = null, ?Collection $contracts = null): ?array
    {
        $today ??= $this->today();
        $contracts ??= $employee->realContracts()->get();

        if ($contracts->isEmpty() || $contracts->contains(fn (Contract $c): bool => $this->continues($c, $contracts, $today))) {
            return null;
        }

        /** @var Contract $last */
        $last = $contracts
            ->sortBy([
                fn (Contract $a, Contract $b): int => $this->endsOn($a, $today)?->toDateString() <=> $this->endsOn($b, $today)?->toDateString(),
                fn (Contract $a, Contract $b): int => $a->id <=> $b->id,
            ])
            ->last();

        $endedOn = $this->endsOn($last, $today) ?? $today->copy();

        return [
            'contract' => $last,
            'ended_on' => $endedOn,
            'deactivate_on' => $endedOn->copy()->addDays($this->graceDays()),
        ];
    }

    /** Current or future: not terminated, and starting after today or active and not past its end. */
    public function isCurrentOrFuture(Contract $contract, CarbonInterface $today): bool
    {
        if ($contract->isTerminated()) {
            return false;
        }

        if ($contract->start_date !== null && $contract->start_date->toDateString() > $today->toDateString()) {
            return true;
        }

        return (bool) $contract->is_active
            && ($contract->end_date === null || $contract->end_date->toDateString() >= $today->toDateString());
    }

    /**
     * Ended, set to auto-renew, and its renewal not made yet.
     *
     * @param  Collection<int, Contract>  $contracts
     */
    public function awaitsAutoRenewal(Contract $contract, Collection $contracts, CarbonInterface $today): bool
    {
        if (! $this->willRollOver($contract) || $contract->end_date === null
            || $contract->end_date->toDateString() >= $today->toDateString()) {
            return false;
        }

        $successorStart = $contract->end_date->copy()->addDay()->toDateString();

        return ! $contracts->contains(fn (Contract $other): bool => $other->id !== $contract->id
            && (int) $other->position_id === (int) $contract->position_id
            && $other->start_date?->toDateString() === $successorStart);
    }

    /**
     * The day a contract stops (or stopped) counting: the termination date
     * when one is recorded (or the end date if that came first), otherwise
     * the end date. A contract switched off early without a date stopped the
     * day it was switched off.
     */
    public function endsOn(Contract $contract, ?CarbonInterface $today = null): ?CarbonInterface
    {
        $today ??= $this->today();
        $end = $contract->end_date?->copy()->startOfDay();

        if ($contract->terminated_at !== null) {
            $terminated = $contract->terminated_at->copy()->startOfDay();

            return $end !== null && $end->lt($terminated) ? $end : $terminated;
        }

        if ($this->isCurrentOrFuture($contract, $today)) {
            return $end;
        }

        if ($end !== null && $end->toDateString() < $today->toDateString()) {
            return $end;
        }

        $off = $contract->updated_at?->copy()->timezone(config('app.timezone'))->startOfDay() ?? $today->copy();

        return $end !== null && $end->lt($off) ? $end : $off;
    }

    public function willRollOver(Contract $contract): bool
    {
        return (bool) $contract->auto_renew && $contract->canAutoRenew();
    }

    /**
     * Start the exit journey for the departure $contract makes, if it is the
     * volunteer's last. Returns the existing journey when this departure
     * already has one, and null when $contract is not a departure.
     */
    public function startForDeparture(Contract $contract, string $trigger, ?User $actor = null): ?OffboardingJourney
    {
        if ((int) $contract->type === ContractTypeEnum::Draft->value || $this->willRollOver($contract)) {
            return null;
        }

        $employee = Employee::query()->find($contract->employee_id);

        if ($employee === null || $employee->isDraft()) {
            return null;
        }

        $today = $this->today();
        $contracts = $employee->realContracts()->get();

        if ($contracts->contains(fn (Contract $other): bool => $other->id !== $contract->id && $this->continues($other, $contracts, $today))) {
            return null;
        }

        $existing = $this->journeyFor($employee->id, $contract->id);

        if ($existing !== null) {
            return $existing;
        }

        $journey = new OffboardingJourney;
        $journey->forceFill([
            'employee_id' => $employee->id,
            'contract_id' => $contract->id,
            'trigger' => $trigger,
            'started_at' => now(),
            'started_by' => $actor?->id,
        ]);

        try {
            $journey->save();
        } catch (UniqueConstraintViolationException) {
            return $this->journeyFor($employee->id, $contract->id);
        }

        $this->record($journey, 'offboarding_started', $actor, ['contract_id' => $contract->id, 'trigger' => $trigger]);

        return $journey;
    }

    public function markExitInterviewHeld(Employee $employee, User $actor): bool
    {
        return $this->change($employee, $actor, 'exit_interview_held', function (OffboardingJourney $journey) use ($actor): ?array {
            if ($journey->exit_interview_held_at !== null) {
                return null;
            }

            $journey->forceFill([
                'exit_interview_held_at' => now(),
                'exit_interview_by' => $actor->id,
            ]);

            return [];
        });
    }

    /** Undo a mark made by mistake. The log keeps both the mark and the undo. */
    public function reopen(Employee $employee, string $step, User $actor): bool
    {
        return $this->change($employee, $actor, 'step_reopened', function (OffboardingJourney $journey) use ($step): ?array {
            if ($step !== 'interview' || $journey->exit_interview_held_at === null) {
                return null;
            }

            $journey->forceFill(['exit_interview_held_at' => null, 'exit_interview_by' => null]);

            return ['step' => $step];
        });
    }

    /**
     * Deactivate $employee if they left at least the grace period ago. Locks
     * the employee row and re-reads the departure under the lock, so a
     * contract saved meanwhile wins. Switches the ID card off, as a manual
     * deactivation does (ToggleEmployeeStatus); the hub refuses sign-in for an
     * inactive employee by itself.
     */
    public function deactivateDeparted(Employee $employee, ?CarbonInterface $today = null): bool
    {
        $today ??= $this->today();

        $done = DB::transaction(function () use ($employee, $today): bool {
            $locked = Employee::query()->whereKey($employee->id)->lockForUpdate()->first();

            if ($locked === null || ! $locked->is_active || $locked->isDraft()) {
                return false;
            }

            $departure = $this->departure($locked, $today);

            if ($departure === null || $departure['deactivate_on']->toDateString() > $today->toDateString()) {
                return false;
            }

            $locked->is_active = false;

            if ($locked->digital_id_enabled) {
                $locked->forceFill(['digital_id_enabled' => false, 'digital_id_revoked_at' => now()]);
            }

            $locked->save();

            $journey = $this->journeyFor($locked->id, $departure['contract']->id);

            $this->activity->log('employee_deactivated', $locked, null, [
                'employee_id' => $locked->id,
                'reason' => 'last_contract_ended',
                'contract_id' => $departure['contract']->id,
                'ended_on' => $departure['ended_on']->toDateString(),
                'grace_days' => $this->graceDays(),
                'journey_id' => $journey?->id,
            ], 'offboarding');

            return true;
        });

        if ($done) {
            Employee::forgetPickerOptions();
        }

        return $done;
    }

    /**
     * A departure no longer stands: auto-renew was switched back on, or another
     * contract carries on. Never true once the journey was completed.
     *
     * @param  Collection<int, Contract>|null  $contracts
     */
    public function isCalledOff(OffboardingJourney $journey, ?Collection $contracts = null, ?CarbonInterface $today = null): bool
    {
        if ($journey->completed_at !== null) {
            return false;
        }

        $today ??= $this->today();
        $contracts ??= Contract::query()
            ->where('employee_id', $journey->employee_id)
            ->where('type', '!=', ContractTypeEnum::Draft->value)
            ->get();

        $contract = $contracts->firstWhere('id', $journey->contract_id);

        if ($contract !== null && $this->willRollOver($contract)) {
            return true;
        }

        return $contracts->contains(fn (Contract $other): bool => $other->id !== $journey->contract_id
            && $this->continues($other, $contracts, $today));
    }

    /** The employee's latest exit journey, with the interviewer. */
    public function latestFor(int $employeeId): ?OffboardingJourney
    {
        return OffboardingJourney::query()
            ->where('employee_id', $employeeId)
            ->with('exitInterviewBy:id,email,employee_id')
            ->latest('id')
            ->first();
    }

    /**
     * Where the departing volunteer stands, step by step. Null for anyone
     * with no exit journey.
     *
     * @param  Collection<int, Contract>|null  $contracts  the employee's real contracts, if already loaded
     * @return list<array{key: string, state: string, owner: string, due: ?CarbonInterface, late: bool, blocked_by: ?string, note: ?string, data: array<string, mixed>}>|null
     */
    public function steps(Employee $employee, ?OffboardingJourney $journey, ?Collection $contracts = null): ?array
    {
        if ($journey === null) {
            return null;
        }

        $today = $this->today();
        $contracts ??= $employee->realContracts()->get();
        $contract = $contracts->firstWhere('id', $journey->contract_id);
        $endsOn = $contract !== null ? $this->endsOn($contract, $today) : null;
        $departure = $this->departure($employee, $today, $contracts);
        $deactivateOn = $departure['deactivate_on'] ?? $endsOn?->copy()->addDays($this->graceDays());

        $steps = [];

        // 1. Equipment: every delivered item has a received return.
        $outstanding = Checklist::query()
            ->where('employee_id', $employee->id)
            ->where('delivered_status', 'delivered')
            ->where(fn (Builder $q) => $q->whereNull('returned_status')->orWhere('returned_status', '!=', 'received'))
            ->get(['id', 'returned_status']);
        $unrequested = $outstanding->whereNull('returned_status')->count();

        $steps[] = $this->step('equipment', match (true) {
            $outstanding->isEmpty() => self::STATE_DONE,
            $unrequested > 0 => self::STATE_TODO,
            default => self::STATE_WAITING,
        }, $outstanding->isNotEmpty() && $unrequested === 0 ? 'receiver' : 'hr', due: $endsOn, late: $this->isPast($endsOn, $today), data: [
            'outstanding' => $outstanding->count(),
            'unrequested' => $unrequested,
        ]);

        // 2. Contract ended.
        $contractEnded = $contract !== null
            ? ! $this->isCurrentOrFuture($contract, $today) && $endsOn !== null && $endsOn->toDateString() <= $today->toDateString()
            : $departure !== null;

        $steps[] = $this->step('contract', $contractEnded ? self::STATE_DONE : self::STATE_WAITING, 'system', due: $endsOn, data: [
            'contract_id' => $contract?->id,
        ]);

        // 3. Deactivated: automatic once the grace period after the end has passed.
        $deactivated = ! $employee->is_active;
        $deactivationOverdue = ! $deactivated && $contractEnded && $this->isPast($deactivateOn, $today);

        $steps[] = $this->step('deactivated', match (true) {
            $deactivated => self::STATE_DONE,
            $deactivationOverdue => self::STATE_PROBLEM,
            default => self::STATE_WAITING,
        }, 'system', due: $deactivateOn, late: $deactivationOverdue, blockedBy: $contractEnded ? null : 'contract', note: $deactivationOverdue ? 'deactivation-overdue' : null, data: [
            'deactivate_on' => $deactivateOn,
        ]);

        // 4. Access and card closed. The hub refuses sign-in to an inactive
        // employee, so the login closes with the deactivation; the card is
        // its own switch.
        $loginOpen = (bool) $employee->is_active && $employee->account()->exists();
        $cardOn = $employee->digital_id_enabled !== false;
        $accessClosed = ! $loginOpen && ! $cardOn;

        $steps[] = $this->step('access', match (true) {
            $accessClosed => self::STATE_DONE,
            $deactivated => self::STATE_PROBLEM,
            default => self::STATE_WAITING,
        }, 'system', due: $deactivateOn, late: $deactivated && ! $accessClosed, blockedBy: $deactivated ? null : 'deactivated', note: match (true) {
            $accessClosed || ! $deactivated => null,
            default => 'access-card-on',
        });

        // 5. Exit interview (optional): the one step this journey records.
        $steps[] = $this->step('interview', $journey->exit_interview_held_at !== null ? self::STATE_DONE : self::STATE_TODO, 'hr', data: [
            'optional' => true,
            'held_at' => $journey->exit_interview_held_at,
            'held_by' => $journey->exitInterviewBy,
        ]);

        return $steps;
    }

    /**
     * Record completion the first time every required step is done. Sticky:
     * a later re-hire does not reopen a finished departure.
     */
    public function settle(OffboardingJourney $journey, ?User $actor = null): bool
    {
        if ($journey->completed_at !== null) {
            return false;
        }

        $employee = Employee::query()->find($journey->employee_id);
        $latest = $employee === null ? null : $this->latestFor($employee->id);

        if ($employee === null || $latest?->id !== $journey->id) {
            return false;
        }

        $contracts = $employee->realContracts()->get();

        if ($this->isCalledOff($latest, $contracts)) {
            return false;
        }

        $steps = collect($this->steps($employee, $latest, $contracts) ?? [])->keyBy('key');
        $complete = collect(self::REQUIRED_STEPS)->every(fn (string $key): bool => ($steps[$key]['state'] ?? null) === self::STATE_DONE);

        if (! $complete) {
            return false;
        }

        $done = OffboardingJourney::query()
            ->whereKey($journey->id)
            ->whereNull('completed_at')
            ->update(['completed_at' => now(), 'updated_at' => now()]);

        if ($done !== 1) {
            return false;
        }

        $journey->refresh();
        $this->record($journey, 'offboarding_completed', $actor, []);

        return true;
    }

    /** @param  Collection<int, Contract>  $contracts */
    private function continues(Contract $contract, Collection $contracts, CarbonInterface $today): bool
    {
        return $this->isCurrentOrFuture($contract, $today) || $this->awaitsAutoRenewal($contract, $contracts, $today);
    }

    private function isPast(?CarbonInterface $date, CarbonInterface $today): bool
    {
        return $date !== null && $date->toDateString() < $today->toDateString();
    }

    private function journeyFor(int $employeeId, int $contractId): ?OffboardingJourney
    {
        return OffboardingJourney::query()
            ->where('employee_id', $employeeId)
            ->where('contract_id', $contractId)
            ->first();
    }

    /**
     * @param  callable(OffboardingJourney): ?array<string, mixed>  $apply  returns the log properties, or null for "nothing to change"
     */
    private function change(Employee $employee, User $actor, string $event, callable $apply): bool
    {
        return DB::transaction(function () use ($employee, $actor, $event, $apply): bool {
            $journey = OffboardingJourney::query()
                ->where('employee_id', $employee->id)
                ->latest('id')
                ->lockForUpdate()
                ->first();

            if ($journey === null || $this->isCalledOff($journey)) {
                return false;
            }

            $properties = $apply($journey);

            if ($properties === null) {
                return false;
            }

            $journey->save();
            $this->record($journey, $event, $actor, $properties);
            $this->settle($journey, $actor);

            return true;
        });
    }

    /**
     * @param  array<string, mixed>  $properties
     */
    private function record(OffboardingJourney $journey, string $event, ?User $actor, array $properties): void
    {
        $this->activity->log($event, $journey, $actor, ['employee_id' => $journey->employee_id] + $properties, 'offboarding');
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{key: string, state: string, owner: string, due: ?CarbonInterface, late: bool, blocked_by: ?string, note: ?string, data: array<string, mixed>}
     */
    private function step(string $key, string $state, string $owner, ?CarbonInterface $due = null, bool $late = false, ?string $blockedBy = null, ?string $note = null, array $data = []): array
    {
        return [
            'key' => $key,
            'state' => $state,
            'owner' => $owner,
            'due' => $due,
            'late' => $late && $state !== self::STATE_DONE,
            'blocked_by' => $state === self::STATE_WAITING ? $blockedBy : null,
            'note' => $note,
            'data' => $data,
        ];
    }
}
