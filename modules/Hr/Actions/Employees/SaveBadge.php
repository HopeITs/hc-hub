<?php

declare(strict_types=1);

namespace Modules\Hr\Actions\Employees;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Modules\Hr\Enums\BadgeSubtype;
use Modules\Hr\Enums\BadgeType;
use Modules\Hr\Models\Badge;
use Modules\Hr\Models\Employee;
use Modules\Hr\Support\Employees\HrAccess;

/**
 * Record or change a badge on an employee: a warning, a reward or a salary
 * increase. A warning or reward names its kind (subtype), and the kind
 * decides which details are required: an amount and currency for a fine or
 * bonus, the new position for a promotion, a unit and quantity for leave.
 */
class SaveBadge
{
    /**
     * @param  array<string, mixed>  $data  validated badge columns
     */
    public function handle(Employee $employee, ?int $badgeId, array $data): Badge
    {
        Gate::authorize('create', [Badge::class, $employee]);

        $this->validateDetails($data);
        $values = $this->normalise($data);

        if (! HrAccess::canViewSalary(Auth::user())) {
            unset($values['current_salary'], $values['new_salary']);
        }

        if ($badgeId === null) {
            return $employee->badges()->create($values);
        }

        $badge = $employee->badges()->findOrFail($badgeId);
        $badge->update($values);

        return $badge;
    }

    public function delete(Badge $badge): void
    {
        Gate::authorize('delete', $badge);

        $badge->delete();
    }

    /**
     * @param  array<string, mixed>  $badge
     */
    private function validateDetails(array $badge): void
    {
        $type = (int) ($badge['type'] ?? BadgeType::Unknown->value);
        $subtype = $badge['subtype'] ?? null;
        $errors = [];

        if (! in_array($type, [BadgeType::Warning->value, BadgeType::Reward->value], true)) {
            return;
        }

        $required = fn (string $attribute): string => __('validation.required', ['attribute' => $attribute]);

        if (blank($subtype) || ! array_key_exists($subtype, BadgeSubtype::optionsForType($type))) {
            throw ValidationException::withMessages(['badgeSubtype' => $required(__('hr_employees.badges.kind'))]);
        }

        $amount = $badge['amount'] ?? null;

        if (BadgeSubtype::isFinancial($subtype)) {
            if (blank($amount) || (float) $amount <= 0) {
                $errors['badgeAmount'] = $required(__('hr_employees.badges.amount'));
            }

            if (! in_array((int) ($badge['currency'] ?? 0), [1, 2, 3], true)) {
                $errors['badgeCurrency'] = $required(__('common.currency'));
            }
        }

        if (BadgeSubtype::isPromotion($subtype) && blank($badge['new_position'] ?? null)) {
            $errors['badgeNewPosition'] = $required(__('hr_employees.badges.new_position'));
        }

        if (BadgeSubtype::isLeave($subtype)) {
            if (! in_array($badge['leave_unit'] ?? null, ['hour', 'day'], true)) {
                $errors['badgeLeaveUnit'] = $required(__('hr_employees.badges.leave_unit'));
            }

            if (blank($amount) || (float) $amount <= 0 || floor((float) $amount) !== (float) $amount) {
                $errors['badgeAmount'] = __('validation.integer', ['attribute' => __('hr_employees.badges.leave_quantity')]);
            }
        }

        if ($subtype === BadgeSubtype::WarningLeaveDeduction->value && blank($badge['reason'] ?? null)) {
            $errors['badgeReason'] = $required(__('hr_employees.badges.reason'));
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    /**
     * Keep only the details the chosen type and kind use.
     *
     * @param  array<string, mixed>  $badge
     * @return array<string, mixed>
     */
    private function normalise(array $badge): array
    {
        $type = (int) ($badge['type'] ?? BadgeType::Unknown->value);
        $subtype = $badge['subtype'] ?? null;

        if (! in_array($type, [BadgeType::Warning->value, BadgeType::Reward->value], true)) {
            return array_merge($badge, ['subtype' => null, 'amount' => null, 'new_position' => null, 'leave_unit' => null]);
        }

        $badge['current_salary'] = null;
        $badge['new_salary'] = null;

        if (! BadgeSubtype::isFinancial($subtype) && ! BadgeSubtype::isLeave($subtype)) {
            $badge['amount'] = null;
        }

        if (! BadgeSubtype::isFinancial($subtype)) {
            $badge['currency'] = null;
        }

        if (! BadgeSubtype::isPromotion($subtype)) {
            $badge['new_position'] = null;
        }

        if (! BadgeSubtype::isLeave($subtype)) {
            $badge['leave_unit'] = null;
        }

        return $badge;
    }
}
