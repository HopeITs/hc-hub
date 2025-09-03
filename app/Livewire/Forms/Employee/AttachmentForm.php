<?php

namespace App\Livewire\Forms\Employee;

use App\Models\Employee;
use App\Models\EmployeeAttachment;
use Livewire\Form;
use Livewire\WithFileUploads;

class AttachmentForm extends Form
{
    use WithFileUploads;

    public ?Employee $employee = null;

    public array $attachments = [];

    public function setEmployee(Employee $employee): void
    {
        $this->employee = $employee;
        $this->attachments = $employee->attachments()
            ->select('id', 'name', 'file_path')
            ->get()
            ->map(fn($a) => [
                'id' => $a->id,
                'name' => $a->name,
                'file' => null,
            ])
            ->toArray();

        if (empty($this->attachments)) {
            $this->addAttachment();
        }
    }

    public function hasEmployee(): bool
    {
        return !is_null($this->employee?->id);
    }

    public function addAttachment(): void
    {
        $this->attachments[] = [
            'id' => null,
            'name' => '',
            'file' => null,
        ];
    }

    public function removeAttachment(int $index, ?int $id = null): void
    {
        if ($id) {
            EmployeeAttachment::find($id)?->delete();
        }
        unset($this->attachments[$index]);
        $this->attachments = array_values($this->attachments);
    }

    public function save(): void
    {
        if (!$this->employee) return;

        foreach ($this->attachments as $item) {
            $path = null;

            if (isset($item['file']) && $item['file']) {
                $path = $item['file']->store('attachments', 'public');
            }

            EmployeeAttachment::updateOrCreate(
                ['id' => $item['id']],
                [
                    'employee_id' => $this->employee->id,
                    'name' => $item['name'],
                    'file_path' => $path ?? null,
                ]
            );
        }
    }
}
