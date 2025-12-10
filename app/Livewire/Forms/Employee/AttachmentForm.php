<?php

namespace App\Livewire\Forms\Employee;

use App\Enums\AttachmentCategory;
use App\Models\Attachment;
use App\Models\Employee;
use Livewire\Form;
use Livewire\WithFileUploads;
use Illuminate\Validation\Rule;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class AttachmentForm extends Form
{
    use WithFileUploads;

    public ?Employee $employee = null;

    public array $attachments = [];

    protected function rules(): array
    {
        $rules = [];

        foreach ($this->attachments as $index => $item) {
            $rules["attachments.{$index}.name"] = 'required|string|max:255';
            $rules["attachments.{$index}.category"] = ['nullable', Rule::in(array_keys(AttachmentCategory::options()))];
            $fileValidation = ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'];

            if (empty($item['id']) || (!empty($item['file']) && $item['file'] instanceof TemporaryUploadedFile)) {
                $fileValidation[] = 'required_if:attachments.' . $index . '.id,null';
            }

            $rules["attachments.{$index}.file"] = $fileValidation;
        }

        return $rules;
    }

    public function setEmployee(Employee $employee): void
    {
        $this->employee = $employee;
        $this->attachments = $employee
            ->attachments()
            ->select('id', 'name', 'category')
            ->get()
            ->map(
                fn(Attachment $a) => [
                    'id' => $a->id,
                    'name' => $a->name,
                    'category' => $a->category?->value ?? '',
                    'old_file_url' => $a->getFirstMediaUrl('file'),
                    'file_name' => $a->getFirstMedia('file')->file_name ?? null,
                    'file' => null,
                ],
            )
            ->toArray();

        if (empty($this->attachments)) {
            $this->addAttachment();
        }
    }

    public function addAttachment(): void
    {
        $this->attachments[] = [
            'id' => null,
            'name' => '',
            'category' => '',
            'old_file_url' => null,
            'file_name' => null,
            'file' => null,
        ];
    }

    public function removeAttachment(int $index, ?int $id = null): void
    {
        if ($id) {
            $attachment = Attachment::find($id);
            if ($attachment) {
                $attachment->clearMediaCollection('file');
                $attachment->delete();
            }
        }

        unset($this->attachments[$index]);
        $this->attachments = array_values($this->attachments);
    }

    public function save(): void
    {
        if (!$this->employee) {
            return;
        }

        $this->validate();

        foreach ($this->attachments as $index => $item) {
            $data = collect($item)
                ->except(['file', 'old_file_url', 'file_name', 'id'])
                ->toArray();

            $attachment = Attachment::updateOrCreate(['id' => $item['id']], $data + ['employee_id' => $this->employee->id]);

            if ($item['file'] instanceof TemporaryUploadedFile) {
                $attachment->clearMediaCollection('file');

                $attachment
                    ->addMedia($item['file']->getRealPath())
                    ->usingFileName('attachment-' . $attachment->id . '.' . $item['file']->getClientOriginalExtension())
                    ->preservingOriginal()
                    ->toMediaCollection('file');
            }
        }
        $this->setEmployee($this->employee->fresh());
    }
}
