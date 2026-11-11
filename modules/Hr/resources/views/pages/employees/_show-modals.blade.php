{{--
    The dialogs of an employee profile: a record row (education, experience,
    course, relative, document), a contact section, a badge, holding a
    position, and the confirmations. Included by ⚡show.blade.php.
--}}
@use('Modules\Hr\Actions\Employees\SaveEmployeeAttachment')
@use('Modules\Hr\Enums\BadgeSubtype')
@use('Modules\Hr\Enums\BadgeType')
@use('Modules\Hr\Enums\ContractPlace')
@use('Modules\Hr\Enums\ContractType')
@use('Modules\Hr\Enums\Currency')
@use('Modules\Hr\Enums\EducationStatus')
@use('Modules\Hr\Enums\EducationType')
@use('Modules\Hr\Enums\KinshipType')
@use('Modules\Hr\Models\CourseCategory')
@use('Modules\Hr\Support\Employees\EnumLabel')

@php
    $suffix = app()->getLocale() === 'ar' ? 'ar' : 'en';
    $recordTitle = $recordKind === '' ? '' : __('hr_employees.records.'.($recordId === null ? 'add_' : 'edit_').$recordKind);
@endphp

<x-ui.modal wire:model="showRecordModal" size="lg" :title="$recordTitle" submit="saveRecord" close="closeRecord" required-note>
    @if ($showRecordModal)
        <div class="grid gap-x-4 gap-y-3 @2xl/modal:grid-cols-2" data-record-form="{{ $recordKind }}">
            @if ($recordKind === 'education')
                <x-ui.form-input wire:model="record.name" :label="__('hr_employees.records.fields.name')" maxlength="255" required autofocus />
                <x-ui.form-input wire:model="record.specialization" :label="__('hr_employees.records.fields.specialization')" maxlength="255" />
                <x-ui.form-select wire:model="record.degree" :label="__('hr_employees.records.fields.degree')" :placeholder="__('common.select')" required>
                    @foreach (EnumLabel::options(EducationType::class) as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </x-ui.form-select>
                <x-ui.form-select wire:model="record.status" :label="__('hr_employees.records.fields.status')" required>
                    @foreach (EnumLabel::options(EducationStatus::class) as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </x-ui.form-select>
                <x-ui.form-textarea wire:model="record.notes" :label="__('hr_employees.records.fields.notes')" rows="2" maxlength="1000" class="@2xl/modal:col-span-2" />
            @elseif ($recordKind === 'experience')
                <x-ui.form-input wire:model="record.job_title" :label="__('hr_employees.records.fields.job_title')" maxlength="255" required autofocus />
                <x-ui.form-input wire:model="record.company" :label="__('hr_employees.records.fields.company')" maxlength="255" required />
                <x-ui.form-input wire:model="record.start_date" :label="__('hr_employees.records.fields.start_date')" type="date" required />
                <x-ui.form-input wire:model="record.end_date" :label="__('hr_employees.records.fields.end_date')" type="date" />
                <x-ui.form-select wire:model="record.place" :label="__('hr_employees.records.fields.place')" required>
                    @foreach (EnumLabel::options(ContractPlace::class) as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </x-ui.form-select>
                <x-ui.form-select wire:model="record.type" :label="__('hr_employees.records.fields.type')" required>
                    @foreach ([ContractType::FullTime, ContractType::PartTime, ContractType::Freelance] as $case)
                        <option value="{{ $case->value }}">{{ EnumLabel::of(ContractType::class, $case->value) }}</option>
                    @endforeach
                </x-ui.form-select>
            @elseif ($recordKind === 'course')
                <x-ui.form-input wire:model="record.title" :label="__('hr_employees.records.fields.title')" maxlength="255" required autofocus class="@2xl/modal:col-span-2" />
                <x-ui.form-select wire:model="record.organization" :label="__('hr_employees.records.fields.organization')" :placeholder="__('common.select')" required>
                    @foreach ($this::COURSE_ORGANIZATIONS as $organization)
                        <option value="{{ $organization }}">{{ __('hr_employees.records.organizations.'.\Illuminate\Support\Str::snake($organization)) }}</option>
                    @endforeach
                </x-ui.form-select>
                <x-ui.form-select wire:model="record.category_id" :label="__('hr_employees.records.fields.category_id')">
                    <option value="">—</option>
                    @foreach (CourseCategory::query()->orderBy('name_'.$suffix)->get(['id', 'name_en', 'name_ar']) as $category)
                        <option value="{{ $category->id }}">{{ $category->{'name_'.$suffix} }}</option>
                    @endforeach
                </x-ui.form-select>
                <x-ui.form-select wire:model="record.place" :label="__('hr_employees.records.fields.place')">
                    <option value="">—</option>
                    <option value="1">{{ __('hr_employees.records.course_place.1') }}</option>
                    <option value="2">{{ __('hr_employees.records.course_place.2') }}</option>
                </x-ui.form-select>
                <x-ui.form-select wire:model="record.status" :label="__('hr_employees.records.fields.status')" required>
                    @foreach ([1, 2, 3, 4, 5] as $courseStatus)
                        <option value="{{ $courseStatus }}">{{ __('hr_employees.records.course_status.'.$courseStatus) }}</option>
                    @endforeach
                </x-ui.form-select>
                <x-ui.form-input wire:model="record.granted_by" :label="__('hr_employees.records.fields.granted_by')" maxlength="255" />
                <x-ui.form-input wire:model="record.duration" :label="__('hr_employees.records.fields.duration')" maxlength="255" />
                <x-ui.form-input wire:model="record.end_date" :label="__('hr_employees.records.fields.end_date')" type="date" />
                <label class="flex items-center gap-2 self-end pb-2 text-sm text-secondary-dark dark:text-ink">
                    <input type="checkbox" wire:model="record.has_certificate" class="size-4 rounded border-secondary-light accent-primary">
                    {{ __('hr_employees.records.fields.has_certificate') }}
                </label>
                <x-ui.form-textarea wire:model="record.notes" :label="__('hr_employees.records.fields.notes')" rows="2" class="@2xl/modal:col-span-2" />
            @elseif ($recordKind === 'relative')
                <x-ui.form-input wire:model="record.name" :label="__('hr_employees.records.fields.name')" maxlength="255" required autofocus />
                <x-ui.form-select wire:model="record.relation" :label="__('hr_employees.records.fields.relation')" :placeholder="__('common.select')" required>
                    @foreach (EnumLabel::options(KinshipType::class, withUnknown: true) as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </x-ui.form-select>
                <x-ui.form-input wire:model="record.phone" :label="__('hr_employees.records.fields.phone')" type="tel" maxlength="255" dir="ltr" />
            @elseif ($recordKind === 'attachment')
                <x-ui.form-input wire:model="record.name" :label="__('hr_employees.records.fields.name')" maxlength="255" required autofocus />
                <x-ui.form-select wire:model="record.category" :label="__('hr_employees.records.fields.category')">
                    <option value="">—</option>
                    @foreach (SaveEmployeeAttachment::CATEGORIES as $category)
                        <option value="{{ $category }}">{{ __('hr_employees.records.categories.'.$category) }}</option>
                    @endforeach
                </x-ui.form-select>
            @endif

            @if ($recordKind !== 'relative')
                <x-ui.form-file
                    wire:model="recordFile"
                    :label="__('hr_employees.records.file')"
                    :required="$recordKind === 'attachment' && $recordId === null"
                    :help="$recordId !== null ? __('hr_employees.records.file_replace_help') : null"
                    accept=".pdf,.jpg,.jpeg,.png"
                    types="PDF, JPG, PNG"
                    :max-size="$recordKind === 'attachment' ? '10 MB' : '5 MB'"
                    class="@2xl/modal:col-span-2"
                />
            @endif
        </div>
    @endif

    <x-slot:actions>
        <x-ui.button type="submit" tone="primary" data-test="save-record">{{ __('common.save_changes') }}</x-ui.button>
    </x-slot:actions>
</x-ui.modal>

<x-ui.confirm-modal
    wire:model="showRecordDeleteModal"
    close="cancelRecordDelete"
    :title="__('hr_employees.confirm.record_delete_title')"
    :message="__('hr_employees.confirm.record_delete_body')"
    confirm="deleteRecord"
    :confirm-label="__('common.delete')"
    confirm-test="confirm-record-delete"
/>

<x-ui.modal wire:model="showSectionModal" size="md" :title="$editingSection === '' ? '' : __('hr_employees.profile.sections.'.str_replace('-', '_', $editingSection))" submit="saveSection" close="closeSection">
    @if ($showSectionModal)
        <div class="grid gap-x-4 gap-y-3">
            @error('section')
                <p class="rounded-lg bg-status-critical-soft px-3 py-2 text-sm text-status-critical" role="alert">{{ $message }}</p>
            @enderror
            @foreach ($this::SECTIONS[$editingSection] ?? [] as $sectionField)
                @if (in_array($sectionField, ['skills', 'interests'], true))
                    <x-ui.form-input wire:model="sectionFields.{{ $sectionField }}" :label="__('hr_employees.fields.'.$sectionField)" :help="__('hr_employees.profile.tags_help')" maxlength="255" wire:key="section-{{ $sectionField }}" />
                @else
                    <x-ui.form-input
                        wire:model="sectionFields.{{ $sectionField }}"
                        :label="__('hr_employees.fields.'.$sectionField)"
                        :type="$sectionField === 'personal_email' ? 'email' : (str_ends_with($sectionField, '_phone') ? 'tel' : 'text')"
                        :dir="str_starts_with($sectionField, 'address_') ? 'auto' : 'ltr'"
                        maxlength="255"
                        wire:key="section-{{ $sectionField }}"
                    />
                @endif
            @endforeach
        </div>
    @endif

    <x-slot:actions>
        <x-ui.button type="submit" tone="primary" data-test="save-section">{{ __('common.save_changes') }}</x-ui.button>
    </x-slot:actions>
</x-ui.modal>

<x-ui.modal wire:model="showBadgeModal" size="lg" :title="$badgeId === null ? __('hr_employees.badges.add') : __('hr_employees.badges.edit')" submit="saveBadge" close="closeBadge" required-note>
    @if ($showBadgeModal)
        @php
            $kindOptions = BadgeSubtype::optionsForType($badgeType);
        @endphp
        <div class="grid gap-x-4 gap-y-3 @2xl/modal:grid-cols-2">
            <x-ui.form-select wire:model.live="badgeType" :label="__('common.type')" :placeholder="__('common.select')" required>
                @foreach ([BadgeType::Warning, BadgeType::Reward, BadgeType::Salary_Increase] as $case)
                    <option value="{{ $case->value }}">{{ BadgeType::getLocalizedName($case->value) }}</option>
                @endforeach
            </x-ui.form-select>
            <x-ui.form-input wire:model="badgeDate" :label="__('hr_employees.badges.date')" type="date" required />

            @if ($kindOptions !== [])
                <x-ui.form-select wire:model.live="badgeSubtype" :label="__('hr_employees.badges.kind')" :placeholder="__('common.select')" required class="@2xl/modal:col-span-2" wire:key="badge-kind-{{ $badgeType }}">
                    @foreach ($kindOptions as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </x-ui.form-select>
            @endif

            @if (BadgeSubtype::isFinancial($badgeSubtype))
                <x-ui.form-input wire:model="badgeAmount" :label="__('hr_employees.badges.amount')" type="number" min="0" step="0.01" required />
                <x-ui.form-select wire:model="badgeCurrency" :label="__('common.currency')" required>
                    @foreach (EnumLabel::options(Currency::class) as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </x-ui.form-select>
            @endif

            @if (BadgeSubtype::isPromotion($badgeSubtype))
                <x-ui.form-input wire:model="badgeNewPosition" :label="__('hr_employees.badges.new_position')" maxlength="255" required class="@2xl/modal:col-span-2" />
            @endif

            @if (BadgeSubtype::isLeave($badgeSubtype))
                <x-ui.form-select wire:model="badgeLeaveUnit" :label="__('hr_employees.badges.leave_unit')" :placeholder="__('common.select')" required>
                    <option value="hour">{{ __('hr_employees.badges.unit_hour') }}</option>
                    <option value="day">{{ __('hr_employees.badges.unit_day') }}</option>
                </x-ui.form-select>
                <x-ui.form-input wire:model="badgeAmount" :label="__('hr_employees.badges.leave_quantity')" type="number" min="1" step="1" required />
            @endif

            @if ((int) $badgeType === BadgeType::Salary_Increase->value && $canViewFinancial)
                <x-ui.form-input wire:model="badgeCurrentSalary" :label="__('hr_employees.badges.current_salary')" type="number" min="0" step="0.01" />
                <x-ui.form-input wire:model="badgeNewSalary" :label="__('hr_employees.badges.new_salary')" type="number" min="0" step="0.01" />
                <x-ui.form-select wire:model="badgeCurrency" :label="__('common.currency')">
                    @foreach (EnumLabel::options(Currency::class) as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </x-ui.form-select>
            @endif

            <x-ui.form-textarea wire:model="badgeReason" :label="__('hr_employees.badges.reason')" rows="2" maxlength="5000" class="@2xl/modal:col-span-2" />
            <x-ui.form-textarea wire:model="badgeNotes" :label="__('common.notes')" rows="2" maxlength="5000" class="@2xl/modal:col-span-2" />
        </div>
    @endif

    <x-slot:actions>
        <x-ui.button type="submit" tone="primary" data-test="save-badge">{{ __('common.save_changes') }}</x-ui.button>
    </x-slot:actions>
</x-ui.modal>

<x-ui.confirm-modal
    wire:model="showBadgeDeleteModal"
    close="cancelBadgeDelete"
    :title="__('hr_employees.badges.delete')"
    :message="__('hr_employees.confirm.record_delete_body')"
    confirm="deleteBadge"
    :confirm-label="__('common.delete')"
    confirm-test="confirm-badge-delete"
/>

<x-ui.modal wire:model="showDraftModal" size="md" :title="__('hr_employees.actions.reserve_position')" :description="__('hr_employees.draft.description')" submit="saveDraft" close="closeDraft" required-note>
    @if ($showDraftModal)
        <div class="grid gap-x-4 gap-y-3">
            <x-ui.form-select wire:model.live="draftCityId" :label="__('common.city')" :placeholder="__('common.select')" required>
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
        <x-ui.button type="submit" tone="primary" data-test="save-draft-position">{{ __('hr_employees.actions.reserve_position') }}</x-ui.button>
    </x-slot:actions>
</x-ui.modal>
