<?php

declare(strict_types=1);

namespace Modules\Hr\Livewire\Concerns\Employees;

use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Modules\Hr\Actions\Employees\SaveBadge;
use Modules\Hr\Enums\BadgeSubtype;
use Modules\Hr\Enums\BadgeType;
use Modules\Hr\Models\Badge;
use Modules\Hr\Support\Employees\HrAccess;

/**
 * The badge dialog on an employee's profile: record a warning, a reward or a
 * salary increase, change one, or remove it.
 *
 * Expects the page to use ReadsEmployeeProfile (employee, badges).
 */
trait ManagesBadgeForm
{
    public bool $showBadgeModal = false;

    #[Locked]
    public ?int $badgeId = null;

    public string $badgeType = '';

    public string $badgeSubtype = '';

    public string $badgeDate = '';

    public string $badgeReason = '';

    public string $badgeAmount = '';

    public string $badgeCurrency = '1';

    public string $badgeNewPosition = '';

    public string $badgeLeaveUnit = '';

    public string $badgeCurrentSalary = '';

    public string $badgeNewSalary = '';

    public string $badgeNotes = '';

    public bool $showBadgeDeleteModal = false;

    #[Locked]
    public ?int $deletingBadgeId = null;

    public function openBadge(?int $badgeId = null): void
    {
        Gate::authorize('create', [Badge::class, $this->employee]);

        $this->resetValidation();
        $this->reset(['badgeType', 'badgeSubtype', 'badgeReason', 'badgeAmount', 'badgeNewPosition', 'badgeLeaveUnit', 'badgeCurrentSalary', 'badgeNewSalary', 'badgeNotes']);
        $this->badgeCurrency = '1';
        $this->badgeDate = now()->toDateString();
        $this->badgeId = $badgeId;

        if ($badgeId !== null) {
            $badge = $this->employee->badges()->findOrFail($badgeId);
            $showsPay = HrAccess::canViewSalary(Auth::user());

            $this->badgeType = (string) $badge->type;
            $this->badgeSubtype = (string) $badge->subtype;
            $this->badgeDate = $badge->date?->format('Y-m-d') ?? '';
            $this->badgeReason = (string) $badge->reason;
            $this->badgeAmount = $badge->amount === null ? '' : (string) (BadgeSubtype::isLeave($badge->subtype) ? (int) $badge->amount : $badge->amount);
            $this->badgeCurrency = (string) ($badge->currency ?? '1');
            $this->badgeNewPosition = (string) $badge->new_position;
            $this->badgeLeaveUnit = (string) $badge->leave_unit;
            $this->badgeCurrentSalary = $showsPay ? (string) $badge->current_salary : '';
            $this->badgeNewSalary = $showsPay ? (string) $badge->new_salary : '';
            $this->badgeNotes = (string) $badge->notes;
        }

        $this->showBadgeModal = true;
    }

    public function closeBadge(): void
    {
        $this->showBadgeModal = false;
        $this->badgeId = null;
        $this->resetValidation();
    }

    /** A new type starts with no kind and none of the kind's details. */
    public function updatedBadgeType(): void
    {
        $this->reset(['badgeSubtype', 'badgeAmount', 'badgeNewPosition', 'badgeLeaveUnit']);
    }

    public function updatedBadgeSubtype(): void
    {
        $this->reset(['badgeAmount', 'badgeNewPosition', 'badgeLeaveUnit']);
    }

    public function saveBadge(): void
    {
        $validated = $this->validate([
            'badgeType' => ['required', Rule::in([BadgeType::Warning->value, BadgeType::Reward->value, BadgeType::Salary_Increase->value])],
            'badgeSubtype' => ['nullable', 'string', Rule::enum(BadgeSubtype::class)],
            'badgeCurrency' => ['nullable', 'integer', 'in:0,1,2,3'],
            'badgeDate' => ['required', 'date'],
            'badgeReason' => ['nullable', 'string', 'max:5000'],
            'badgeAmount' => ['nullable', 'numeric', 'min:0'],
            'badgeNewPosition' => ['nullable', 'string', 'max:255'],
            'badgeLeaveUnit' => ['nullable', 'string', 'in:hour,day'],
            'badgeCurrentSalary' => ['nullable', 'numeric', 'min:0'],
            'badgeNewSalary' => ['nullable', 'numeric', 'min:0'],
            'badgeNotes' => ['nullable', 'string', 'max:5000'],
        ], [], [
            'badgeType' => __('common.type'),
            'badgeSubtype' => __('hr_employees.badges.kind'),
            'badgeDate' => __('hr_employees.badges.date'),
            'badgeReason' => __('hr_employees.badges.reason'),
            'badgeAmount' => __('hr_employees.badges.amount'),
            'badgeNewPosition' => __('hr_employees.badges.new_position'),
            'badgeLeaveUnit' => __('hr_employees.badges.leave_unit'),
            'badgeCurrentSalary' => __('hr_employees.badges.current_salary'),
            'badgeNewSalary' => __('hr_employees.badges.new_salary'),
            'badgeNotes' => __('common.notes'),
        ]);

        $blank = fn (string $value): ?string => $value === '' ? null : $value;

        app(SaveBadge::class)->handle($this->employee, $this->badgeId, [
            'type' => (int) $validated['badgeType'],
            'subtype' => $blank((string) $validated['badgeSubtype']),
            'currency' => $blank((string) $validated['badgeCurrency']),
            'date' => $validated['badgeDate'],
            'reason' => $blank((string) $validated['badgeReason']),
            'amount' => $blank((string) $validated['badgeAmount']),
            'new_position' => $blank((string) $validated['badgeNewPosition']),
            'leave_unit' => $blank((string) $validated['badgeLeaveUnit']),
            'current_salary' => $blank((string) $validated['badgeCurrentSalary']),
            'new_salary' => $blank((string) $validated['badgeNewSalary']),
            'notes' => $blank((string) $validated['badgeNotes']),
        ]);

        $this->closeBadge();
        unset($this->badges);

        Flux::toast(variant: 'success', text: __('hr_employees.toasts.badge_saved'));
    }

    public function confirmBadgeDelete(int $badgeId): void
    {
        Gate::authorize('delete', $this->employee->badges()->findOrFail($badgeId));

        $this->deletingBadgeId = $badgeId;
        $this->showBadgeDeleteModal = true;
    }

    public function cancelBadgeDelete(): void
    {
        $this->showBadgeDeleteModal = false;
        $this->deletingBadgeId = null;
    }

    public function deleteBadge(): void
    {
        if ($this->deletingBadgeId !== null) {
            app(SaveBadge::class)->delete($this->employee->badges()->findOrFail($this->deletingBadgeId));

            Flux::toast(variant: 'success', text: __('hr_employees.toasts.badge_deleted'));
        }

        $this->cancelBadgeDelete();
        unset($this->badges);
    }
}
