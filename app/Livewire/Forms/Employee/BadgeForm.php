<?php

namespace App\Livewire\Forms\Employee;

use App\Enums\BadgeType;
use App\Models\Badge;
use App\Models\Employee;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
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
        $this->badges = $this->employee
            ->badges()
            ->select('id', 'evaluation_id', 'type', 'currency', 'date', 'reason', 'current_salary', 'new_salary', 'notes')
            ->get()
            ->map(
                fn (Badge $badge) => [
                    'id' => $badge->id,
                    'evaluation_id' => $badge->evaluation_id,
                    'type' => $badge->type,
                    'currency' => $badge->currency,
                    'date' => $badge->date?->format('Y-m-d'),
                    'reason' => $badge->reason,
                    'current_salary' => $badge->current_salary,
                    'new_salary' => $badge->new_salary,
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
            'badges.*.currency' => ['nullable', 'integer', 'in:0,1,2,3'],
            'badges.*.date' => ['required', 'date'],
            'badges.*.reason' => ['nullable', 'string', 'max:5000'],
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
            'currency' => 1,
            'date' => now()->format('Y-m-d'),
            'reason' => '',
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

    public function save(): void
    {
        $this->authorizeCreate();

        $validated = $this->validate();
        $newBadges = collect($validated['badges'])
            ->filter(fn (array $badge) => empty($badge['id']));

        if ($newBadges->isEmpty()) {
            return;
        }

        DB::transaction(function () use ($newBadges): void {
            $newBadges->each(function (array $badge): void {
                $this->employee->badges()->create(Arr::only($badge, [
                    'evaluation_id',
                    'type',
                    'currency',
                    'date',
                    'reason',
                    'current_salary',
                    'new_salary',
                    'notes',
                ]));
            });
        });

        $this->setEmployee($this->employee->fresh());
    }

    private function authorizeCreate(): void
    {
        abort_unless($this->employee, 404);
        Gate::authorize('create', [Badge::class, $this->employee]);
    }
}
