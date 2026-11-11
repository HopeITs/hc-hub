<?php

declare(strict_types=1);

namespace Modules\Hr\Support\Journeys;

use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Modules\Core\Models\User;
use Modules\Hr\Models\Contract;
use Modules\Hr\Models\Employee;
use Modules\Hr\Models\OnboardingJourney;
use Modules\Hr\Support\Employees\DigitalId;
use Modules\Hr\Support\Recruitment\ActivityLogger;
use Modules\Hr\Support\Recruitment\OnboardingStarter;

/**
 * The onboarding journey of a new volunteer (the old App\Services\OnboardingJourneys,
 * D4-I, DEC-09):
 *
 *     record -> contract -> login account -> ID card -> orientation session
 *
 * Each step has one authoritative record, and this class never copies one
 * into another:
 *
 *  - record       employees.record_status
 *  - contract     the first real contract
 *  - account      users.employee_id (ActivateEmployeeForContract)
 *  - card         digital: DigitalId; printed + handed over: this journey
 *  - orientation  this journey
 *
 * The only writes here are the journey's own two steps. Each one is
 * serialized on the journey row and conditional on the state it moves from, so
 * a retried or double-clicked action changes nothing the second time and
 * reports that it did not. Every change is logged under "onboarding" with the
 * person who made it. Authorization is the caller's job (EmployeePolicy::update).
 */
class OnboardingJourneys
{
    public const ORIENTATION_DAYS = OnboardingStarter::ORIENTATION_DAYS;

    public const STEPS = ['record', 'contract', 'account', 'card', 'orientation'];

    public const STATE_DONE = 'done';

    public const STATE_TODO = 'todo';

    public const STATE_WAITING = 'waiting';

    public const STATE_PROBLEM = 'problem';

    public function __construct(private readonly ActivityLogger $activity) {}

    /**
     * Start the journey for a volunteer whose first real contract just
     * activated them (the same rule as OnboardingStarter, which hiring uses).
     */
    public function startForContract(Employee $employee, Contract $contract): ?OnboardingJourney
    {
        return app(OnboardingStarter::class)($employee, $contract);
    }

    public function scheduleOrientation(Employee $employee, CarbonInterface $date, User $actor): bool
    {
        return $this->change($employee, $actor, 'orientation_scheduled', function (OnboardingJourney $journey) use ($date, $actor): ?array {
            if ($journey->orientation_completed_at !== null
                || $journey->orientation_scheduled_for?->isSameDay($date)) {
                return null;
            }

            $from = $journey->orientation_scheduled_for?->toDateString();
            $journey->forceFill([
                'orientation_scheduled_for' => $date->toDateString(),
                'orientation_scheduled_by' => $actor->id,
            ]);

            return ['from' => $from, 'to' => $date->toDateString()];
        });
    }

    /** Only on or after the session's date: a session cannot be recorded as held before it happens. */
    public function completeOrientation(Employee $employee, User $actor): bool
    {
        return $this->change($employee, $actor, 'orientation_completed', function (OnboardingJourney $journey) use ($actor): ?array {
            if ($journey->orientation_completed_at !== null
                || $journey->orientation_scheduled_for === null
                || $journey->orientation_scheduled_for->gt(now(config('app.timezone'))->startOfDay())) {
                return null;
            }

            $journey->forceFill([
                'orientation_completed_at' => now(),
                'orientation_completed_by' => $actor->id,
            ]);

            return ['held_on' => $journey->orientation_scheduled_for->toDateString()];
        });
    }

    /** Only once the digital card works: the printed card carries its link. */
    public function markCardHandedOver(Employee $employee, User $actor): bool
    {
        if (! $this->digitalCardReady($employee)) {
            return false;
        }

        return $this->change($employee, $actor, 'card_handed_over', function (OnboardingJourney $journey) use ($actor): ?array {
            if ($journey->card_handed_over_at !== null) {
                return null;
            }

            $journey->forceFill([
                'card_handed_over_at' => now(),
                'card_handed_over_by' => $actor->id,
            ]);

            return [];
        });
    }

    /** Undo a step marked by mistake. The log keeps both the mark and the undo. */
    public function reopen(Employee $employee, string $step, User $actor): bool
    {
        return $this->change($employee, $actor, 'step_reopened', function (OnboardingJourney $journey) use ($step): ?array {
            if ($step === 'orientation' && $journey->orientation_completed_at !== null) {
                $journey->forceFill(['orientation_completed_at' => null, 'orientation_completed_by' => null]);

                return ['step' => $step];
            }

            if ($step === 'card' && $journey->card_handed_over_at !== null) {
                $journey->forceFill(['card_handed_over_at' => null, 'card_handed_over_by' => null]);

                return ['step' => $step];
            }

            return null;
        });
    }

    /**
     * The volunteer's journey row, with who did what. Null when there is none.
     */
    public function journeyFor(int $employeeId): ?OnboardingJourney
    {
        return OnboardingJourney::query()
            ->where('employee_id', $employeeId)
            ->with([
                'contract:id,employee_id,start_date',
                'cardHandedOverBy:id,email,employee_id',
                'orientationCompletedBy:id,email,employee_id',
            ])
            ->first();
    }

    /**
     * Where the volunteer stands, step by step. Null for staff who never had a
     * journey (existing staff, former staff re-hired), so nobody already
     * working sees one.
     *
     * @return list<array{key: string, state: string, owner: string, due: ?CarbonInterface, late: bool, blocked_by: ?string, note: ?string, data: array<string, mixed>}>|null
     */
    public function steps(Employee $employee, ?OnboardingJourney $journey): ?array
    {
        if ($journey === null && ! $employee->isDraft() && ! $employee->isAwaitingContract()) {
            return null;
        }

        $isDraft = $employee->isDraft();
        $contractId = $journey?->contract_id ?? $employee->realContracts()->orderBy('start_date')->orderBy('id')->value('id');
        $hasContract = $contractId !== null;
        $hasAccount = $employee->account()->exists();

        $steps = [];

        $steps[] = $this->step('record', $isDraft ? self::STATE_TODO : self::STATE_DONE, 'hr');

        $steps[] = $this->step('contract', match (true) {
            $hasContract => self::STATE_DONE,
            $isDraft => self::STATE_WAITING,
            default => self::STATE_TODO,
        }, 'hr', blockedBy: $isDraft ? 'record' : null, data: ['contract_id' => $contractId]);

        $steps[] = $this->step('account', match (true) {
            $hasAccount => self::STATE_DONE,
            $hasContract => self::STATE_PROBLEM,
            default => self::STATE_WAITING,
        }, 'system', blockedBy: $hasContract ? null : 'contract', note: ! $hasAccount && $hasContract ? 'account-missing' : null);

        $cardReady = $hasContract && $this->digitalCardReady($employee);
        $steps[] = $this->step('card', match (true) {
            $journey?->card_handed_over_at !== null => self::STATE_DONE,
            ! $hasContract || $journey === null => self::STATE_WAITING,
            ! $cardReady => self::STATE_PROBLEM,
            default => self::STATE_TODO,
        }, 'hr', blockedBy: $hasContract ? null : 'contract', note: $hasContract && ! $cardReady ? (DigitalId::missingLinkReason($employee) ?? 'hr_journeys.onboarding.card-not-valid') : null, data: [
            'digital_ready' => $cardReady,
            'handed_over_at' => $journey?->card_handed_over_at,
            'handed_over_by' => $journey?->cardHandedOverBy,
        ]);

        $steps[] = $this->step('orientation', match (true) {
            $journey?->orientation_completed_at !== null => self::STATE_DONE,
            $journey === null => self::STATE_WAITING,
            default => self::STATE_TODO,
        }, 'hr', due: $journey?->orientation_due_on, late: (bool) $journey?->orientationIsLate(), blockedBy: $journey !== null ? null : 'contract', data: [
            'scheduled_for' => $journey?->orientation_scheduled_for,
            'completed_at' => $journey?->orientation_completed_at,
            'completed_by' => $journey?->orientationCompletedBy,
            'can_complete' => $journey?->orientation_scheduled_for !== null
                && $journey->orientation_scheduled_for->lte(now(config('app.timezone'))->startOfDay()),
        ]);

        return $steps;
    }

    public function digitalCardReady(Employee $employee): bool
    {
        return (bool) $employee->is_active
            && $employee->digital_id_enabled !== false
            && DigitalId::cardCode($employee) !== null;
    }

    /**
     * @param  callable(OnboardingJourney): ?array<string, mixed>  $apply  returns the log properties, or null for "nothing to change"
     */
    private function change(Employee $employee, User $actor, string $event, callable $apply): bool
    {
        return DB::transaction(function () use ($employee, $actor, $event, $apply): bool {
            $journey = OnboardingJourney::query()
                ->where('employee_id', $employee->id)
                ->lockForUpdate()
                ->first();

            if ($journey === null) {
                return false;
            }

            $properties = $apply($journey);

            if ($properties === null) {
                return false;
            }

            $journey->save();
            $this->settleCompletion($journey, $employee);
            $this->activity->log($event, $journey, $actor, ['employee_id' => $journey->employee_id] + $properties, 'onboarding');

            return true;
        });
    }

    /** completed_at follows the steps, so an undo reopens the journey too. */
    private function settleCompletion(OnboardingJourney $journey, Employee $employee): void
    {
        $complete = $journey->orientation_completed_at !== null
            && $journey->card_handed_over_at !== null
            && $employee->account()->exists();

        if ($complete === ($journey->completed_at !== null)) {
            return;
        }

        $journey->forceFill(['completed_at' => $complete ? now() : null])->save();
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
