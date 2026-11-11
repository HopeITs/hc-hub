<?php

declare(strict_types=1);

namespace Modules\Hr\Support\Leave;

use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;
use Modules\Hr\Models\Employee;
use Modules\Hr\Models\LeaveBalance;
use Modules\Hr\Models\LeaveRequest;
use Throwable;

/**
 * What a leave request form holds, read the way the old LeaveRequestForm read
 * it: a day request (start_at..end_at) or an hourly one (leave_date,
 * start_hour..end_hour), a balance, a reason, an optional alternative and the
 * December "exceptional" flag. Pure: it never writes and never throws while
 * reading a partial form.
 */
final class LeaveRequestDraft
{
    /** Longest single request, in days (a guard for the day-by-day pricing loop). */
    public const MAX_SPAN_DAYS = 366;

    public function __construct(
        public readonly string $type = LeaveWindow::DAY,
        public readonly mixed $leaveBalanceId = null,
        public readonly mixed $leaveDate = null,
        public readonly mixed $startHour = null,
        public readonly mixed $endHour = null,
        public readonly mixed $startAt = null,
        public readonly mixed $endAt = null,
        public readonly string $reason = '',
        public readonly mixed $alternativeEmployeeId = null,
        public readonly bool $isExceptional = false,
    ) {}

    /**
     * @param  array<string, mixed>  $input  the form's snake_case fields
     */
    public static function fromArray(array $input): self
    {
        return new self(
            type: in_array($input['type'] ?? null, [LeaveWindow::DAY, LeaveWindow::HOUR], true) ? $input['type'] : LeaveWindow::DAY,
            leaveBalanceId: $input['leave_balance_id'] ?? null,
            leaveDate: $input['leave_date'] ?? null,
            startHour: $input['start_hour'] ?? null,
            endHour: $input['end_hour'] ?? null,
            startAt: $input['start_at'] ?? null,
            endAt: $input['end_at'] ?? null,
            reason: trim((string) ($input['reason'] ?? '')),
            alternativeEmployeeId: blank($input['alternative_employee'] ?? null) ? null : (int) $input['alternative_employee'],
            isExceptional: filter_var($input['is_exceptional'] ?? false, FILTER_VALIDATE_BOOLEAN),
        );
    }

    /**
     * The form fields of a stored request (for an edit).
     *
     * @return array<string, mixed>
     */
    public static function fieldsOf(LeaveRequest $leaveRequest): array
    {
        $hourly = $leaveRequest->type === LeaveWindow::HOUR;

        return [
            'type' => $hourly ? LeaveWindow::HOUR : LeaveWindow::DAY,
            'leave_balance_id' => (string) $leaveRequest->leave_balance_id,
            'leave_date' => $hourly ? $leaveRequest->start_at?->format('Y-m-d') : null,
            'start_hour' => $hourly ? $leaveRequest->start_at?->format('H:i') : null,
            'end_hour' => $hourly ? $leaveRequest->end_at?->format('H:i') : null,
            'start_at' => $hourly ? '' : (string) $leaveRequest->start_at?->format('Y-m-d'),
            'end_at' => $hourly ? '' : (string) $leaveRequest->end_at?->format('Y-m-d'),
            'reason' => (string) $leaveRequest->reason,
            'alternative_employee' => $leaveRequest->alternative_employee_id === null ? '' : (string) $leaveRequest->alternative_employee_id,
            'is_exceptional' => (bool) $leaveRequest->is_exceptional,
        ];
    }

    public function isHourly(): bool
    {
        return $this->type === LeaveWindow::HOUR;
    }

    /**
     * The validation rules of the old form (shape only: hours, the minimum,
     * countable days, the type maximum, December and the balance are the
     * pricing service's errors).
     *
     * @param  list<int>  $alternativeIds
     * @return array<string, mixed>
     */
    public function rules(?int $contractId, array $alternativeIds, ?\Closure $eligibility = null): array
    {
        $rules = [
            'type' => ['required', Rule::in([LeaveWindow::DAY, LeaveWindow::HOUR])],
            'reason' => ['required', 'string', 'min:3', 'max:2000'],
            'leave_balance_id' => array_values(array_filter([
                'bail',
                'required',
                Rule::exists('leave_balances', 'id')->where('contract_id', $contractId ?? 0),
                $eligibility,
            ])),
            'alternative_employee' => ['nullable', Rule::in($alternativeIds)],
        ];

        if ($this->isHourly()) {
            $rules['leave_date'] = ['required', 'date'];
            $rules['start_hour'] = ['required', 'date_format:H:i'];
            $rules['end_hour'] = [
                'required',
                'date_format:H:i',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (self::isTime($this->startHour) && self::isTime($value)
                        && LeaveWindow::minuteOfDay((string) $value) <= LeaveWindow::minuteOfDay((string) $this->startHour)) {
                        $fail(__('hr_leave.validation.end-hour-after-start-hour'));
                    }
                },
            ];
        } else {
            $rules['start_at'] = ['required', 'date', 'after_or_equal:'.today()->format('Y-m-d')];
            $rules['end_at'] = [
                'required',
                'date',
                'after_or_equal:start_at',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if ($this->spanTooLong()) {
                        $fail(__('hr_leave.validation.range-too-long', ['days' => self::MAX_SPAN_DAYS]));
                    }
                },
            ];
        }

        return $rules;
    }

    /**
     * @return array<string, mixed>
     */
    public function data(): array
    {
        return [
            'type' => $this->type,
            'reason' => $this->reason,
            'leave_balance_id' => $this->leaveBalanceId,
            'alternative_employee' => $this->alternativeEmployeeId,
            'leave_date' => $this->leaveDate,
            'start_hour' => $this->startHour,
            'end_hour' => $this->endHour,
            'start_at' => $this->startAt,
            'end_at' => $this->endAt,
        ];
    }

    /**
     * The typed window as the pricing service reads it, or null while the
     * form is partial or unparseable. The December flag is carried only when
     * the period touches December.
     */
    public function window(): ?LeaveWindow
    {
        try {
            if ($this->isHourly()) {
                if (! self::isDate($this->leaveDate) || ! self::isTime($this->startHour) || ! self::isTime($this->endHour)) {
                    return null;
                }

                $build = fn (bool $exceptional) => LeaveWindow::hours((string) $this->leaveDate, (string) $this->startHour, (string) $this->endHour, $exceptional);
            } else {
                if (! self::isDate($this->startAt) || ! self::isDate($this->endAt)) {
                    return null;
                }

                $build = fn (bool $exceptional) => LeaveWindow::days($this->startAt, $this->endAt, $exceptional);
            }

            $window = $build(false);
        } catch (Throwable) {
            return null;
        }

        return $this->isExceptional && self::touchesDecember($window) ? $build(true) : $window;
    }

    public function touchesDecemberNow(): bool
    {
        $window = $this->window();

        return $window !== null && self::touchesDecember($window);
    }

    public static function touchesDecember(LeaveWindow $window): bool
    {
        for ($month = $window->startDate->startOfMonth(); $month->lte($window->endDate); $month = $month->addMonth()) {
            if ($month->month === 12) {
                return true;
            }
        }

        return false;
    }

    public function spanTooLong(): bool
    {
        if ($this->isHourly() || ! self::isDate($this->startAt) || ! self::isDate($this->endAt)) {
            return false;
        }

        try {
            return CarbonImmutable::parse($this->startAt)->startOfDay()
                ->diffInDays(CarbonImmutable::parse($this->endAt)->startOfDay(), false) > self::MAX_SPAN_DAYS;
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * The stored start and end of this request.
     *
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    public function storedPeriod(): array
    {
        if ($this->isHourly()) {
            return [
                CarbonImmutable::parse($this->leaveDate.' '.$this->startHour),
                CarbonImmutable::parse($this->leaveDate.' '.$this->endHour),
            ];
        }

        return [
            CarbonImmutable::parse($this->startAt)->startOfDay(),
            CarbonImmutable::parse($this->endAt)->startOfDay(),
        ];
    }

    /**
     * The period the form asks for, as covered time. Null when incomplete.
     *
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}|null
     */
    public function requestedPeriod(): ?array
    {
        try {
            if ($this->window() === null) {
                return null;
            }

            [$start, $end] = $this->storedPeriod();
        } catch (Throwable) {
            return null;
        }

        return $end->lt($start) ? null : self::coveredPeriod($this->type, $start, $end);
    }

    /**
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    public static function coveredPeriod(?string $type, mixed $start, mixed $end): array
    {
        $start = CarbonImmutable::parse($start);
        $end = CarbonImmutable::parse($end);

        return $type === LeaveWindow::HOUR ? [$start, $end] : [$start->startOfDay(), $end->endOfDay()];
    }

    /**
     * "25-10-2026 → 26-10-2026" or "25-10-2026, 09:00 → 11:00", each value
     * isolated so it reads correctly inside an Arabic sentence.
     */
    public static function describePeriod(LeaveRequest $leaveRequest): string
    {
        $isolate = fn (string $value): string => "\u{2068}{$value}\u{2069}";
        $separator = ' '.__('hr_leave.range-separator').' ';

        if ($leaveRequest->type === LeaveWindow::HOUR) {
            return $isolate((string) $leaveRequest->start_at?->format('d-m-Y, H:i')).$separator.$isolate((string) $leaveRequest->end_at?->format('H:i'));
        }

        $start = (string) $leaveRequest->start_at?->format('d-m-Y');
        $end = (string) $leaveRequest->end_at?->format('d-m-Y');

        return $start === $end ? $isolate($start) : $isolate($start).$separator.$isolate($end);
    }

    /** Would saving re-price $original? */
    public function differsFrom(LeaveRequest $original, LeaveBalance $balance, LeaveWindow $window): bool
    {
        return (int) $original->leave_balance_id !== (int) $balance->id
            || $original->type !== $window->type
            || $original->start_at?->format('Y-m-d H:i') !== $window->startsAt()->format('Y-m-d H:i')
            || $original->end_at?->format('Y-m-d H:i') !== $window->endsAt()->format('Y-m-d H:i')
            || (bool) $original->is_exceptional !== $window->exceptional;
    }

    public static function isDate(mixed $value): bool
    {
        if ($value instanceof DateTimeInterface) {
            return true;
        }

        if (! is_string($value) || trim($value) === '') {
            return false;
        }

        try {
            CarbonImmutable::parse($value);

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    public static function isTime(mixed $value): bool
    {
        return is_string($value) && preg_match('/^([01]?\d|2[0-3]):[0-5]\d(:[0-5]\d)?$/', trim($value)) === 1;
    }

    /**
     * The employee the request is about: the edited request's owner, else
     * the employee HR picked (inside its governorates), else the actor.
     */
    public static function employeeQuery(): Builder
    {
        return Employee::query()->select(['id', 'first_name_en', 'first_name_ar', 'last_name_en', 'last_name_ar', 'gender', 'marital_status', 'city_id', 'is_active']);
    }
}
