<?php

use Flux\Flux;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Renderless;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use Modules\Hr\Actions\Employees\DeleteEmployee;
use Modules\Hr\Livewire\Concerns\Employees\ManagesEmployeeForm;
use Modules\Hr\Models\Employee;
use Modules\Hr\Support\Employees\HrAccess;

new #[Layout('layouts::desk'), Title('hr_employees.titles.form')] class extends Component {
    use ManagesEmployeeForm;
    use WithFileUploads;

    /** Whether the record being edited is still a draft. */
    #[Locked]
    public bool $isDraft = true;

    /** One token per opened form: a double submit of a new record creates one employee. */
    #[Locked]
    public string $submissionToken = '';

    public bool $showDiscardModal = false;

    public function mount(?Employee $employee = null): void
    {
        if ($employee?->exists) {
            Gate::authorize('update', $employee);
        } else {
            Gate::authorize('create', Employee::class);
            $employee = null;
        }

        $this->fillEmployeeForm($employee);
        $this->isDraft = $employee === null || $employee->isDraft();
        $this->submissionToken = (string) Str::uuid();
    }

    public function save(): void
    {
        $this->saveRecord(complete: true);
    }

    public function saveAsDraft(): void
    {
        abort_unless($this->isDraft, 403);

        $this->saveRecord(complete: false);
    }

    /**
     * Called by the page every few seconds while a draft has unsaved typing.
     *
     * @return array{status: string, message?: string, url?: string}
     */
    #[Renderless]
    public function autosaveDraft(): array
    {
        if (! $this->isDraft) {
            return ['status' => 'complete'];
        }

        abort_unless(auth()->user()->can($this->employeeId === null ? 'employees.create' : 'employees.update'), 403);

        if ($this->employeeId === null && ! HrAccess::canAccessCity(auth()->user(), (int) ($this->form['city_id'] ?? 0))) {
            return ['status' => 'incomplete'];
        }

        $result = Cache::lock($this->submissionKey().':lock', 30)->get(function (): array {
            $this->adoptCreatedRecord();

            return $this->autosaveSilently(fn (): Employee => $this->persistEmployee(complete: false, withPhoto: false));
        });

        if ($result === false) {
            return ['status' => 'busy'];
        }

        if ($result['status'] === 'saved') {
            Cache::put($this->submissionKey(), $this->employeeId, now()->addDay());
        }

        return $result;
    }

    public function removePhoto(): void
    {
        $this->photo = null;
        $this->resetValidation('photo');
    }

    public function discardDraft(): void
    {
        abort_unless($this->isDraft && $this->employeeId !== null, 403);

        app(DeleteEmployee::class)->handle(Employee::query()->findOrFail($this->employeeId), discardDraft: true);

        Flux::toast(variant: 'success', text: __('hr_employees.toasts.draft_discarded'));

        $this->redirectRoute('hr.employees.index', ['view' => 'incomplete'], navigate: true);
    }

    protected function saveRecord(bool $complete): void
    {
        try {
            $employee = Cache::lock($this->submissionKey().':lock', 30)->block(10, function () use ($complete): Employee {
                $this->adoptCreatedRecord();

                return $this->persistEmployee($complete);
            });
        } catch (LockTimeoutException) {
            Flux::toast(text: __('hr_employees.form.save_in_progress'));

            return;
        }

        Cache::put($this->submissionKey(), $employee->id, now()->addDay());
        $this->isDraft = $employee->isDraft();

        Flux::toast(variant: 'success', text: $complete ? __('hr_employees.toasts.saved_complete') : __('hr_employees.toasts.saved_draft'));

        if ($complete && $employee->username) {
            $this->redirectRoute('hr.employees.show', $employee->username, navigate: true);
        } else {
            $this->redirectRoute('hr.employees.edit', $employee->id, navigate: true);
        }
    }

    /**
     * A second submit of the same new form edits the record the first one
     * created instead of creating another.
     */
    protected function adoptCreatedRecord(): void
    {
        if ($this->employeeId === null && ($id = Cache::get($this->submissionKey()))) {
            $this->employeeId = (int) $id;
        }
    }

    protected function submissionKey(): string
    {
        return 'hr-employee-form:'.auth()->id().':'.sha1($this->submissionToken);
    }
};
?>

@use('App\Support\BackUrl')
@use('Modules\Hr\Enums\Gender')
@use('Modules\Hr\Enums\MaritalStatus')
@use('Modules\Hr\Enums\Nationality')
@use('Modules\Hr\Support\Employees\EnumLabel')

@php
    $editing = $employeeId !== null;
    $title = $editing ? __('hr_employees.form.edit_title') : __('hr_employees.form.create_title');
    $cities = $this->formCityOptions();
    $grid = 'grid gap-x-4 gap-y-3 @2xl/form:grid-cols-2 @5xl/form:grid-cols-3';
@endphp

<section
    class="@container/form flex flex-col gap-5"
    data-employee-form
    x-data="{
        dirty: false,
        note: '',
        timer: null,
        touched() {
            this.dirty = true;
            if (! @js($isDraft)) return;
            clearTimeout(this.timer);
            this.timer = setTimeout(() => this.autosave(), 4000);
        },
        async autosave() {
            if (! this.dirty) return;
            const result = await $wire.autosaveDraft();
            if (result.status === 'saved') {
                this.dirty = false;
                this.note = result.message;
                if (! @js($editing) && result.url) history.replaceState(history.state, '', result.url);
            }
        },
    }"
    x-on:input="touched()"
    x-on:change="touched()"
>
    <x-ui.detail-header
        :back="BackUrl::for(route('hr.employees.index'))"
        :breadcrumbs="[
            ['label' => __('hr_employees.titles.index'), 'url' => route('hr.employees.index')],
            ['label' => $title],
        ]"
        :title="$title"
    >
        <x-slot:meta>
            @if ($isDraft)
                <span class="rounded-md bg-status-warn-soft px-2 py-0.5 font-semibold text-status-warn">{{ __('hr_employees.status.incomplete') }}</span>
                <span x-text="note" aria-live="polite" data-autosave-note></span>
            @endif
        </x-slot:meta>
    </x-ui.detail-header>

    <form wire:submit="save" novalidate class="flex flex-col gap-5">
        <x-ui.panel :title="__('hr_employees.form.identity')">
            <div class="p-4 {{ $grid }}">
                <x-ui.form-input wire:model="form.first_name_ar" :label="__('hr_employees.fields.first_name_ar')" maxlength="255" required dir="rtl" />
                <x-ui.form-input wire:model="form.father_name_ar" :label="__('hr_employees.fields.father_name_ar')" maxlength="255" required dir="rtl" />
                <x-ui.form-input wire:model="form.last_name_ar" :label="__('hr_employees.fields.last_name_ar')" maxlength="255" required dir="rtl" />
                <x-ui.form-input wire:model="form.first_name_en" :label="__('hr_employees.fields.first_name_en')" maxlength="255" required dir="ltr" />
                <x-ui.form-input wire:model="form.father_name_en" :label="__('hr_employees.fields.father_name_en')" maxlength="255" required dir="ltr" />
                <x-ui.form-input wire:model="form.last_name_en" :label="__('hr_employees.fields.last_name_en')" maxlength="255" required dir="ltr" />
                <x-ui.form-input wire:model="form.mother_name_ar" :label="__('hr_employees.fields.mother_name_ar')" maxlength="255" required dir="rtl" />
                <x-ui.form-input wire:model="form.mother_name_en" :label="__('hr_employees.fields.mother_name_en')" maxlength="255" required dir="ltr" />
                <x-ui.form-input wire:model="form.username" :label="__('hr_employees.fields.username')" :help="__('hr_employees.form.username_help')" maxlength="255" required dir="ltr" suffix="@hcsyria.org" />
                <x-ui.form-select wire:model="form.city_id" :label="__('common.city')" :placeholder="__('common.select')" required :disabled="count($cities) === 1">
                    @foreach ($cities as $cityId => $cityName)
                        <option value="{{ $cityId }}">{{ $cityName }}</option>
                    @endforeach
                </x-ui.form-select>
                <x-ui.form-select wire:model.live="form.nationality" :label="__('hr_employees.fields.nationality')" :placeholder="__('common.select')" :required="true">
                    @foreach (EnumLabel::options(Nationality::class) as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </x-ui.form-select>
                <x-ui.form-input wire:model="form.national_number" :label="__('hr_employees.fields.national_number')" maxlength="255" required dir="ltr" inputmode="numeric" />
            </div>
        </x-ui.panel>

        <x-ui.panel :title="__('hr_employees.profile.personal')">
            <div class="p-4 {{ $grid }}">
                <x-ui.form-select wire:model="form.gender" :label="__('hr_employees.fields.gender')" :placeholder="__('common.select')" required>
                    @foreach (EnumLabel::options(Gender::class) as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </x-ui.form-select>
                <x-ui.form-select wire:model="form.marital_status" :label="__('hr_employees.fields.marital_status')" :placeholder="__('common.select')" required>
                    @foreach (EnumLabel::options(MaritalStatus::class) as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </x-ui.form-select>
                <x-ui.form-input wire:model="form.children" :label="__('hr_employees.fields.children')" type="number" min="0" step="1" />
                <x-ui.form-input wire:model="form.dob" :label="__('hr_employees.fields.dob')" type="date" />
                <x-ui.form-input wire:model="form.actual_dob" :label="__('hr_employees.fields.actual_dob')" :help="__('hr_employees.form.actual_dob_help')" type="date" />
                <x-ui.form-input wire:model="form.joined_at" :label="__('hr_employees.fields.joined_at')" type="date" />
                <x-ui.form-input wire:model="form.pob_ar" :label="__('hr_employees.fields.pob_ar')" maxlength="255" dir="rtl" />
                <x-ui.form-input wire:model="form.pob_en" :label="__('hr_employees.fields.pob_en')" maxlength="255" dir="ltr" />
                <x-ui.form-input wire:model="form.civil_registry_office" :label="__('hr_employees.fields.civil_registry_office')" maxlength="255" />
                <x-ui.form-input wire:model="form.civil_registry_number" :label="__('hr_employees.fields.civil_registry_number')" maxlength="255" dir="ltr" />
                <x-ui.form-input wire:model="form.skills" :label="__('hr_employees.fields.skills')" :help="__('hr_employees.profile.tags_help')" maxlength="255" />
                <x-ui.form-input wire:model="form.interests" :label="__('hr_employees.fields.interests')" :help="__('hr_employees.profile.tags_help')" maxlength="255" />
            </div>
        </x-ui.panel>

        <x-ui.panel :title="__('hr_employees.form.contact')">
            <div class="p-4 {{ $grid }}">
                <x-ui.form-input wire:model="form.work_phone" :label="__('hr_employees.fields.work_phone')" :help="__('hr_employees.form.phone_help')" type="tel" maxlength="10" dir="ltr" />
                <x-ui.form-input wire:model="form.personal_phone" :label="__('hr_employees.fields.personal_phone')" :help="__('hr_employees.form.phone_help')" type="tel" maxlength="10" dir="ltr" />
                <x-ui.form-input wire:model="form.personal_email" :label="__('hr_employees.fields.personal_email')" type="email" maxlength="255" dir="ltr" />
                <x-ui.form-input wire:model="form.whatsapp_work" :label="__('hr_employees.fields.whatsapp_work')" maxlength="255" dir="ltr" />
                <x-ui.form-input wire:model="form.telegram_work" :label="__('hr_employees.fields.telegram_work')" maxlength="255" dir="ltr" />
                <x-ui.form-input wire:model="form.linkedin" :label="__('hr_employees.fields.linkedin')" type="url" maxlength="255" dir="ltr" />
                <x-ui.form-input wire:model="form.facebook" :label="__('hr_employees.fields.facebook')" type="url" maxlength="255" dir="ltr" />
                <x-ui.form-input wire:model="form.instagram" :label="__('hr_employees.fields.instagram')" type="url" maxlength="255" dir="ltr" />
                <span class="hidden @5xl/form:block"></span>
                <x-ui.form-textarea wire:model="form.address_ar" :label="__('hr_employees.fields.address_ar')" rows="2" dir="rtl" />
                <x-ui.form-textarea wire:model="form.address_en" :label="__('hr_employees.fields.address_en')" rows="2" dir="ltr" />
            </div>
        </x-ui.panel>

        <x-ui.panel :title="__('hr_employees.form.photo_and_board')">
            <div class="p-4 {{ $grid }}">
                <div class="flex items-center gap-4">
                    <x-ui.avatar :src="$photo ? $photo->temporaryUrl() : ($editing ? Employee::photoUrl($employeeId) : null)" class="size-20 shrink-0 rounded-full" />
                    <x-ui.form-file wire:model="photo" :label="__('hr_employees.fields.photo')" accept=".jpg,.jpeg,.png,.webp" types="JPG, PNG, WEBP" max-size="4 MB" class="min-w-0 flex-1" />
                </div>
                <div class="flex flex-col gap-3">
                    <label class="flex items-center gap-2 text-sm font-semibold text-secondary-dark dark:text-ink">
                        <input type="checkbox" wire:model.live="isBoardMember" class="size-4 rounded border-secondary-light accent-primary" data-test="board-member">
                        {{ __('hr_employees.fields.is_board_member') }}
                    </label>
                    @if ($isBoardMember)
                        <x-ui.form-input wire:model="form.board_member_started_at" :label="__('hr_employees.fields.board_member_started_at')" type="date" />
                        <x-ui.form-input wire:model="form.board_member_end_at" :label="__('hr_employees.fields.board_member_end_at')" type="date" />
                    @endif
                </div>
            </div>
        </x-ui.panel>

        <div class="sticky bottom-0 z-10 flex flex-wrap items-center justify-end gap-2 border-t border-panel-border bg-ground py-3 dark:bg-surface-0">
            @if ($errors->any())
                <p class="me-auto text-sm font-semibold text-status-critical" role="alert">{{ trans_choice('common.fields_need_attention', $errors->count(), ['count' => $errors->count()]) }}</p>
            @endif
            @if ($isDraft && $editing)
                <x-ui.button type="button" tone="ghost" icon="trash" wire:click="$set('showDiscardModal', true)" data-test="discard-draft">{{ __('hr_employees.actions.discard_draft') }}</x-ui.button>
            @endif
            <x-ui.button type="button" :href="BackUrl::for(route('hr.employees.index'))" wire:navigate>{{ __('common.cancel') }}</x-ui.button>
            @if ($isDraft)
                <x-ui.button type="button" wire:click="saveAsDraft" wire:loading.attr="disabled" data-test="save-draft">{{ __('hr_employees.actions.save_draft') }}</x-ui.button>
            @endif
            <x-ui.button type="submit" tone="primary" wire:loading.attr="disabled" data-test="save-employee">{{ $isDraft ? __('hr_employees.actions.save_complete') : __('common.save_changes') }}</x-ui.button>
        </div>
    </form>

    <x-ui.confirm-modal
        wire:model="showDiscardModal"
        :title="__('hr_employees.confirm.discard_title')"
        :message="__('hr_employees.confirm.discard_body')"
        confirm="discardDraft"
        :confirm-label="__('hr_employees.actions.discard_draft')"
        confirm-test="confirm-discard-draft"
    />
</section>
