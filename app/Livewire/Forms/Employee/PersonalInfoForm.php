<?php

namespace App\Livewire\Forms\Employee;

use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use App\Rules\SafeImage;
use App\Services\SecureImageUploadService;
use App\Support\BlindIndex;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Validate;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\Form;
use Livewire\WithFileUploads;

class PersonalInfoForm extends Form
{
    use WithFileUploads;

    public ?Employee $employee = null;

    public $photo = null;

    #[Validate('required|string')]
    public $first_name_ar;

    #[Validate('required|string')]
    public $first_name_en;

    #[Validate('required|string')]
    public $last_name_ar;

    #[Validate('required|string')]
    public $last_name_en;

    public $id;

    #[Validate('required|string')]
    public $father_name_ar;

    #[Validate('required|string')]
    public $father_name_en;

    #[Validate('required|string')]
    public $mother_name_ar;

    #[Validate('required|string')]
    public $mother_name_en;

    #[Validate('nullable|string')]
    public $address_ar;

    #[Validate('nullable|string')]
    public $address_en;

    public $email;

    public $national_number;

    #[Validate('nullable|date')]
    public $dob;

    #[Validate('nullable|date')]
    public $actual_dob;

    #[Validate('required|in:1,2')]
    public $gender;

    #[Validate('required')]
    public $marital_status;

    #[Validate('nullable|integer|min:0')]
    public $children;

    #[Validate('integer')]
    public $nationality;

    #[Validate('required|exists:cities,id')]
    public $city_id;

    #[Validate('nullable|string')]
    public $civil_registry_office;

    #[Validate('nullable|string')]
    public $civil_registry_number;

    #[Validate('nullable|string')]
    public $pob_en;

    #[Validate('nullable|string')]
    public $pob_ar;

    #[Validate('nullable|string')]
    public $work_phone;

    #[Validate('nullable|string')]
    public $personal_phone;

    #[Validate('nullable|email')]
    public $personal_email;

    #[Validate('nullable|string')]
    public $skills;

    #[Validate('nullable|string')]
    public $interests;

    #[Validate('boolean')]
    public $is_board_member = false;

    #[Validate('nullable|date')]
    public $board_member_started_at;

    #[Validate('nullable|date|after:board_member_started_at')]
    public $board_member_end_at;

    #[Validate('boolean')]
    public $is_active = true;

    public $username;

    /**
     * When true, a platform login account (work email) is created/kept for the
     * employee. When false, the employee exists without platform access — the
     * username is still required (used for the profile page and QR scan ID).
     */
    public bool $create_account = true;

    #[Validate('nullable|exists:employees,id')]
    public $direct_manager_id = null;

    #[Validate('nullable|exists:employees,id')]
    public $tech_manager_id = null;

    #[Validate('nullable|string')]
    public $whatsapp_work;

    #[Validate('nullable|string')]
    public $telegram_work;

    #[Validate('nullable|url')]
    public $facebook;

    #[Validate('nullable|url')]
    public $instagram;

    #[Validate('nullable|url')]
    public $linkedin;

    public function rules()
    {
        $rules = [
            // 'image' alone accepts anything GD can parse under any name the
            // client chooses. SafeImage narrows the set and the upload service
            // re-encodes it before it is stored.
            'photo' => $this->photo instanceof TemporaryUploadedFile
                ? ['mimes:jpeg,jpg,png,webp', 'max:4096', new SafeImage('avatar')]
                : ['nullable'],
            // Uniqueness is checked against the blind index, not the column
            // itself: the encrypted value differs on every write, so a
            // unique rule on national_number would never match and the same
            // person could be entered twice.
            'national_number' => [
                'required',
                Rule::unique('employees', 'national_number_index')
                    ->ignore($this->employee?->id)
                    ->where(fn ($query) => $query->where(
                        'national_number_index',
                        BlindIndex::of($this->national_number),
                    )),
            ],
            // Username is always required: it drives the profile page and QR scan ID.
            'username' => [
                'required',
                Rule::unique('employees', 'username')->ignore($this->employee?->id),
            ],
        ];

        // The work email/login account is only validated when HR opts to create it.
        if ($this->create_account) {
            $rules['email'] = [
                'required',
                Rule::unique('users', 'email')->ignore($this->employee?->account?->id),
            ];
        }

        return $rules;
    }

    public function setEmployee(Employee $employee): void
    {
        $this->employee = $employee;
        $this->id = $employee->id;
        $this->city_id = $employee->city_id;
        $this->national_number = $employee->national_number;
        $this->direct_manager_id = $employee->direct_manager_id;
        $this->tech_manager_id = $employee->tech_manager_id;
        $this->actual_dob = $employee->actual_dob?->format('Y-m-d');
        $this->dob = $employee->dob?->format('Y-m-d');
        $this->gender = $employee->gender;
        $this->marital_status = $employee->marital_status;
        $this->children = $employee->children;
        $this->nationality = $employee->nationality;
        $this->civil_registry_office = $employee->civil_registry_office;
        $this->civil_registry_number = $employee->civil_registry_number;

        $this->pob_en = $employee->pob_en;
        $this->pob_ar = $employee->pob_ar;

        $this->work_phone = $employee->work_phone;
        $this->personal_phone = $employee->personal_phone;
        $this->personal_email = $employee->personal_email;
        $this->skills = $employee->skills;
        $this->interests = $employee->interests;
        $this->is_active = $employee->is_active;
        $this->is_board_member = $employee->is_board_member;
        $this->board_member_started_at = $employee->board_member_started_at?->format('Y-m-d');
        $this->board_member_end_at = $employee->board_member_end_at?->format('Y-m-d');
        $this->first_name_en = $employee->first_name_en;
        $this->first_name_ar = $employee->first_name_ar;
        $this->last_name_en = $employee->last_name_en;
        $this->last_name_ar = $employee->last_name_ar;
        $this->father_name_en = $employee->father_name_en;
        $this->father_name_ar = $employee->father_name_ar;
        $this->mother_name_en = $employee->mother_name_en;
        $this->mother_name_ar = $employee->mother_name_ar;
        $this->address_en = $employee->address_en;
        $this->address_ar = $employee->address_ar;
        $this->username = $employee->username;
        $this->email = $employee->account?->email;
        $this->photo = $employee->avatar_url;
        // Pre-tick the checkbox only if a login account already exists.
        $this->create_account = (bool) $employee->account;
        $this->whatsapp_work = $employee->whatsapp_work ?? null;
        $this->telegram_work = $employee->telegram_work ?? null;
        $this->facebook = $employee->facebook ?? null;
        $this->instagram = $employee->instagram ?? null;
        $this->linkedin = $employee->linkedin ?? null;
    }

    public function saveInformation(): Employee
    {
        // Work email is derived from the username, only when an account is wanted.
        $this->email = $this->create_account ? $this->buildWorkEmail($this->username) : null;

        $this->validate();
        $this->authorizeScope();

        // create_account is a UI-only flag, not an employees column.
        $payload = $this->except(['email', 'photo', 'create_account']);

        if ($this->employee) {
            $this->employee->update($payload);
        } else {
            $this->employee = Employee::create($payload);
        }

        // Create/update the login account only when HR opted in. Unchecking on
        // an existing employee leaves any current account untouched.
        if ($this->create_account) {
            $this->persistAccount();
        }

        if ($this->photo instanceof TemporaryUploadedFile) {
            // Employee photographs are protection-sensitive: they are stored
            // re-encoded, under a random server-chosen name, on the private
            // disk, and served only through EmployeeAvatarController.
            app(SecureImageUploadService::class)->attachTo(
                model: $this->employee,
                file: $this->photo,
                collection: 'avatar',
                profile: 'avatar',
                disk: 'local',
            );
        }

        return $this->employee;
    }

    protected function buildWorkEmail(?string $username): ?string
    {
        $username = trim($username ?? '');

        return $username ? strtolower($username).'@hcsyria.org' : null;
    }

    protected function extractUsernameFromEmail(?string $email): ?string
    {
        return $email ? explode('@', $email, 2)[0] ?? null : null;
    }

    /**
     * Create/update the login account. Privileged role changes are deliberately
     * restricted to the dedicated Access Management module.
     */
    protected function persistAccount(): void
    {
        if ($this->employee->account) {
            $this->employee->account->update(['email' => $this->email]);

            return;
        }

        $user = User::create([
            'email' => $this->email,
            'password' => Hash::make(Str::password(64)),
            'employee_id' => $this->employee->id,
        ]);

        if (Role::query()->where('name', 'employee')->exists()) {
            $user->assignRole('employee');
        }

        Password::sendResetLink(['email' => $user->email]);
    }

    private function authorizeScope(): void
    {
        $user = auth()->user();
        abort_unless($user, 403);

        if ($this->employee) {
            Gate::authorize('update', $this->employee);
        } else {
            Gate::authorize('create', [Employee::class, (int) $this->city_id]);
        }

        $activeCityId = $user->activeCityId();
        if ($activeCityId !== null && $activeCityId !== (int) $this->city_id) {
            throw ValidationException::withMessages([
                'city_id' => __('You may only manage employees in your active city.'),
            ]);
        }

        foreach (['direct_manager_id', 'tech_manager_id'] as $field) {
            $managerId = $this->{$field};
            if ($managerId && ! Employee::query()
                ->whereKey($managerId)
                ->where('city_id', $this->city_id)
                ->exists()) {
                throw ValidationException::withMessages([
                    $field => __('The selected manager must belong to the employee city.'),
                ]);
            }
        }
    }
}
