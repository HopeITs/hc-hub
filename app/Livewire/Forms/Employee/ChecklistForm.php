<?php

namespace App\Livewire\Forms\Employee;

use App\Models\Checklist;
use App\Models\Course;
use App\Models\Employee;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\Form;

class ChecklistForm extends Form
{
    public ?Employee $employee = null;
    public ?Checklist $checklist = null;
    public array $checklists = [];
    public function mount()
    {
        $this->checklistForm['checklists'] = $this->allItems->map(function ($item) {
            return [
                'item_id'      => $item->id,
                'delivered_id' => optional($item->defaultDeliverable()->first())->employee_id,
            ];
        })->toArray();
    }
    public function setEmployee(Employee $employee): void
    {
        $this->employee = $employee;

        $this->checklists = $employee
            ->checklists()
            ->select('id', 'item_id', 'delivered_id', 'brand', 'model', 'serial', 'delivered_at', 'delivered_status', 'returned_at', 'returned_status', 'notes')
            ->get()
            ->map(
                fn(Checklist $checklist) => [
                    'id' => $checklist->id,
                    'item_id' => $checklist->item_id,
                    'delivered_id' => $checklist->delivered_id,
                    'brand' => $checklist->brand,
                    'model' => $checklist->model,
                    'serial' => $checklist->serial,
                    'delivered_at' => $checklist->delivered_at?->format('Y-m-d'),
                    'delivered_status' => $checklist->delivered_status,
                    'returned_at' => $checklist->returned_at?->format('Y-m-d'),
                    'returned_status' => $checklist->returned_status,
                    'notes' => $checklist->notes,
                ],
            )
            ->toArray();
    }

    public function rules(): array
    {
        return [
            'checklists' => ['array'],
            'checklists.*.id' => ['nullable', 'exists:checklists,id'],
            'checklists.*.item_id' => ['nullable', 'integer', 'exists:checklist_items,id'],
            'checklists.*.delivered_id' => ['nullable', 'integer', 'exists:employees,id'],
            'checklists.*.brand' => ['nullable', 'string', 'max:255'],
            'checklists.*.model' => ['nullable', 'string', 'max:255'],
            'checklists.*.serial' => ['nullable', 'string', 'max:255'],
            'checklists.*.delivered_at' => ['nullable', 'date'],
            'checklists.*.delivered_status' => ['nullable', 'string', 'max:255'],
            'checklists.*.returned_at' => ['nullable', 'date'],
            'checklists.*.returned_status' => ['nullable', 'string', 'max:255'],
            'checklists.*.notes' => ['nullable', 'string'],
        ];
    }

    public function addChecklist(): void
    {
        $this->checklists[] = [
            'id' => null,
            'item_id' => null,
            'delivered_id' => null,
            'brand' => '',
            'model' => '',
            'serial' => '',
            'delivered_at' => now()->format('Y-m-d'),
            'delivered_status' => '',
            'returned_at' => null,
            'returned_status' => '',
            'notes' => '',
        ];
    }

    public function removeChecklist(int $index, ?int $id = null): void
    {
        if (!array_key_exists($index, $this->checklists)) {
            return;
        }

        unset($this->checklists[$index]);

        if ($id) {
            Checklist::find($id)?->delete();
        }

        $this->checklists = array_values($this->checklists);
    }

    public function save(): void
    {
        $this->validate();
        foreach ($this->checklists as $index => $checklist) {
            if (array_key_exists('id', $checklist) && isset($checklist['id'])) {

                $this->checklist = Checklist::find($checklist['id']);
                $this->checklist->update($checklist);
            } else {
                $checklist['employee_id'] = $this->employee->id;
                $this->checklist = Checklist::create($checklist);
            }


        }
//        collect($this->checklists)->each(function ($data) {
//            $this->employee->checklists()->updateOrCreate(['id' => $data['id'] ?? null], $data);
//        });
    }
}
