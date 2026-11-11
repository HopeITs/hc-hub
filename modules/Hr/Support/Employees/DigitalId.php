<?php

declare(strict_types=1);

namespace Modules\Hr\Support\Employees;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Core\Models\City;
use Modules\Hr\Models\Contract;
use Modules\Hr\Models\Employee;

/**
 * The employee ID card and its public link:
 * /ID/[city code, 3 digits][program code, 3 digits][employee id]-[card index].
 *
 * The index starts at 0 (the cardboard card) and moves forward each time a
 * card is reissued, which is what makes a lost card's link stop working.
 */
final class DigitalId
{
    public const CARDBOARD_INDEX = 0;

    public const MAX_INDEX = 65535;

    private const PATTERN = '/^(\d{3})(\d{3})([1-9]\d{0,17})-(0|[1-9]\d{0,4})\z/';

    /**
     * The active employee a card link belongs to, or null for every link that
     * is malformed, old, disabled or not the current card.
     */
    public static function resolve(string $card): ?Employee
    {
        if (! preg_match(self::PATTERN, $card, $parts) || (int) $parts[4] > self::MAX_INDEX) {
            return null;
        }

        $employee = Employee::query()
            ->whereKey((int) $parts[3])
            ->where('is_active', true)
            ->where('digital_id_enabled', true)
            ->first();

        if ($employee === null) {
            return null;
        }

        $expected = self::cardCode($employee);

        return $expected !== null && hash_equals($expected, $card) ? $employee : null;
    }

    public static function cardCode(Employee $employee): ?string
    {
        $cityCode = self::normaliseCode(self::city($employee)?->code);
        $programCode = self::normaliseCode(self::cardContract($employee)?->position?->program?->code);

        if ($cityCode === null || $programCode === null || ! $employee->getKey()) {
            return null;
        }

        return $cityCode.$programCode.$employee->getKey().'-'.self::currentIndex($employee);
    }

    public static function url(Employee $employee): ?string
    {
        $code = self::cardCode($employee);

        return $code === null ? null : route('hr.employees.card.show', ['card' => $code]);
    }

    /**
     * Why the employee has no card link yet, as a lang key; null when they do.
     */
    public static function missingLinkReason(Employee $employee): ?string
    {
        $city = self::city($employee);

        if ($city === null) {
            return 'hr_employees.card.missing.no_city';
        }

        if (self::normaliseCode($city->code) === null) {
            return 'hr_employees.card.missing.city_code';
        }

        $program = self::cardContract($employee)?->position?->program;

        if ($program === null) {
            return 'hr_employees.card.missing.no_position';
        }

        if (self::normaliseCode($program->code) === null) {
            return 'hr_employees.card.missing.program_code';
        }

        return null;
    }

    public static function currentIndex(Employee $employee): int
    {
        return (int) ($employee->digital_id_card_index ?? self::CARDBOARD_INDEX);
    }

    /**
     * The contract the card shows: the running one with the latest start.
     */
    public static function cardContract(Employee $employee): ?Contract
    {
        return once(fn (): ?Contract => Contract::query()
            ->select(['id', 'employee_id', 'position_id', 'start_date', 'end_date', 'is_active'])
            ->where('employee_id', $employee->getKey())
            ->activeNow()
            ->with(['position:id,program_id,center_id,city_id,department_id,title_en,title_ar', 'position.program:id,name,code', 'position.center:id,name_en,name_ar,city_id'])
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->first());
    }

    /**
     * Issue the next card. $expectedIndex is the index the caller was looking
     * at, so two people reissuing at once cannot skip a number.
     */
    public static function reissue(Employee $employee, int $expectedIndex): int
    {
        return DB::transaction(function () use ($employee, $expectedIndex): int {
            $locked = Employee::query()->whereKey($employee->getKey())->lockForUpdate()->firstOrFail();
            $current = self::currentIndex($locked);

            $problem = match (true) {
                $current !== $expectedIndex => 'stale_index',
                ! $locked->is_active => 'inactive_employee',
                self::cardCode($locked) === null => 'no_card_link',
                $current >= self::MAX_INDEX => 'limit_reached',
                default => null,
            };

            if ($problem !== null) {
                throw ValidationException::withMessages(['card' => __('hr_employees.card.errors.'.$problem)]);
            }

            $locked->forceFill([
                'digital_id_card_index' => $current + 1,
                'digital_id_enabled' => true,
                'digital_id_rotated_at' => now(),
                'digital_id_revoked_at' => null,
            ])->save();

            return $current + 1;
        });
    }

    public static function disable(Employee $employee): void
    {
        if ($employee->digital_id_enabled === false) {
            return;
        }

        $employee->forceFill(['digital_id_enabled' => false, 'digital_id_revoked_at' => now()])->save();
    }

    public static function enable(Employee $employee): void
    {
        if (! $employee->is_active) {
            throw ValidationException::withMessages(['card' => __('hr_employees.card.errors.inactive_employee')]);
        }

        if ($employee->digital_id_enabled === true) {
            return;
        }

        $employee->forceFill(['digital_id_enabled' => true, 'digital_id_revoked_at' => null])->save();
    }

    public static function normaliseCode(mixed $code): ?string
    {
        $code = trim((string) $code);

        return preg_match('/^\d{1,3}\z/', $code) === 1 ? str_pad($code, 3, '0', STR_PAD_LEFT) : null;
    }

    private static function city(Employee $employee): ?City
    {
        return $employee->city_id === null
            ? null
            : City::query()->select(['id', 'name_en', 'name_ar', 'code', 'slug', 'hr_hotline'])->find($employee->city_id);
    }
}
