<?php

namespace App\Livewire\Forms\Employee;

use App\Models\Employee;
use App\Models\Relative;
use Livewire\Form;

class RelativeForm extends Form
{
    public ?Employee $employee = null;
    public array $relatives = [];

    public function setEmployee(Employee $employee): void
    {
        $this->employee = $employee;

        $this->relatives = $this->employee
            ->relatives()
            ->select('id', 'name', 'phone', 'relation')
            ->get()
            ->map(
                fn(Relative $relative) => [
                    'id' => $relative->id,
                    'name' => $relative->name,
                    'phone' => $relative->phone,
                    'relation' => $relative->relation,
                ],
            )
            ->toArray();
    }

    public function rules(): array
    {
        return [
            'relatives' => ['array'],
            'relatives.*.id' => ['nullable', 'exists:relatives,id'],
            'relatives.*.name' => ['required', 'string', 'max:255'],
            'relatives.*.phone' => ['nullable', 'string', 'max:255'],
            'relatives.*.relation' => ['required', 'string', 'max:255'],
        ];
    }

    public function addRelative(): void
    {
        $this->relatives[] = [
            'id' => null,
            'name' => '',
            'phone' => '',
            'relation' => '',
        ];
    }

    public function removeRelative(int $index, ?int $id = null): void
    {
        if (!array_key_exists($index, $this->relatives)) {
            return;
        }

        unset($this->relatives[$index]);

        if (isset($id)) {
            Relative::find($id)?->delete();
        }

        $this->relatives = array_values($this->relatives);
    }

    public function save(): void
    {
        $this->validate();
        collect($this->relatives)->each(function ($relativeData) {
            $this->employee->relatives()->updateOrCreate(['id' => $relativeData['id'] ?? null], $relativeData);
        });
    }
}
