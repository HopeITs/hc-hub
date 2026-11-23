<?php

declare(strict_types=1);

namespace Modules\Hr\Support\Contracts;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Modules\Core\Models\User;
use Modules\Hr\Enums\ContractType as ContractTypeEnum;
use Modules\Hr\Models\Contract;
use Modules\Hr\Models\Employee;
use Modules\Hr\Models\ProbationReview;
use Modules\Hr\Support\Employees\HrAccess;
use Modules\Hr\Support\Journeys\DeliveryLedger;
use Modules\Hr\Support\Journeys\Notifications\JourneyNotice;
use Throwable;

/**
 * Probation review of a trial contract.
 *
 * From (start + two thirds of the term) the direct manager is asked what should
 * happen: extend, confirm or cancel. The answer is a REQUEST to HR with a
 * mandatory reason; nothing is written to the contract (no end date, no new
 * contract, no termination), because what HR does with each request is not
 * agreed yet. A trial never rolls over by itself: Contract::canAutoRenew stays
 * false, and RenewalJourneys / SendContractExpirationWarnings still skip it.
 */
class ProbationReviews
{
    public const string REMINDER_KIND = 'probation-reminder';

    public function __construct(private readonly DeliveryLedger $ledger) {}

    /** The first day of the last third: start + two thirds of the term. */
    public function lastThirdStart(Contract $contract): ?CarbonInterface
    {
        if ($contract->start_date === null || $contract->end_date === null) {
            return null;
        }

        $start = $contract->start_date->copy()->startOfDay();
        $days = $start->diffInDays($contract->end_date->copy()->startOfDay());

        return $start->addDays((int) floor($days * (float) config('probation.last_third_after')));
    }

    /**
     * Start a review for every running trial that entered its last third.
     * Idempotent (unique contract_id); a missed day is caught up on the next
     * run. Returns how many reviews were started.
     */
    public function startDue(?CarbonInterface $today = null): int
    {
        $today ??= $this->ledger->today();
        $started = 0;

        $this->trialsInForce($today)
            ->orderBy('contracts.id')
            ->each(function (Contract $contract) use ($today, &$started): void {
                $from = $this->lastThirdStart($contract);

                if ($from === null || $from->gt($today)) {
                    return;
                }

                if ($this->startFor($contract) !== null) {
                    $started++;
                }
            });

        return $started;
    }

    /** One review per contract: a retry or a second worker is a no-op. */
    public function startFor(Contract $contract): ?ProbationReview
    {
        $now = now();
        $inserted = ProbationReview::query()->insertOrIgnore([
            'contract_id' => $contract->id,
            'employee_id' => $contract->employee_id,
            'started_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        if ($inserted !== 1) {
            return null;
        }

        $review = ProbationReview::query()->where('contract_id', $contract->id)->firstOrFail();
        $contract->loadMissing('employee');

        $this->send($this->employeeUsers($contract->employee), $this->notice('probation-employee', $review, $contract, filled($contract->employee?->username) ? route('hr.employees.show', ['employee' => $contract->employee->username]) : route('dashboard')));
        $this->send($this->managerUsers($contract), $this->notice('probation-manager', $review, $contract));
        $this->send($this->hrUsers($contract->employee), $this->notice('probation-hr', $review, $contract));

        return $review;
    }

    /**
     * Remind the direct manager every N days until they answer. The ledger
     * claim is keyed to the review and to the day the Nth week falls on, so a
     * second run or worker the same day sends nothing. Returns reminders sent.
     */
    public function remindPending(?CarbonInterface $today = null): int
    {
        $today ??= $this->ledger->today();
        $every = max(1, (int) config('probation.reminder_every_days'));
        $sent = 0;

        ProbationReview::query()
            ->whereNull('decision')
            ->with(['employee.city', 'contract.position.directManager.activeContracts.employee.account'])
            ->orderBy('id')
            ->each(function (ProbationReview $review) use ($today, $every, &$sent): void {
                $contract = $review->contract;

                if (! $this->inForce($contract, $today)) {
                    return;
                }

                $since = $review->started_at->copy()->timezone(config('app.timezone'))->startOfDay();
                $weeks = intdiv((int) $since->diffInDays($today), $every);

                if ($weeks < 1) {
                    return;
                }

                $day = $since->copy()->addDays($weeks * $every);

                foreach ($this->managerUsers($contract) as $user) {
                    if ($this->ledger->claim(self::REMINDER_KIND, $user->id, [$review->id], $day) === []) {
                        continue;
                    }

                    try {
                        $user->notify($this->notice('probation-manager-reminder', $review, $contract));
                        $this->ledger->markDelivered(self::REMINDER_KIND, $user->id, [$review->id], $day);
                        $sent++;
                    } catch (Throwable $exception) {
                        $this->ledger->release(self::REMINDER_KIND, $user->id, [$review->id], $day);
                        report($exception);
                    }
                }
            });

        return $sent;
    }

    /**
     * Record the manager's request. The reason is mandatory. One answer per
     * review: a second one is refused (returns false). Nothing else changes.
     *
     * @throws ValidationException
     */
    public function request(ProbationReview $review, string $decision, string $reason, User $actor): bool
    {
        $reason = trim($reason);

        if (! in_array($decision, ProbationReview::DECISIONS, true)) {
            throw ValidationException::withMessages(['decision' => __('hr_probation.errors.decision')]);
        }

        if ($reason === '') {
            throw ValidationException::withMessages(['reason' => __('hr_probation.errors.reason')]);
        }

        $done = DB::transaction(function () use ($review, $decision, $reason, $actor): bool {
            $locked = ProbationReview::query()->lockForUpdate()->findOrFail($review->id);

            if ($locked->isDecided() || ! $this->canDecide($actor, $locked)) {
                return false;
            }

            $locked->forceFill([
                'decision' => $decision,
                'reason' => $reason,
                'decided_by' => $actor->id,
                'decided_at' => now(),
            ])->save();

            return true;
        });

        if (! $done) {
            return false;
        }

        $review->refresh()->loadMissing(['contract', 'employee.city']);

        // After the commit: a rolled-back request must not tell HR anything.
        $this->send(
            $this->hrUsers($review->employee, except: [(int) $actor->id]),
            $this->notice('probation-requested', $review, $review->contract),
        );

        return true;
    }

    /** HR opened a request: note it once. */
    public function markSeenByHr(ProbationReview $review, User $user): void
    {
        if ($review->decision === null || $review->hr_seen_at !== null || ! HrAccess::isHr($user)) {
            return;
        }

        ProbationReview::query()->whereKey($review->id)->whereNull('hr_seen_at')->update(['hr_seen_at' => now()]);
        $review->refresh();
    }

    /** The direct manager of the trial's position, or HR of its city when it has none. */
    public function canDecide(User $user, ProbationReview $review): bool
    {
        $contract = $review->contract()->with('position.directManager.activeContracts.employee')->first();
        $review->loadMissing('employee');

        if ($contract === null || ! $this->inForce($contract, $this->ledger->today())) {
            return false;
        }

        if ($this->isDirectManager($user, $contract)) {
            return true;
        }

        // HR stands in only when nobody is running the manager's position.
        return $this->managerEmployees($contract)->isEmpty()
            && HrAccess::isHr($user)
            && Gate::forUser($user)->allows('update', $review->employee);
    }

    /** Who may open the review: the manager, and HR of the employee's city. */
    public function canView(User $user, ProbationReview $review): bool
    {
        $contract = $review->contract()->with('position.directManager.activeContracts.employee')->first();
        $review->loadMissing('employee');

        if ($contract !== null && $this->isDirectManager($user, $contract)) {
            return true;
        }

        return HrAccess::isHr($user) && Gate::forUser($user)->allows('update', $review->employee);
    }

    public function isDirectManager(User $user, Contract $contract): bool
    {
        return $user->employee_id !== null
            && $this->managerEmployees($contract)->contains(fn (Employee $employee): bool => (int) $employee->id === (int) $user->employee_id);
    }

    /** @return Collection<int, Employee> */
    public function managerEmployees(Contract $contract): Collection
    {
        $contract->loadMissing('position.directManager.activeContracts.employee');

        return collect([$contract->position?->directManager])
            ->filter()
            ->flatMap(fn ($position) => $position->activeContracts)
            ->map(fn (Contract $running): ?Employee => $running->employee)
            ->filter(fn (?Employee $employee): bool => $employee?->is_active === true)
            ->values();
    }

    /** @return Collection<int, User> */
    public function managerUsers(Contract $contract): Collection
    {
        $managers = $this->managerEmployees($contract);

        return User::query()
            ->whereIn('employee_id', $managers->pluck('id')->all() ?: [0])
            ->get()
            ->unique('id')
            ->values();
    }

    /** @return Collection<int, User> */
    public function employeeUsers(?Employee $employee): Collection
    {
        return $employee === null ? collect() : User::query()->where('employee_id', $employee->id)->get();
    }

    /**
     * HR of the employee's city; HR-SYR when the city has none (as the other
     * reminders).
     *
     * @param  array<int, int|null>  $except
     * @return Collection<int, User>
     */
    public function hrUsers(?Employee $employee, array $except = []): Collection
    {
        if ($employee === null) {
            return collect();
        }

        return $this->ledger->cityHr($employee->city_id === null ? null : (int) $employee->city_id)
            ->reject(fn (User $user): bool => in_array((int) $user->id, $except, true))
            ->values();
    }

    /**
     * Unanswered reviews of a running trial whose position reports to a
     * position the user holds now: the rows the manager's inbox lists.
     *
     * @return Builder<ProbationReview>
     */
    public function pendingFor(User $user): Builder
    {
        return ProbationReview::query()
            ->whereNull('decision')
            ->whereHas('contract', fn (Builder $contract) => $contract
                ->where('is_active', true)
                ->whereNull('terminated_at')
                ->whereNull('termination_reason_id')
                ->whereDate('end_date', '>=', $this->ledger->today()->toDateString()))
            ->whereHas('contract.position.directManager.contracts', fn (Builder $running) => $running
                ->activeNow()
                ->where('employee_id', (int) $user->employee_id));
    }

    /** Running, real, not terminated, not ended: the contract still has a last third to review. */
    private function inForce(?Contract $contract, CarbonInterface $today): bool
    {
        return $contract !== null
            && $contract->is_active
            && ! $contract->isTerminated()
            && $contract->isTrial()
            && $contract->end_date !== null
            && $contract->end_date->copy()->startOfDay()->gte($today);
    }

    /** @return Builder<Contract> */
    private function trialsInForce(CarbonInterface $today): Builder
    {
        $trialTypeIds = collect(config('contracts.map_by_id', []))
            ->filter(fn (string $category): bool => $category === 'trial')
            ->keys()
            ->all();

        return Contract::query()
            ->with(['employee.city', 'position.directManager.activeContracts.employee'])
            ->where('contracts.is_active', true)
            ->where(fn (Builder $query) => $query->whereNull('contracts.type')->orWhere('contracts.type', '!=', ContractTypeEnum::Draft->value))
            ->whereNull('contracts.terminated_at')
            ->whereNull('contracts.termination_reason_id')
            ->whereNotNull('contracts.start_date')
            ->whereNotNull('contracts.end_date')
            ->whereDate('contracts.start_date', '<=', $today->toDateString())
            ->whereDate('contracts.end_date', '>=', $today->toDateString())
            ->whereIn('contracts.contract_type_id', $trialTypeIds === [] ? [0] : $trialTypeIds)
            ->whereNotExists(fn ($sub) => $sub->selectRaw('1')
                ->from('probation_reviews')
                ->whereColumn('probation_reviews.contract_id', 'contracts.id'));
    }

    /** The notice of one audience: the page it opens is the review, or the profile for the volunteer. */
    private function notice(string $kind, ProbationReview $review, ?Contract $contract, ?string $url = null): JourneyNotice
    {
        $review->loadMissing('employee');
        $employee = $review->employee;
        $end = $contract?->end_date;
        $request = $review->decision === null ? '' : $review->decision;

        return new JourneyNotice($kind, [
            'name' => ['en' => $employee?->nameForLocale('en') ?? '', 'ar' => $employee?->nameForLocale('ar') ?? ''],
            'date' => $end?->format('d-m-Y') ?? '',
            'request' => ['en' => $request === '' ? '' : __('hr_probation.decision.'.$request, [], 'en'), 'ar' => $request === '' ? '' : __('hr_probation.decision.'.$request, [], 'ar')],
            'reason' => (string) $review->reason,
        ], $url ?? route('hr.probation.show', $review));
    }

    /**
     * A failed notification never undoes the review that triggered it.
     *
     * @param  Collection<int, User>  $users
     */
    private function send(Collection $users, JourneyNotice $notice): void
    {
        foreach ($users as $user) {
            try {
                $user->notify(clone $notice);
            } catch (Throwable $exception) {
                report($exception);
            }
        }
    }
}
