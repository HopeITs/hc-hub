<?php

namespace Modules\Hr\Livewire\Forms\Employee;

use Modules\Hr\Enums\AttachmentCategory;
use Modules\Hr\Models\Employee;
use Illuminate\Validation\Rule;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\Form;
use Livewire\WithFileUploads;
use Throwable;

class AttachmentForm extends Form
{
    use WithFileUploads;

    public ?Employee $employee = null;

    public array $attachments = [];

    public function setEmployee(Employee $employee): void
    {
        $this->employee = $employee;
        $this->attachments = $employee
            ->getMedia('attachments')
            ->map(
                fn ($media) => [
                    'media_id' => $media->id,
                    'name' => $media->getCustomProperty('name', ''),
                    'category' => $media->getCustomProperty('category', ''),
                    'old_file_url' => route('employee-files.download', $media),
                    'file_name' => $media->file_name,
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
            'media_id' => null,
            'name' => '',
            'category' => '',
            'old_file_url' => null,
            'file_name' => null,
            'file' => null,
        ];
    }

    public function removeAttachment(int $index, $mediaId = null): void
    {
        if ($mediaId) {
            $media = $this->employee->media()->find($mediaId);
            if ($media) {
                $media->delete();
            }
        }

        unset($this->attachments[$index]);
        $this->attachments = array_values($this->attachments);
    }

    public function save(): void
    {
        if (! $this->employee) {
            return;
        }

        $this->validate();

        foreach ($this->attachments as $index => $item) {
            if ($item['file'] instanceof TemporaryUploadedFile) {
                try {
                    $media = $this->employee
                        ->addMedia($item['file']->getRealPath())
                        ->usingFileName($item['file']->getClientOriginalName())
                        ->preservingOriginal()
                        ->withCustomProperties([
                            'name' => $item['name'],
                            'category' => $item['category'],
                        ])
                        ->toMediaCollection('attachments', 'local');
                } catch (Throwable $e) {
                    // Surface a field error instead of a 500; keep the old
                    // media untouched so a failed replacement loses nothing.
                    report($e);
                    $this->addError("attachments.{$index}.file", __('validation.uploaded', ['attribute' => $item['name'] ?: __('general.attachment')]));

                    continue;
                }

                if ($item['media_id']) {
                    $oldMedia = $this->employee->media()->find($item['media_id']);
                    if ($oldMedia) {
                        $oldMedia->delete();
                    }
                }
            } elseif ($item['media_id']) {
                $media = $this->employee->media()->find($item['media_id']);
                if ($media) {
                    $media->setCustomProperty('name', $item['name']);
                    $media->setCustomProperty('category', $item['category']);
                    $media->save();
                }
            }
        }

        $this->setEmployee($this->employee->fresh());
    }

    protected function rules(): array
    {
        $rules = [];

        foreach ($this->attachments as $index => $item) {
            // An untouched blank row (the one setEmployee() seeds when the
            // employee has no attachments yet, or one just added and left
            // alone) is not something to complain about — save() ignores it.
            if ($this->isBlankRow($item)) {
                continue;
            }

            $rules["attachments.{$index}.name"] = ['required', 'string', 'max:255'];
            $rules["attachments.{$index}.category"] = ['nullable', Rule::in(array_keys(AttachmentCategory::options()))];

            // A row that has no stored media behind it needs a file. This was
            // `required_if:attachments.N.media_id,null`, which rendered as
            // "The attachments.0.file field is required when attachments.0
            // media id is empty." — the rule is unconditional here anyway.
            $rules["attachments.{$index}.file"] = [
                empty($item['media_id']) ? 'required' : 'nullable',
                'file',
                'mimes:pdf,jpg,jpeg,png',
                'max:10240',
            ];
        }

        return $rules;
    }

    /**
     * Human field names, so errors read "The file (attachment 1) field is
     * required." instead of leaking the array path at the user.
     */
    protected function validationAttributes(): array
    {
        $attributes = [];

        foreach (array_keys($this->attachments) as $index) {
            $row = __('general.attachment').' '.($index + 1);

            $attributes["attachments.{$index}.name"] = __('general.name').' ('.$row.')';
            $attributes["attachments.{$index}.category"] = __('general.category').' ('.$row.')';
            $attributes["attachments.{$index}.file"] = __('general.file').' ('.$row.')';
        }

        return $attributes;
    }

    private function isBlankRow(array $item): bool
    {
        return empty($item['media_id'])
            && ! ($item['file'] ?? null) instanceof TemporaryUploadedFile
            && blank($item['name'] ?? null)
            && blank($item['category'] ?? null);
    }
}
