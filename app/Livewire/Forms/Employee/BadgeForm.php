<?php

namespace App\Livewire\Forms\Employee;

use App\Models\Badge;
use App\Models\Employee;
use Livewire\Form;

class BadgeForm extends Form
{
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
                fn(Badge $badge) => [
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
        return [
            'badges' => ['array'],
            'badges.*.id' => ['nullable', 'exists:badges,id'],
            'badges.*.evaluation_id' => ['nullable', 'exists:evaluations,id'],
            'badges.*.type' => ['required', 'string', 'max:255'],
            'badges.*.currency' => ['nullable', 'integer', 'in:0,1,2,3'],
            'badges.*.date' => ['required', 'date'],
            'badges.*.reason' => ['nullable', 'string'],
            'badges.*.current_salary' => ['nullable', 'numeric', 'min:0'],
            'badges.*.new_salary' => ['nullable', 'numeric', 'min:0'],
            'badges.*.notes' => ['nullable', 'string'],
        ];
    }

    public function addBadge(): void
    {
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

    public function removeBadge(int $index, ?int $id = null): void
    {
        if (!array_key_exists($index, $this->badges)) {
            return;
        }

        unset($this->badges[$index]);

        if (isset($id)) {
            Badge::find($id)?->delete();
        }

        $this->badges = array_values($this->badges);
    }

    public function save(): void
    {
        $this->validate();

        collect($this->badges)->each(function ($badgeData) {
            $this->employee->badges()->updateOrCreate(['id' => $badgeData['id'] ?? null], $badgeData);
        });
    }
}
