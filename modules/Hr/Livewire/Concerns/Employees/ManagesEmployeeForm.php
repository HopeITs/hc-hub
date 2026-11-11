<?php

declare(strict_types=1);

namespace Modules\Hr\Livewire\Concerns\Employees;

use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Modules\Core\Models\City;
use Modules\Hr\Actions\Employees\SaveEmployee;
use Modules\Hr\Enums\Gender;
use Modules\Hr\Enums\MaritalStatus;
use Modules\Hr\Enums\Nationality;
use Modules\Hr\Models\Employee;
use Modules\Hr\Support\Employees\HrAccess;

/**
 * The employee record form's state and validation: one public property per
 * column, the rules for a complete record and the looser rules for a draft.
 *
 * A draft keeps the uniqueness and format rules (a wrong national number is
 * wrong in a draft too) and only drops "required" from the three choices a
 * new starter may not have given yet: gender, marital status, nationality.
 */
trait ManagesEmployeeForm
{
    /** The columns the form writes, in the order of the form. */
    public const FIELDS = [
        'first_name_ar', 'first_name_en', 'father_name_ar', 'father_name_en', 'last_name_ar', 'last_name_en',
        'mother_name_ar', 'mother_name_en', 'username', 'city_id', 'national_number', 'nationality', 'gender',
        'marital_status', 'children', 'dob', 'actual_dob', 'pob_ar', 'pob_en', 'civil_registry_office',
        'civil_registry_number', 'joined_at', 'work_phone', 'personal_phone', 'personal_email', 'whatsapp_work',
        'telegram_work', 'facebook', 'instagram', 'linkedin', 'address_ar', 'address_en', 'skills', 'interests',
        'board_member_started_at', 'board_member_end_at',
    ];

    private const DATE_FIELDS = ['dob', 'actual_dob', 'joined_at', 'board_member_started_at', 'board_member_end_at'];

    #[Locked]
    public ?int $employeeId = null;

    /** @var array<string, string> */
    public array $form = [];

    public bool $isBoardMember = false;

    /** @var TemporaryUploadedFile|null */
    public $photo = null;

    protected function fillEmployeeForm(?Employee $employee): void
    {
        $this->employeeId = $employee?->id;
        $this->isBoardMember = (bool) ($employee?->is_board_member ?? false);

        foreach (self::FIELDS as $field) {
            $value = $employee?->getAttribute($field);

            $this->form[$field] = match (true) {
                $value instanceof \DateTimeInterface => $value->format('Y-m-d'),
                default => (string) ($value ?? ''),
            };
        }

        if ($employee === null) {
            $cities = $this->formCityOptions();
            $this->form['city_id'] = count($cities) === 1 ? (string) array_key_first($cities) : '';
            $this->form['nationality'] = (string) Nationality::Syrian->value;
        }
    }

    /**
     * The cities this user may file an employee under.
     *
     * @return array<int, string>
     */
    public function formCityOptions(): array
    {
        $allowed = HrAccess::cityIds(Auth::user());

        return array_filter(
            City::options(withNational: false),
            fn (int $id): bool => $allowed === null || in_array($id, $allowed, true),
            ARRAY_FILTER_USE_KEY,
        );
    }

    /**
     * Validate and save. Returns the saved employee.
     */
    protected function persistEmployee(bool $complete, bool $withPhoto = true): Employee
    {
        $this->normaliseForm();

        $validated = $this->validate($this->employeeRules($complete), [
            'form.work_phone.regex' => __('hr_employees.validation.phone_format'),
            'form.personal_phone.regex' => __('hr_employees.validation.phone_format'),
            'form.national_number.digits' => __('hr_employees.validation.national_number_syrian'),
        ], $this->employeeAttributes());

        $data = array_map(fn (mixed $value): mixed => $value === '' ? null : $value, $validated['form']);
        $data['is_board_member'] = $this->isBoardMember;

        if (! $this->isBoardMember) {
            $data['board_member_started_at'] = null;
            $data['board_member_end_at'] = null;
        }

        $employee = app(SaveEmployee::class)->handle(
            $this->employeeId === null ? null : Employee::query()->findOrFail($this->employeeId),
            $data,
            $complete,
            Auth::user(),
            $withPhoto && $this->photo instanceof TemporaryUploadedFile ? $this->photo : null,
        );

        $this->employeeId = $employee->id;

        if ($withPhoto) {
            $this->photo = null;
        }

        return $employee;
    }

    /**
     * @return array<string, mixed>
     */
    protected function employeeRules(bool $complete): array
    {
        $choice = fn (string $enum): array => [$complete ? 'required' : 'nullable', Rule::enum($enum)];
        $isSyrian = (int) ($this->form['nationality'] ?? 0) === Nationality::Syrian->value;

        return [
            'form.first_name_ar' => ['required', 'string', 'max:255'],
            'form.first_name_en' => ['required', 'string', 'max:255'],
            'form.last_name_ar' => ['required', 'string', 'max:255'],
            'form.last_name_en' => ['required', 'string', 'max:255'],
            'form.father_name_ar' => ['required', 'string', 'max:255'],
            'form.father_name_en' => ['required', 'string', 'max:255'],
            'form.mother_name_ar' => ['required', 'string', 'max:255'],
            'form.mother_name_en' => ['required', 'string', 'max:255'],
            'form.username' => ['required', 'string', 'max:255', Rule::unique('employees', 'username')->ignore($this->employeeId)],
            'form.city_id' => ['required', 'integer', 'exists:cities,id'],
            'form.national_number' => ['required', 'string', $isSyrian ? 'digits:11' : 'max:255', $this->nationalNumberIsFree(...)],
            'form.nationality' => $choice(Nationality::class),
            'form.gender' => [$complete ? 'required' : 'nullable', Rule::in([Gender::Male->value, Gender::Female->value])],
            'form.marital_status' => $choice(MaritalStatus::class),
            'form.children' => ['nullable', 'integer', 'min:0'],
            'form.dob' => ['nullable', 'date'],
            'form.actual_dob' => ['nullable', 'date'],
            'form.pob_ar' => ['nullable', 'string', 'max:255'],
            'form.pob_en' => ['nullable', 'string', 'max:255'],
            'form.civil_registry_office' => ['nullable', 'string', 'max:255'],
            'form.civil_registry_number' => ['nullable', 'string', 'max:255'],
            'form.joined_at' => ['nullable', 'date'],
            'form.work_phone' => ['nullable', 'regex:/^09\d{8}$/'],
            'form.personal_phone' => ['nullable', 'regex:/^09\d{8}$/'],
            'form.personal_email' => ['nullable', 'email', 'max:255'],
            'form.whatsapp_work' => ['nullable', 'string', 'max:255'],
            'form.telegram_work' => ['nullable', 'string', 'max:255'],
            'form.facebook' => ['nullable', 'url', 'max:255'],
            'form.instagram' => ['nullable', 'url', 'max:255'],
            'form.linkedin' => ['nullable', 'url', 'max:255'],
            'form.address_ar' => ['nullable', 'string'],
            'form.address_en' => ['nullable', 'string'],
            'form.skills' => ['nullable', 'string', 'max:255'],
            'form.interests' => ['nullable', 'string', 'max:255'],
            'form.board_member_started_at' => ['nullable', 'date'],
            'form.board_member_end_at' => ['nullable', 'date', 'after:form.board_member_started_at'],
            'photo' => $this->photo instanceof TemporaryUploadedFile ? ['image', 'mimes:jpeg,jpg,png,webp', 'max:4096'] : ['nullable'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function employeeAttributes(): array
    {
        $attributes = ['photo' => __('hr_employees.fields.photo')];

        foreach (self::FIELDS as $field) {
            $attributes['form.'.$field] = __('hr_employees.fields.'.$field);
        }

        return $attributes;
    }

    /**
     * Arabic-Indic digits typed into the number fields are stored as Western
     * digits, and blank dates as null.
     */
    protected function normaliseForm(): void
    {
        $digits = ['٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9'];

        foreach (['national_number', 'work_phone', 'personal_phone'] as $field) {
            $this->form[$field] = trim(strtr((string) ($this->form[$field] ?? ''), $digits));
        }

        foreach (self::FIELDS as $field) {
            $this->form[$field] = trim((string) ($this->form[$field] ?? ''));
        }

        $this->form['username'] = strtolower($this->form['username']);
    }

    /**
     * A national number belongs to one employee: name the holder and their
     * city so HR knows the record already exists.
     */
    private function nationalNumberIsFree(string $attribute, mixed $value, Closure $fail): void
    {
        $existing = Employee::query()
            ->select(['id', 'city_id', 'first_name_en', 'first_name_ar', 'last_name_en', 'last_name_ar'])
            ->where('national_number', $value)
            ->when($this->employeeId !== null, fn ($query) => $query->whereKeyNot($this->employeeId))
            ->first();

        if ($existing !== null) {
            $fail(__('hr_employees.validation.national_number_taken', [
                'name' => $existing->name,
                'city' => City::nameOf($existing->city_id) ?: __('common.unknown_city'),
            ]));
        }
    }

    /**
     * Run a save without showing its validation errors: an autosave must not
     * turn half-typed fields red.
     *
     * @return array{status: string, message?: string, url?: string}
     */
    protected function autosaveSilently(callable $save): array
    {
        $errors = $this->getErrorBag();

        try {
            /** @var Employee $employee */
            $employee = $save();
        } catch (ValidationException) {
            return ['status' => 'incomplete'];
        } finally {
            $this->setErrorBag($errors);
        }

        return [
            'status' => 'saved',
            'message' => __('hr_employees.form.autosaved', ['time' => $employee->updated_at?->timezone(config('app.timezone'))->format('H:i') ?? '']),
            'url' => route('hr.employees.edit', $employee->id),
        ];
    }
}
