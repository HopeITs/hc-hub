<?php

declare(strict_types=1);

namespace Modules\Hr\Livewire\Concerns\Employees;

use Flux\Flux;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Modules\Hr\Actions\Employees\SaveEmployeeAttachment;
use Modules\Hr\Actions\Employees\SaveEmployeeRecord;
use Modules\Hr\Enums\ContractPlace;
use Modules\Hr\Enums\ContractType;
use Modules\Hr\Enums\EducationStatus;
use Modules\Hr\Enums\EducationType;
use Modules\Hr\Enums\KinshipType;
use Modules\Hr\Support\Employees\HrMedia;

/**
 * The add / edit dialog for the rows of an employee's record on the profile
 * page: education, experience, courses, relatives and documents. One dialog
 * serves all five; `recordKind` says which fields it shows.
 *
 * Expects the page to use WithFileUploads and ReadsEmployeeProfile.
 */
trait ManagesEmployeeRecords
{
    /** Where a course was organised: stored values, labels are translated. */
    public const COURSE_ORGANIZATIONS = ['Hope Center', 'Personal', 'Other'];

    public bool $showRecordModal = false;

    #[Locked]
    public string $recordKind = '';

    #[Locked]
    public ?int $recordId = null;

    /** @var array<string, mixed> */
    public array $record = [];

    /** @var TemporaryUploadedFile|null */
    public $recordFile = null;

    public bool $showRecordDeleteModal = false;

    #[Locked]
    public string $deletingKind = '';

    #[Locked]
    public ?int $deletingId = null;

    public function openRecord(string $kind, ?int $id = null): void
    {
        abort_unless($this->canEdit && array_key_exists($kind, $this->recordDefaults()), 404);
        Gate::authorize('update', $this->employee);

        $this->resetValidation();
        $this->recordKind = $kind;
        $this->recordId = $id;
        $this->recordFile = null;
        $this->record = $id === null ? $this->recordDefaults()[$kind] : $this->recordValues($kind, $id);
        $this->showRecordModal = true;
    }

    public function closeRecord(): void
    {
        $this->showRecordModal = false;
        $this->recordKind = '';
        $this->recordId = null;
        $this->record = [];
        $this->recordFile = null;
        $this->resetValidation();
    }

    public function saveRecord(): void
    {
        abort_unless($this->canEdit && $this->recordKind !== '', 404);

        $validated = $this->validate($this->recordRules(), [], $this->recordAttributes());
        $data = array_map(fn (mixed $value): mixed => $value === '' ? null : $value, $validated['record']);
        $file = $this->recordFile instanceof TemporaryUploadedFile ? $this->recordFile : null;

        if ($this->recordKind === 'attachment') {
            app(SaveEmployeeAttachment::class)->handle($this->employee, $this->recordId, (string) $data['name'], (string) ($data['category'] ?? ''), $file);
        } else {
            app(SaveEmployeeRecord::class)->handle($this->employee, $this->recordKind, $this->recordId, $data, $file);
        }

        $this->closeRecord();
        $this->forgetRecords();

        Flux::toast(variant: 'success', text: __('hr_employees.toasts.record_saved'));
    }

    public function confirmRecordDelete(string $kind, int $id): void
    {
        abort_unless($this->canEdit && array_key_exists($kind, $this->recordDefaults()), 404);

        $this->deletingKind = $kind;
        $this->deletingId = $id;
        $this->showRecordDeleteModal = true;
    }

    public function cancelRecordDelete(): void
    {
        $this->showRecordDeleteModal = false;
        $this->deletingKind = '';
        $this->deletingId = null;
    }

    public function deleteRecord(): void
    {
        abort_unless($this->canEdit, 404);

        if ($this->deletingId !== null) {
            $this->deletingKind === 'attachment'
                ? app(SaveEmployeeAttachment::class)->delete($this->employee, $this->deletingId)
                : app(SaveEmployeeRecord::class)->delete($this->employee, $this->deletingKind, $this->deletingId);

            Flux::toast(variant: 'success', text: __('hr_employees.toasts.record_deleted'));
        }

        $this->cancelRecordDelete();
        $this->forgetRecords();
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    protected function recordDefaults(): array
    {
        return [
            'education' => ['name' => '', 'specialization' => '', 'degree' => '', 'status' => (string) EducationStatus::Graduated->value, 'notes' => ''],
            'experience' => ['job_title' => '', 'company' => '', 'start_date' => '', 'end_date' => '', 'place' => (string) ContractPlace::Onsite->value, 'type' => (string) ContractType::FullTime->value],
            'course' => ['category_id' => '', 'title' => '', 'organization' => '', 'place' => '', 'granted_by' => '', 'notes' => '', 'end_date' => '', 'has_certificate' => false, 'duration' => '', 'status' => '5'],
            'relative' => ['name' => '', 'phone' => '', 'relation' => ''],
            'attachment' => ['name' => '', 'category' => ''],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function recordValues(string $kind, int $id): array
    {
        if ($kind === 'attachment') {
            $media = HrMedia::find($id);

            abort_unless($media !== null && $media->model_type === HrMedia::EMPLOYEE && (int) $media->model_id === $this->employeeId, 404);

            return ['name' => HrMedia::property($media, 'name', (string) $media->name), 'category' => HrMedia::property($media, 'category')];
        }

        $row = $this->employee->{SaveEmployeeRecord::KINDS[$kind][0]}()->findOrFail($id);
        $values = [];

        foreach (array_keys($this->recordDefaults()[$kind]) as $field) {
            $value = $row->getAttribute($field);

            $values[$field] = match (true) {
                $value instanceof \DateTimeInterface => $value->format('Y-m-d'),
                is_bool($value) => $value,
                default => (string) ($value ?? ''),
            };
        }

        return $values;
    }

    /**
     * @return array<string, mixed>
     */
    protected function recordRules(): array
    {
        $supportingFile = ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'];

        return match ($this->recordKind) {
            'education' => [
                'record.name' => ['required', 'string', 'max:255'],
                'record.specialization' => ['nullable', 'string', 'max:255'],
                'record.degree' => ['required', Rule::enum(EducationType::class)],
                'record.status' => ['required', Rule::enum(EducationStatus::class)],
                'record.notes' => ['nullable', 'string', 'max:1000'],
                'recordFile' => $supportingFile,
            ],
            'experience' => [
                'record.job_title' => ['required', 'string', 'max:255'],
                'record.company' => ['required', 'string', 'max:255'],
                'record.start_date' => ['required', 'date'],
                'record.end_date' => ['nullable', 'date', 'after_or_equal:record.start_date'],
                'record.place' => ['required', Rule::enum(ContractPlace::class)],
                'record.type' => ['required', Rule::enum(ContractType::class)],
                'recordFile' => $supportingFile,
            ],
            'course' => [
                'record.category_id' => ['nullable', 'exists:course_categories,id'],
                'record.title' => ['required', 'string', 'max:255'],
                'record.organization' => ['required', Rule::in(self::COURSE_ORGANIZATIONS)],
                'record.place' => ['nullable', 'integer', 'min:1', 'max:2'],
                'record.granted_by' => ['nullable', 'string', 'max:255'],
                'record.notes' => ['nullable', 'string'],
                'record.end_date' => ['nullable', 'date'],
                'record.has_certificate' => ['nullable', 'boolean'],
                'record.duration' => ['nullable', 'string', 'max:255'],
                'record.status' => ['required', 'integer', 'min:1', 'max:5'],
                'recordFile' => $supportingFile,
            ],
            'relative' => [
                'record.name' => ['required', 'string', 'max:255'],
                'record.phone' => ['nullable', 'string', 'max:255'],
                'record.relation' => ['required', Rule::enum(KinshipType::class)],
            ],
            'attachment' => [
                'record.name' => ['required', 'string', 'max:255'],
                'record.category' => ['nullable', Rule::in(SaveEmployeeAttachment::CATEGORIES)],
                'recordFile' => [$this->recordId === null ? 'required' : 'nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            ],
            default => abort(404),
        };
    }

    /**
     * @return array<string, string>
     */
    protected function recordAttributes(): array
    {
        $attributes = ['recordFile' => __('hr_employees.records.file')];

        foreach (array_keys($this->record) as $field) {
            $attributes['record.'.$field] = __('hr_employees.records.fields.'.$field);
        }

        return $attributes;
    }

    protected function forgetRecords(): void
    {
        unset($this->educations, $this->experiences, $this->courses, $this->relatives, $this->attachments, $this->recordFiles);
    }
}
