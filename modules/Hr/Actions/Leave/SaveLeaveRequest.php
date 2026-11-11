<?php

declare(strict_types=1);

namespace Modules\Hr\Actions\Leave;

use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Modules\Core\Models\User;
use Modules\Hr\Enums\LeaveRequestStatus;
use Modules\Hr\Models\Contract;
use Modules\Hr\Models\Employee;
use Modules\Hr\Models\LeaveBalance;
use Modules\Hr\Models\LeaveRequest;
use Modules\Hr\Support\Leave\LeaveAlternativeEmployees;
use Modules\Hr\Support\Leave\LeaveContext;
use Modules\Hr\Support\Leave\LeaveEligibility;
use Modules\Hr\Support\Leave\LeaveNotificationService;
use Modules\Hr\Support\Leave\LeaveQuote;
use Modules\Hr\Support\Leave\LeaveRequestDraft;
use Modules\Hr\Support\Leave\LeaveRequestPricing;
use Modules\Hr\Support\Leave\LeaveWindow;
use Modules\Hr\Support\Settings\HrAccess;

/**
 * Files or edits a leave request (the old LeaveRequestForm store/update).
 *
 * A new request: priced by LeaveRequestPricing under the employee and balance
 * locks, refused when it overlaps the employee's own pending or accepted
 * request, when the balance is not requestable (LeaveEligibility) or when the
 * quote has a blocking error; it is always pending and stores the quote as
 * its snapshot. A submission token makes a double click or a retried request
 * create one row and one set of notifications.
 *
 * An edit: a pending request is re-priced; an accepted one (HR or a deciding
 * manager) moves `used` by new − old when its period, unit, balance or
 * December flag changed, and prices nothing for a content-only change.
 *
 * Validation errors use the form's snake_case field names.
 */
final class SaveLeaveRequest
{
    private const EPSILON = 0.005;

    public function __construct(
        private readonly LeaveRequestPricing $pricing,
        private readonly LeaveEligibility $eligibility,
        private readonly LeaveNotificationService $notifications,
    ) {}

    /**
     * The employee a new request is for: the one HR picked (inside its
     * governorates), else the actor's own record.
     *
     * @throws AuthorizationException
     */
    public function targetEmployee(User $actor, ?int $employeeId): Employee
    {
        if ($employeeId !== null && $employeeId !== (int) $actor->employee_id) {
            Gate::forUser($actor)->authorize('createForOthers', LeaveRequest::class);

            $employee = HrAccess::for($actor)
                ->scopeToCities(LeaveRequestDraft::employeeQuery())
                ->where('is_active', true)
                ->find($employeeId);
        } else {
            if (! $actor->can('leaves.create')) {
                throw new AuthorizationException;
            }

            $employee = $actor->employee_id === null ? null : LeaveRequestDraft::employeeQuery()->find($actor->employee_id);
        }

        if (! $employee instanceof Employee) {
            throw ValidationException::withMessages(['employee_id' => __('hr_leave.validation.no-employee')]);
        }

        return $employee;
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public function create(User $actor, ?int $employeeId, array $input, ?string $token = null): LeaveRequest
    {
        $employee = $this->targetEmployee($actor, $employeeId);
        $key = $token === null ? null : self::submissionKey((int) $actor->id, $token);

        if ($key === null) {
            return $this->createOnce($actor, $employee, $input, null);
        }

        try {
            return Cache::lock($key.':lock', 30)->block(10, fn () => $this->createOnce($actor, $employee, $input, $key));
        } catch (LockTimeoutException) {
            throw ValidationException::withMessages(['submission' => __('hr_leave.submit.in-progress')]);
        }
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public function update(User $actor, int $leaveRequestId, array $input): LeaveRequest
    {
        $current = LeaveContext::request($leaveRequestId);
        Gate::forUser($actor)->authorize('update', $current);

        $employee = $current->leaveBalance->contract->employee;
        $draft = LeaveRequestDraft::fromArray($input);
        $contract = $this->validate($draft, $employee, $current);
        $balance = $this->balanceOf($contract, $draft);
        $window = $this->requireWindow($draft);

        $saved = DB::transaction(function () use ($actor, $leaveRequestId, $employee, $draft, $balance, $window): LeaveRequest {
            // Lock order: request, then employee, then balance(s).
            $original = LeaveContext::request($leaveRequestId, lock: true);
            Gate::forUser($actor)->authorize('update', $original);

            $originalBalanceId = (int) $original->leave_balance_id;
            $this->lockRequesterRows((int) $employee->id, (int) $balance->id, $originalBalanceId);
            $this->reloadUsed($balance);
            $this->assertNoOwnOverlap((int) $employee->id, $draft, $original->id);

            if ($originalBalanceId !== (int) $balance->id) {
                $this->assertEligible($employee, $balance);
            }

            $accepted = (int) $original->status === LeaveRequestStatus::Accepted->value;

            if ($accepted && ! $draft->differsFrom($original, $balance, $window)) {
                $original->update($this->requestData($draft));

                return $original;
            }

            $quote = $this->pricing->quote($balance->contract, $balance, $window, $original);

            if (! $accepted) {
                $this->assertQuoteAllows($quote, $balance, $window);
                $original->update($this->requestData($draft) + $this->snapshot($quote, $window));

                return $original;
            }

            $old = $this->pricing->deductionHours($original);
            $sameBalance = $originalBalanceId === (int) $balance->id;
            $this->assertQuoteAllows($quote, $balance, $window, releasedHours: $sameBalance ? $old : 0.0, committedOnly: true);
            $original->update($this->requestData($draft) + $this->snapshot($quote, $window));

            if ($sameBalance) {
                $this->adjustUsed($balance, $quote->requestedHours - $old);
            } else {
                $this->adjustUsed(LeaveBalance::query()->findOrFail($originalBalanceId), -$old);
                $this->adjustUsed($balance, $quote->requestedHours);
            }

            return $original;
        });

        return $saved;
    }

    /**
     * The live impact summary of a form (null for a partial or invalid form).
     *
     * @param  array<string, mixed>  $input
     */
    public function preview(Employee $employee, array $input, ?LeaveRequest $editing = null): ?LeaveQuote
    {
        try {
            $draft = LeaveRequestDraft::fromArray($input);

            if (blank($draft->leaveBalanceId) || $draft->spanTooLong() || ($window = $draft->window()) === null) {
                return null;
            }

            $balance = LeaveContext::contractOf((int) $employee->id)?->leaveBalances->firstWhere('id', (int) $draft->leaveBalanceId);

            return $balance === null ? null : $this->pricing->quote($balance->contract, $balance, $window, $editing);
        } catch (\Throwable $exception) {
            report($exception);

            return null;
        }
    }

    public static function submissionKey(int $userId, string $token): string
    {
        return 'hr-leave-request-submission:'.$userId.':'.sha1($token);
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private function createOnce(User $actor, Employee $employee, array $input, ?string $key): LeaveRequest
    {
        if ($key !== null && ($existing = $this->consumed($key, (int) $employee->id)) !== null) {
            return $existing;
        }

        $draft = LeaveRequestDraft::fromArray($input);
        $contract = $this->validate($draft, $employee, null);
        $balance = $this->balanceOf($contract, $draft);
        $window = $this->requireWindow($draft);
        [$start, $end] = $draft->storedPeriod();

        $leaveRequest = DB::transaction(function () use ($employee, $draft, $balance, $window, $start, $end): LeaveRequest {
            $this->lockRequesterRows((int) $employee->id, (int) $balance->id);
            $this->reloadUsed($balance);
            $this->assertNoOwnOverlap((int) $employee->id, $draft);
            $this->assertEligible($employee, $balance);

            $quote = $this->pricing->quote($balance->contract, $balance, $window);
            $this->assertQuoteAllows($quote, $balance, $window);

            // status / approved_* never come from the form: a new request is pending.
            return LeaveRequest::query()->create([
                'leave_balance_id' => $balance->id,
                'type' => $draft->type,
                'start_at' => $start,
                'end_at' => $end,
                'reason' => $draft->reason,
                'alternative_employee_id' => $draft->alternativeEmployeeId,
                'approved_by' => null,
                'approved_at' => null,
                'reject_reason' => null,
                'hr_approved' => false,
                'status' => LeaveRequestStatus::Pending->value,
            ] + $this->snapshot($quote, $window));
        });

        if ($key !== null) {
            Cache::put($key, $leaveRequest->id, now()->addDay());
        }

        $this->notifications->sendNewRequest($leaveRequest, $actor);

        return $leaveRequest;
    }

    private function consumed(string $key, int $employeeId): ?LeaveRequest
    {
        $id = Cache::get($key);

        return $id === null ? null : LeaveRequest::query()->whereKey($id)->ofEmployee($employeeId)->first();
    }

    /**
     * Shape validation; returns the employee's current contract (balances loaded).
     */
    private function validate(LeaveRequestDraft $draft, Employee $employee, ?LeaveRequest $editing): Contract
    {
        $contract = LeaveContext::contractOf((int) $employee->id);
        $alternatives = app(LeaveAlternativeEmployees::class)->query($employee)->pluck('id')->map(fn ($id) => (int) $id)->all();

        $eligibility = function (string $attribute, mixed $value, \Closure $fail) use ($employee, $editing, $contract): void {
            // An edit that keeps its stored balance is not asked again.
            if ($editing !== null && (int) $editing->leave_balance_id === (int) $value) {
                return;
            }

            $balance = $contract?->leaveBalances->firstWhere('id', (int) $value);
            $reason = $balance === null ? null : $this->eligibility->reasonAgainst($employee, $balance, $contract->leaveBalances);

            if ($reason !== null) {
                $fail($this->eligibility->message($reason));
            }
        };

        Validator::make(
            $draft->data() + ['is_exceptional' => $draft->isExceptional],
            $draft->rules($contract?->id, $alternatives, $eligibility),
            ['end_at.after_or_equal' => __('hr_leave.validation.end-before-start')],
            __('hr_leave.fields'),
        )->validate();

        if ($contract === null) {
            throw ValidationException::withMessages(['leave_balance_id' => __('hr_leave.validation.no-contract')]);
        }

        return $contract;
    }

    private function balanceOf(Contract $contract, LeaveRequestDraft $draft): LeaveBalance
    {
        $balance = $contract->leaveBalances->firstWhere('id', (int) $draft->leaveBalanceId);

        if (! $balance instanceof LeaveBalance) {
            throw ValidationException::withMessages(['leave_balance_id' => __('hr_leave.validation.no-balance')]);
        }

        return $balance;
    }

    private function requireWindow(LeaveRequestDraft $draft): LeaveWindow
    {
        $window = $draft->window();

        if ($window === null) {
            throw ValidationException::withMessages([
                ($draft->isHourly() ? 'end_hour' : 'end_at') => __(LeaveQuote::messageKey(LeaveQuote::INVALID_WINDOW)),
            ]);
        }

        return $window;
    }

    /**
     * Serialize writes for one requester: employee row, then balance rows.
     */
    private function lockRequesterRows(int $employeeId, int ...$balanceIds): void
    {
        Employee::query()->whereKey($employeeId)->lockForUpdate()->value('id');
        $balanceIds = array_values(array_unique($balanceIds));
        sort($balanceIds);

        foreach ($balanceIds as $balanceId) {
            LeaveBalance::query()->whereKey($balanceId)->lockForUpdate()->value('id');
        }
    }

    private function assertEligible(Employee $employee, LeaveBalance $balance): void
    {
        $reason = $this->eligibility->reasonAgainst($employee, $balance);

        if ($reason !== null) {
            throw ValidationException::withMessages(['leave_balance_id' => $this->eligibility->message($reason)]);
        }
    }

    /**
     * No two pending/accepted requests of one employee may overlap. Day
     * requests cover whole days; hour requests their exact times.
     */
    private function assertNoOwnOverlap(int $employeeId, LeaveRequestDraft $draft, ?int $ignoreId = null): void
    {
        $period = $draft->requestedPeriod();

        if ($period === null) {
            return;
        }

        [$from, $to] = $period;

        $conflict = LeaveRequest::query()
            ->ofEmployee($employeeId)
            ->whereIn('status', [LeaveRequestStatus::Pending->value, LeaveRequestStatus::Accepted->value])
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->whereDate('start_at', '<=', $to->toDateString())
            ->whereDate('end_at', '>=', $from->toDateString())
            ->orderBy('start_at')
            ->get(['id', 'type', 'start_at', 'end_at', 'status'])
            ->first(function (LeaveRequest $existing) use ($from, $to): bool {
                [$existingFrom, $existingTo] = LeaveRequestDraft::coveredPeriod($existing->type, $existing->start_at, $existing->end_at);

                return $existingFrom->lt($to) && $existingTo->gt($from);
            });

        if ($conflict === null) {
            return;
        }

        throw ValidationException::withMessages([
            ($draft->isHourly() ? 'leave_date' : 'start_at') => __('hr_leave.validation.overlap', [
                'status' => LeaveRequestStatus::of($conflict->status)->label(),
                'period' => LeaveRequestDraft::describePeriod($conflict),
            ]),
        ]);
    }

    /**
     * The quote's blocking errors as field errors. The annual shortfall is
     * re-derived so an accepted request being edited is checked against the
     * committed balance with its own previous deduction given back first.
     */
    private function assertQuoteAllows(LeaveQuote $quote, LeaveBalance $balance, LeaveWindow $window, float $releasedHours = 0.0, bool $committedOnly = false): void
    {
        $errors = [];

        foreach ($quote->errors as $code) {
            if ($code !== LeaveQuote::EXCEEDS_BALANCE) {
                $errors[$this->fieldForCode($code, $window)][] = __(LeaveQuote::messageKey($code));
            }
        }

        $projected = $quote->projectedHours + $releasedHours + ($committedOnly ? $quote->pendingHours : 0.0);

        if ($balance->isAnnual() && $quote->requestedHours > 0 && $projected < -self::EPSILON) {
            $errors['leave_balance_id'][] = __(LeaveQuote::messageKey(LeaveQuote::EXCEEDS_BALANCE));
        }

        if ($errors !== []) {
            throw ValidationException::withMessages(array_map(fn (array $messages) => array_values(array_unique($messages)), $errors));
        }
    }

    private function fieldForCode(string $code, LeaveWindow $window): string
    {
        $hourly = $window->isHourly();

        return match ($code) {
            LeaveQuote::EXCEEDS_BALANCE, LeaveQuote::SCHEDULE_UNKNOWN, LeaveQuote::NO_LEAVE_ENTITLEMENT => 'leave_balance_id',
            LeaveQuote::BELOW_MINIMUM, LeaveQuote::OUTSIDE_WORKING_HOURS => 'end_hour',
            LeaveQuote::INVALID_WINDOW => $hourly ? 'end_hour' : 'end_at',
            default => $hourly ? 'leave_date' : 'start_at',
        };
    }

    /**
     * @return array{computed_hours: float, rule_version: string, is_exceptional: bool}
     */
    private function snapshot(LeaveQuote $quote, LeaveWindow $window): array
    {
        return [
            'computed_hours' => $quote->requestedHours,
            'rule_version' => $quote->ruleVersion,
            'is_exceptional' => $window->exceptional,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function requestData(LeaveRequestDraft $draft): array
    {
        /** @var array{0: CarbonImmutable, 1: CarbonImmutable} $period */
        $period = $draft->storedPeriod();

        return [
            'leave_balance_id' => (int) $draft->leaveBalanceId,
            'type' => $draft->type,
            'start_at' => $period[0],
            'end_at' => $period[1],
            'reason' => $draft->reason,
            'alternative_employee_id' => $draft->alternativeEmployeeId,
        ];
    }

    /** Read the locked balance's committed figures without dropping its loaded contract. */
    private function reloadUsed(LeaveBalance $balance): void
    {
        $row = LeaveBalance::query()->whereKey($balance->id)->first(['deserved', 'used', 'amendment_offset']);

        if ($row !== null) {
            $balance->forceFill($row->only(['deserved', 'used', 'amendment_offset']))->syncOriginal();
        }
    }

    private function adjustUsed(LeaveBalance $balance, float $delta): void
    {
        $delta = round($delta, 2);

        if ($delta > 0) {
            $balance->increment('used', $delta);
        } elseif ($delta < 0) {
            $balance->decrement('used', -$delta);
        }
    }
}
