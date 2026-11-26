<?php

use Flux\Flux;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Renderless;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use Modules\Hr\Actions\Contracts\ReservePosition;
use Modules\Hr\Actions\Employees\DeleteEmployee;
use Modules\Hr\Livewire\Concerns\Employees\ManagesEmployeeForm;
use Modules\Hr\Livewire\Concerns\Employees\PicksDraftPosition;
use Modules\Hr\Models\Employee;
use Modules\Hr\Models\RecruitmentJobOffer;
use Modules\Hr\Support\Employees\HrAccess;

/*
| The employee record form, as in HR: a Profile tab and a Contact tab. A new
| record's first save stays here and offers to hold a position with a draft
| contract; both answers open the record's edit page. Saving an existing
| record stays on the tab it was saved from.
*/
new #[Layout('layouts::desk'), Title('hr_employees.titles.form')] class extends Component {
    use ManagesEmployeeForm;
    use PicksDraftPosition;
    use WithFileUploads;

    /** Whether the record being edited is still a draft. */
    #[Locked]
    public bool $isDraft = true;

    /** Opened as "New employee": the first save leads to the draft-position step. */
    #[Locked]
    public bool $isNew = false;

    /** One token per opened form: a double submit of a new record creates one employee. */
    #[Locked]
    public string $submissionToken = '';

    public bool $showDiscardModal = false;

    #[Locked]
    public bool $showDraftPrompt = false;

    public function mount(?Employee $employee = null): void
    {
        if ($employee?->exists) {
            Gate::authorize('update', $employee);
        } else {
            Gate::authorize('create', Employee::class);
            $employee = null;
        }

        $this->fillEmployeeForm($employee);
        $this->isNew = $employee === null;
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
        if (! $this->isDraft || $this->showDraftPrompt) {
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

    /**
     * The step after a new record's first save: hold a position with a
     * draft contract (ReservePosition), then open the record.
     */
    public function createDraftAndContinue(): void
    {
        abort_unless($this->showDraftPrompt && $this->employeeId !== null, 403);

        $employee = Employee::query()->findOrFail($this->employeeId);

        if (! $this->draftCityIsEditable()) {
            $this->draftCityId = (string) $employee->city_id;
        }

        $this->validate(['draftPositionId' => ['required', 'integer']], [], ['draftPositionId' => __('hr_employees.fields.position')]);

        app(ReservePosition::class)->handle(
            $employee,
            (int) $this->draftPositionId,
            $this->draftCityId === '' ? null : (int) $this->draftCityId,
            $this->draftProgramId === '' ? null : (int) $this->draftProgramId,
        );

        Flux::toast(variant: 'success', text: __('hr_employees.toasts.position_reserved'));

        $this->redirectRoute('hr.employees.edit', $employee->id, navigate: true);
    }

    public function skipDraft(): void
    {
        abort_unless($this->employeeId !== null, 403);

        $this->redirectRoute('hr.employees.edit', $this->employeeId, navigate: true);
    }

    /** Only a nationwide HR role holds a position in another city (as in HR). */
    public function draftCityIsEditable(): bool
    {
        return HrAccess::cityIds(auth()->user()) === null;
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

        if ($this->isNew) {
            $this->activeTab = 'information';
            $this->draftCityId = (string) $employee->city_id;
            $this->draftProgramId = '';
            $this->draftPositionId = '';
            $this->showDraftPrompt = true;
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
@use('Modules\Hr\Actions\Employees\CreateEmployeeAccount')
@use('Modules\Hr\Enums\Gender')
@use('Modules\Hr\Enums\MaritalStatus')
@use('Modules\Hr\Enums\Nationality')
@use('Modules\Hr\Support\Employees\EnumLabel')

@php
    $editing = $employeeId !== null && ! $isNew;
    $saved = $employeeId !== null;
    $title = $editing ? __('hr_employees.form.edit_title') : __('hr_employees.form.create_title');
    $cities = $this->formCityOptions();
    $grid = 'grid gap-x-4 gap-y-3 @2xl/form:grid-cols-2';
    $tabs = [
        ['key' => 'information', 'label' => __('hr_employees.form.tab_information')],
        ['key' => 'contact', 'label' => __('hr_employees.form.tab_contact')],
    ];
    $filled = fn (string ...$fields): bool => collect($fields)->every(fn (string $field): bool => trim((string) ($form[$field] ?? '')) !== '');
    $checklist = [
        'to_save_draft' => [
            ['key' => 'names', 'label' => __('hr_employees.form.names'), 'done' => $filled('first_name_ar', 'first_name_en', 'last_name_ar', 'last_name_en', 'father_name_ar', 'father_name_en', 'mother_name_ar', 'mother_name_en'), 'tab' => 'information'],
            ['key' => 'city', 'label' => __('common.city'), 'done' => $filled('city_id'), 'tab' => 'information'],
            ['key' => 'national_number', 'label' => __('hr_employees.fields.national_number'), 'done' => $filled('national_number'), 'tab' => 'information'],
            ['key' => 'username', 'label' => __('hr_employees.fields.username'), 'done' => $filled('username'), 'tab' => 'contact'],
        ],
        'to_complete' => [
            ['key' => 'gender', 'label' => __('hr_employees.fields.gender'), 'done' => $filled('gender'), 'tab' => 'information'],
            ['key' => 'marital_status', 'label' => __('hr_employees.fields.marital_status'), 'done' => $filled('marital_status'), 'tab' => 'information'],
        ],
    ];
    $sections = ['records', 'family', 'badges', 'equipment', 'contracts'];
    $workEmail = trim((string) ($form['username'] ?? '')) === '' ? CreateEmployeeAccount::DOMAIN : strtolower(trim((string) $form['username'])).CreateEmployeeAccount::DOMAIN;
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

    @unless ($editing)
        @include('hr::pages.employees._form-offers-awaiting')
    @endunless

    @if ($isDraft)
        <x-ui.panel :title="__('hr_employees.form.status_title')" data-test="record-status">
            <div class="flex flex-col gap-3 p-4 @2xl/form:flex-row @2xl/form:items-start @2xl/form:gap-8">
                @foreach ($checklist as $group => $items)
                    <div class="flex flex-col gap-1.5">
                        <p class="text-sm font-semibold text-secondary-dark dark:text-ink">{{ __('hr_employees.form.'.$group) }}</p>
                        <ul class="flex flex-col gap-1 text-sm">
                            @foreach ($items as $item)
                                <li wire:key="check-{{ $item['key'] }}">
                                    <button type="button" wire:click="$set('activeTab', '{{ $item['tab'] }}')" class="inline-flex items-center gap-1.5 hover:underline" data-test="check-{{ $item['key'] }}" data-done="{{ $item['done'] ? 'true' : 'false' }}">
                                        <flux:icon :name="$item['done'] ? 'check-circle' : 'exclamation-circle'" variant="micro" @class(['size-4', 'text-status-good' => $item['done'], 'text-status-critical' => ! $item['done']]) />
                                        <span class="text-secondary-dark dark:text-ink">{{ $item['label'] }}</span>
                                    </button>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
                @if ($editing)
                    <x-ui.button type="button" tone="ghost" icon="trash" class="@2xl/form:ms-auto" wire:click="$set('showDiscardModal', true)" data-test="discard-draft">{{ __('hr_employees.actions.discard_draft') }}</x-ui.button>
                @endif
            </div>
        </x-ui.panel>
    @endif

    <x-ui.relation-tabs :tabs="$tabs" :active="$activeTab" wire-target="activeTab" />

    <form wire:submit="save" novalidate class="flex flex-col gap-5">
        <div @class(['flex flex-col gap-5', 'hidden' => $activeTab !== 'information']) data-tab-panel="information">
            <x-ui.panel :title="__('hr_employees.form.general_info')">
                <div class="p-4 {{ $grid }}">
                    <div class="flex items-center gap-4 @2xl/form:col-span-2">
                        <x-ui.avatar :src="$photo?->isPreviewable() ? $photo->temporaryUrl() : ($saved ? Employee::photoUrl($employeeId) : null)" class="size-20 shrink-0 rounded-full" />
                        <x-ui.form-file wire:model="photo" :label="__('hr_employees.fields.photo')" accept=".jpg,.jpeg,.png,.webp" types="JPG, PNG, WEBP" max-size="4 MB" class="min-w-0 flex-1" />
                    </div>
                    <x-ui.form-input wire:model="form.first_name_ar" :label="__('hr_employees.fields.first_name_ar')" maxlength="255" required dir="rtl" />
                    <x-ui.form-input wire:model="form.first_name_en" :label="__('hr_employees.fields.first_name_en')" maxlength="255" required dir="ltr" />
                    <x-ui.form-input wire:model="form.last_name_ar" :label="__('hr_employees.fields.last_name_ar')" maxlength="255" required dir="rtl" />
                    <x-ui.form-input wire:model="form.last_name_en" :label="__('hr_employees.fields.last_name_en')" maxlength="255" required dir="ltr" />
                    <x-ui.form-input wire:model="form.actual_dob" :label="__('hr_employees.fields.actual_dob')" :help="__('hr_employees.form.actual_dob_help')" type="date" />
                    <x-ui.form-select wire:model="form.city_id" :label="__('common.city')" :placeholder="__('common.select')" required :disabled="count($cities) === 1">
                        @foreach ($cities as $cityId => $cityName)
                            <option value="{{ $cityId }}">{{ $cityName }}</option>
                        @endforeach
                    </x-ui.form-select>
                    <x-ui.form-select wire:model="form.gender" :label="__('hr_employees.fields.gender')" :placeholder="__('common.select')" required>
                        @foreach (EnumLabel::options(Gender::class) as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </x-ui.form-select>
                    <x-ui.form-input wire:model="form.joined_at" :label="__('hr_employees.fields.joined_at')" type="date" />
                    <x-ui.form-select wire:model="form.marital_status" :label="__('hr_employees.fields.marital_status')" :placeholder="__('common.select')" required>
                        @foreach (EnumLabel::options(MaritalStatus::class) as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </x-ui.form-select>
                    <x-ui.form-input wire:model="form.children" :label="__('hr_employees.fields.children')" type="number" min="0" step="1" />
                    <x-ui.form-textarea wire:model="form.address_ar" :label="__('hr_employees.fields.address_ar')" rows="2" dir="rtl" />
                    <x-ui.form-textarea wire:model="form.address_en" :label="__('hr_employees.fields.address_en')" rows="2" dir="ltr" />
                </div>
            </x-ui.panel>

            <x-ui.panel :title="__('hr_employees.form.family_and_identity')">
                <div class="p-4 {{ $grid }}">
                    <x-ui.form-input wire:model="form.father_name_ar" :label="__('hr_employees.fields.father_name_ar')" maxlength="255" required dir="rtl" />
                    <x-ui.form-input wire:model="form.father_name_en" :label="__('hr_employees.fields.father_name_en')" maxlength="255" required dir="ltr" />
                    <x-ui.form-input wire:model="form.mother_name_ar" :label="__('hr_employees.fields.mother_name_ar')" maxlength="255" required dir="rtl" />
                    <x-ui.form-input wire:model="form.mother_name_en" :label="__('hr_employees.fields.mother_name_en')" maxlength="255" required dir="ltr" />
                    <x-ui.form-input wire:model="form.dob" :label="__('hr_employees.fields.dob')" type="date" />
                    <x-ui.form-select wire:model.live="form.nationality" :label="__('hr_employees.fields.nationality')" :placeholder="__('common.select')">
                        @foreach (EnumLabel::options(Nationality::class) as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </x-ui.form-select>
                    <x-ui.form-input wire:model="form.national_number" :label="__('hr_employees.fields.national_number')" maxlength="255" required dir="ltr" inputmode="numeric" />
                    <span class="hidden @2xl/form:block"></span>
                    <x-ui.form-input wire:model="form.pob_ar" :label="__('hr_employees.fields.pob_ar')" maxlength="255" dir="rtl" />
                    <x-ui.form-input wire:model="form.pob_en" :label="__('hr_employees.fields.pob_en')" maxlength="255" dir="ltr" />
                    <x-ui.form-input wire:model="form.civil_registry_office" :label="__('hr_employees.fields.civil_registry_office')" maxlength="255" />
                    <x-ui.form-input wire:model="form.civil_registry_number" :label="__('hr_employees.fields.civil_registry_number')" maxlength="255" dir="ltr" />
                </div>
            </x-ui.panel>

            <x-ui.panel :title="__('hr_employees.form.membership')">
                <div class="flex flex-col gap-3 p-4">
                    <label class="flex items-center gap-2 text-sm font-semibold text-secondary-dark dark:text-ink">
                        <input type="checkbox" wire:model.live="isBoardMember" class="size-4 rounded border-secondary-light accent-primary" data-test="board-member">
                        {{ __('hr_employees.fields.is_board_member') }}
                    </label>
                    @if ($isBoardMember)
                        <div class="{{ $grid }}">
                            <x-ui.form-input wire:model="form.board_member_started_at" :label="__('hr_employees.fields.board_member_started_at')" type="date" />
                            <x-ui.form-input wire:model="form.board_member_end_at" :label="__('hr_employees.fields.board_member_end_at')" type="date" />
                        </div>
                    @endif
                </div>
            </x-ui.panel>

            <x-ui.panel :title="__('hr_employees.form.skills_and_interests')">
                <div class="p-4 {{ $grid }}">
                    <x-ui.form-input wire:model="form.skills" :label="__('hr_employees.fields.skills')" :help="__('hr_employees.profile.tags_help')" maxlength="255" />
                    <x-ui.form-input wire:model="form.interests" :label="__('hr_employees.fields.interests')" :help="__('hr_employees.profile.tags_help')" maxlength="255" />
                </div>
            </x-ui.panel>
        </div>

        <div @class(['flex flex-col gap-5', 'hidden' => $activeTab !== 'contact']) data-tab-panel="contact">
            <x-ui.panel :title="__('hr_employees.form.employment_info')">
                <div class="p-4 {{ $grid }}">
                    <div class="flex flex-col gap-2">
                        <x-ui.form-input wire:model.live.debounce.500ms="form.username" :label="__('hr_employees.fields.username')" :help="__('hr_employees.form.username_help')" maxlength="255" required dir="ltr" :suffix="$createAccount ? CreateEmployeeAccount::DOMAIN : null" />
                        @if ($mayHaveAccount)
                            <label class="flex items-center gap-2 text-sm font-semibold text-secondary-dark dark:text-ink">
                                <input type="checkbox" wire:model.live="createAccount" class="size-4 rounded border-secondary-light accent-primary" data-test="create-account">
                                {{ __('hr_employees.form.create_account') }}
                            </label>
                            <p class="text-xs text-secondary-medium dark:text-ink-muted" dir="auto">{{ __('hr_employees.form.create_account_help', ['email' => $workEmail]) }}</p>
                            @error('createAccount')
                                <p class="text-sm font-semibold text-status-critical" role="alert">{{ $message }}</p>
                            @enderror
                        @else
                            <p class="text-xs text-secondary-medium dark:text-ink-muted" data-test="account-at-contract">{{ __('hr_employees.form.account_at_contract') }}</p>
                        @endif
                    </div>
                    <x-ui.form-input wire:model="form.work_phone" :label="__('hr_employees.fields.work_phone')" :help="__('hr_employees.form.phone_help')" type="tel" maxlength="10" dir="ltr" />
                </div>
            </x-ui.panel>

            <x-ui.panel :title="__('hr_employees.form.contact_info')">
                <div class="p-4 {{ $grid }}">
                    <x-ui.form-input wire:model="form.personal_phone" :label="__('hr_employees.fields.personal_phone')" :help="__('hr_employees.form.phone_help')" type="tel" maxlength="10" dir="ltr" />
                    <x-ui.form-input wire:model="form.personal_email" :label="__('hr_employees.fields.personal_email')" type="email" maxlength="255" dir="ltr" />
                    <x-ui.form-input wire:model="form.whatsapp_work" :label="__('hr_employees.fields.whatsapp_work')" maxlength="255" dir="ltr" />
                    <x-ui.form-input wire:model="form.telegram_work" :label="__('hr_employees.fields.telegram_work')" maxlength="255" dir="ltr" />
                    <x-ui.form-input wire:model="form.facebook" :label="__('hr_employees.fields.facebook')" type="url" maxlength="255" dir="ltr" />
                    <x-ui.form-input wire:model="form.instagram" :label="__('hr_employees.fields.instagram')" type="url" maxlength="255" dir="ltr" />
                    <x-ui.form-input wire:model="form.linkedin" :label="__('hr_employees.fields.linkedin')" type="url" maxlength="255" dir="ltr" />
                </div>
            </x-ui.panel>
        </div>

        <x-ui.panel :title="__('hr_employees.form.more_sections')" data-test="more-sections">
            <div class="flex flex-wrap items-center gap-2 p-4">
                @if ($editing && filled($form['username'] ?? null))
                    @foreach ($sections as $section)
                        <x-ui.button type="button" :href="route('hr.employees.show', ['employee' => $form['username'], 'tab' => $section])" wire:navigate data-test="section-{{ $section }}">{{ __('hr_employees.tabs.'.$section) }}</x-ui.button>
                    @endforeach
                @else
                    <p class="text-sm text-secondary-medium dark:text-ink-muted" data-test="sections-locked">{{ __('hr_employees.form.sections_locked') }}</p>
                @endif
            </div>
        </x-ui.panel>

        <div class="sticky bottom-0 z-10 flex flex-wrap items-center gap-2 border-t border-panel-border bg-ground py-3 dark:bg-surface-0" data-test="form-buttons">
            <x-ui.button type="submit" tone="primary" wire:loading.attr="disabled" data-test="save-employee">{{ match (true) { ! $isDraft => __('hr_employees.actions.save'), $editing => __('hr_employees.actions.complete_record'), default => __('hr_employees.actions.save_complete') } }}</x-ui.button>
            @if ($isDraft)
                <x-ui.button type="button" wire:click="saveAsDraft" wire:loading.attr="disabled" data-test="save-draft">{{ __('hr_employees.actions.save_draft') }}</x-ui.button>
            @endif
            @if (! $editing)
                <x-ui.button type="button" :href="BackUrl::for(route('hr.employees.index'))" wire:navigate data-test="cancel">{{ __('common.cancel') }}</x-ui.button>
            @endif
            @if ($errors->any())
                <p class="ms-auto text-sm font-semibold text-status-critical" role="alert">{{ trans_choice('common.fields_need_attention', $errors->count(), ['count' => $errors->count()]) }}</p>
            @endif
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

    <x-ui.modal wire:model="showDraftPrompt" size="md" :title="__('hr_employees.form.draft_prompt_title')" :description="__('hr_employees.form.draft_prompt_description')" submit="createDraftAndContinue" close="skipDraft" :cancel-label="__('hr_employees.actions.skip_for_now')" required-note>
        @if ($showDraftPrompt)
            <div class="grid gap-x-4 gap-y-3" data-test="draft-prompt">
                <x-ui.form-select wire:model.live="draftCityId" :label="__('common.city')" :placeholder="__('common.select')" required :disabled="! $this->draftCityIsEditable()">
                    @foreach ($this->draftCityOptions as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </x-ui.form-select>
                <x-ui.form-select wire:model.live="draftProgramId" :label="__('common.program')" :placeholder="__('common.select')" :disabled="$draftCityId === ''" wire:key="draft-program-{{ $draftCityId }}">
                    @foreach ($this->draftProgramOptions as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </x-ui.form-select>
                <x-ui.form-select wire:model="draftPositionId" :label="__('hr_employees.fields.position')" :placeholder="__('common.select')" :disabled="$draftProgramId === ''" required wire:key="draft-position-{{ $draftCityId }}-{{ $draftProgramId }}">
                    @foreach ($this->draftPositionOptions as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </x-ui.form-select>
            </div>
        @endif

        <x-slot:actions>
            <x-ui.button type="submit" tone="primary" data-test="create-draft-continue">{{ __('hr_employees.actions.create_draft_continue') }}</x-ui.button>
        </x-slot:actions>
    </x-ui.modal>
</section>
