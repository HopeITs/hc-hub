<?php

namespace Modules\Hr\Livewire\Forms\Employee;

use Modules\Hr\Enums\BadgeSubtype;
use Modules\Hr\Enums\BadgeType;
use Modules\Hr\Models\Badge;
use Modules\Hr\Models\Employee;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Form;

class BadgeForm extends Form
{
    #[Locked]
    public ?Employee $employee = null;

    public array $badges = [];

    public function setEmployee(Employee $employee): void
    {
        $this->employee = $employee;

        // A salary-increase badge states the old and the new figure outright,
        // which makes it the same secret as contracts.salary - and the badges
        // tab is reachable by every role that can edit an employee. Withheld
        // on load, preserved on save; see salaryIsHidden().
        $salaryHidden = $this->salaryIsHidden();

        $this->badges = $this->employee
            ->badges()
            ->select(
                'id',
                'evaluation_id',
                'type',
                'subtype',
                'currency',
                'date',
                'reason',
                'amount',
                'new_position',
                'leave_unit',
                'current_salary',
                'new_salary',
                'notes',
            )
            ->get()
            ->map(
                fn (Badge $badge) => [
                    'id' => $badge->id,
                    'evaluation_id' => $badge->evaluation_id,
                    'type' => $badge->type,
                    'subtype' => $badge->subtype,
                    'currency' => $badge->currency,
                    'date' => $badge->date?->format('Y-m-d'),
                    'reason' => $badge->reason,
                    'amount' => $this->amountForForm($badge),
                    'new_position' => $badge->new_position,
                    'leave_unit' => $badge->leave_unit,
                    'current_salary' => $salaryHidden ? null : $badge->current_salary,
                    'new_salary' => $salaryHidden ? null : $badge->new_salary,
                    'notes' => $badge->notes,
                ],
            )
            ->toArray();
    }

    public function rules(): array
    {
        $employeeId = $this->employee?->id ?? 0;

        return [
            'badges' => ['array'],
            'badges.*.id' => [
                'nullable',
                'integer',
                Rule::exists('badges', 'id')->where('employee_id', $employeeId),
            ],
            'badges.*.evaluation_id' => [
                'nullable',
                'integer',
                Rule::exists('evaluations', 'id')->where(
                    fn ($query) => $query->whereIn(
                        'contract_id',
                        $this->employee?->allContracts()->select('id') ?? [],
                    ),
                ),
            ],
            'badges.*.type' => ['required', Rule::in(BadgeType::getValues())],
            'badges.*.subtype' => ['nullable', 'string', Rule::in(BadgeSubtype::getValues())],
            'badges.*.currency' => ['nullable', 'integer', 'in:0,1,2,3'],
            'badges.*.date' => ['required', 'date'],
            'badges.*.reason' => ['nullable', 'string', 'max:5000'],
            'badges.*.amount' => ['nullable', 'numeric', 'min:0'],
            'badges.*.new_position' => ['nullable', 'string', 'max:255'],
            'badges.*.leave_unit' => ['nullable', 'string', 'in:hour,day'],
            'badges.*.current_salary' => ['nullable', 'numeric', 'min:0'],
            'badges.*.new_salary' => ['nullable', 'numeric', 'min:0'],
            'badges.*.notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function addBadge(): void
    {
        $this->authorizeCreate();

        $this->badges[] = [
            'id' => null,
            'evaluation_id' => null,
            'type' => '',
            'subtype' => null,
            'currency' => 1,
            'date' => now()->format('Y-m-d'),
            'reason' => '',
            'amount' => null,
            'new_position' => null,
            'leave_unit' => null,
            'current_salary' => null,
            'new_salary' => null,
            'notes' => '',
        ];
    }

    public function removeBadge(int $index): void
    {
        $this->authorizeCreate();

        if (! array_key_exists($index, $this->badges)) {
            return;
        }

        $id = $this->badges[$index]['id'] ?? null;

        if ($id !== null) {
            $badge = $this->employee
                ->badges()
                ->find((int) $id);

            abort_unless($badge, 404);

            Gate::authorize('delete', $badge);
            $badge->delete();
        }

        unset($this->badges[$index]);
        $this->badges = array_values($this->badges);
    }

    public function resetSubtypeDetails(int $index): void
    {
        if (! array_key_exists($index, $this->badges)) {
            return;
        }

        $this->badges[$index] = array_merge($this->badges[$index], [
            'subtype' => null,
            'amount' => null,
            'new_position' => null,
            'leave_unit' => null,
        ]);
    }

    public function resetConditionalDetails(int $index): void
    {
        if (! array_key_exists($index, $this->badges)) {
            return;
        }

        $this->badges[$index] = array_merge($this->badges[$index], [
            'amount' => null,
            'new_position' => null,
            'leave_unit' => null,
        ]);
    }

    public function save(): void
    {
        $this->authorizeCreate();

        $validated = $this->validate();
        $this->validateBadgeDetails($validated['badges'] ?? []);

        $badges = collect($validated['badges'] ?? [])
            ->map(fn (array $badge) => $this->normalizeBadgeDetails($badge));

        DB::transaction(function () use ($badges): void {
            $badges->each(function (array $badge): void {
                $values = Arr::only($badge, $this->badgeDataFields());

                if ($this->salaryIsHidden()) {
                    // The boxes were blank because this role was not shown
                    // them. Writing those blanks back would erase the figures
                    // on every salary badge the employee has - so the keys are
                    // dropped and the stored values are left untouched.
                    unset($values['current_salary'], $values['new_salary']);
                }

                if (empty($badge['id'])) {
                    $this->employee->badges()->create($values);

                    return;
                }

                $existingBadge = $this->employee->badges()->find((int) $badge['id']);

                abort_unless($existingBadge, 404);

                $existingBadge->update($values);
            });
        });

        $this->setEmployee($this->employee->fresh());
    }

    /** @see \App\Models\User::canViewSalary() */
    public function salaryIsHidden(): bool
    {
        $user = auth()->user();

        return $user !== null && ! $user->canViewSalary();
    }

    private function authorizeCreate(): void
    {
        abort_unless($this->employee, 404);
        Gate::authorize('create', [Badge::class, $this->employee]);
    }

    private function amountForForm(Badge $badge): int|float|string|null
    {
        if ($badge->amount === null) {
            return null;
        }

        if (BadgeSubtype::isLeave($badge->subtype)) {
            return (int) $badge->amount;
        }

        return $badge->amount;
    }

    private function validateBadgeDetails(array $badges): void
    {
        $errors = [];

        foreach ($badges as $index => $badge) {
            $type = (int) ($badge['type'] ?? BadgeType::Unknown);
            $subtype = $badge['subtype'] ?? null;

            if (! in_array($type, [BadgeType::Warning, BadgeType::Reward], true)) {
                continue;
            }

            if (blank($subtype)) {
                $errors["badgeForm.badges.$index.subtype"] = __('validation.required', [
                    'attribute' => BadgeSubtype::selectorLabelForType($type),
                ]);

                continue;
            }

            if (! array_key_exists($subtype, BadgeSubtype::optionsForType($type))) {
                $errors["badgeForm.badges.$index.subtype"] = __('validation.in', [
                    'attribute' => BadgeSubtype::selectorLabelForType($type),
                ]);

                continue;
            }

            if (BadgeSubtype::isFinancial($subtype) && blank($badge['amount'] ?? null)) {
                $errors["badgeForm.badges.$index.amount"] = __('validation.required', [
                    'attribute' => __('general.financial-amount'),
                ]);
            }

            if (BadgeSubtype::isFinancial($subtype) && ! blank($badge['amount'] ?? null) && (float) $badge['amount'] <= 0) {
                $errors["badgeForm.badges.$index.amount"] = __('validation.min.numeric', [
                    'attribute' => __('general.financial-amount'),
                    'min' => 1,
                ]);
            }

            if (BadgeSubtype::isFinancial($subtype) && ! in_array((int) ($badge['currency'] ?? 0), [1, 2, 3], true)) {
                $errors["badgeForm.badges.$index.currency"] = __('validation.required', [
                    'attribute' => __('general.currency'),
                ]);
            }

            if (BadgeSubtype::isPromotion($subtype) && blank($badge['new_position'] ?? null)) {
                $errors["badgeForm.badges.$index.new_position"] = __('validation.required', [
                    'attribute' => __('general.new-position'),
                ]);
            }

            if (BadgeSubtype::isLeave($subtype)) {
                $leaveUnit = $badge['leave_unit'] ?? null;

                if (! in_array($leaveUnit, ['hour', 'day'], true)) {
                    $errors["badgeForm.badges.$index.leave_unit"] = __('validation.required', [
                        'attribute' => __('general.leave-calculation'),
                    ]);

                    continue;
                }

                if (blank($badge['amount'] ?? null)) {
                    $errors["badgeForm.badges.$index.amount"] = __('validation.required', [
                        'attribute' => __('general.leave-quantity'),
                    ]);
                }

                if (! blank($badge['amount'] ?? null) && (float) $badge['amount'] <= 0) {
                    $errors["badgeForm.badges.$index.amount"] = __('validation.min.numeric', [
                        'attribute' => $leaveUnit === 'hour'
                            ? __('general.number-of-hours')
                            : __('general.number-of-days'),
                        'min' => 1,
                    ]);
                }

                if (! blank($badge['amount'] ?? null) && floor((float) $badge['amount']) !== (float) $badge['amount']) {
                    $errors["badgeForm.badges.$index.amount"] = __('validation.integer', [
                        'attribute' => $leaveUnit === 'hour'
                            ? __('general.number-of-hours')
                            : __('general.number-of-days'),
                    ]);
                }
            }

            // A leave deduction with no stated reason shows up on the
            // employee's dashboard as a penalty with an empty explanation —
            // require it here rather than leaving it to be noticed later.
            if ($subtype === BadgeSubtype::WarningLeaveDeduction && blank($badge['reason'] ?? null)) {
                $errors["badgeForm.badges.$index.reason"] = __('validation.required', [
                    'attribute' => __('general.reason'),
                ]);
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    private function normalizeBadgeDetails(array $badge): array
    {
        $type = (int) ($badge['type'] ?? BadgeType::Unknown);
        $subtype = $badge['subtype'] ?? null;

        if (! in_array($type, [BadgeType::Warning, BadgeType::Reward], true)) {
            return array_merge($badge, [
                'subtype' => null,
                'amount' => null,
                'new_position' => null,
                'leave_unit' => null,
            ]);
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
            return array_merge($badge, [
                'leave_unit' => null,
            ]);
        }

        return $badge;
    }

    private function badgeDataFields(): array
    {
        return [
            'evaluation_id',
            'type',
            'subtype',
            'currency',
            'date',
            'reason',
            'amount',
            'new_position',
            'leave_unit',
            'current_salary',
            'new_salary',
            'notes',
        ];
    }
}
