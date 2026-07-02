<?php

namespace App\Livewire\Forms\Employee;

use App\Enums\AttachmentCategory;
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

            if (empty($item['media_id']) || (!empty($item['file']) && $item['file'] instanceof TemporaryUploadedFile)) {
                $fileValidation[] = 'required_if:attachments.' . $index . '.media_id,null';
            }

            $rules["attachments.{$index}.file"] = $fileValidation;
        }

        return $rules;
    }

    public function setEmployee(Employee $employee): void
    {
        $this->employee = $employee;
        $this->attachments = $employee
            ->getMedia('attachments')
            ->map(
                fn($media) => [
                    'media_id' => $media->id,
                    'name' => $media->getCustomProperty('name', ''),
                    'category' => $media->getCustomProperty('category', ''),
                    'old_file_url' => $media->getUrl(),
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
        if (!$this->employee) {
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
                            'category' => $item['category']
                        ])
                        ->toMediaCollection('attachments');
                } catch (\Throwable $e) {
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
}
