{{--
    The Overview tab of an employee profile: who they are, where they work and
    how to reach them. Included by ⚡show.blade.php; reads $employee,
    $currentContract, $position and the page's access flags.
--}}
@use('Modules\Core\Models\Center')
@use('Modules\Core\Models\City')
@use('Modules\Core\Models\Program')
@use('Modules\Hr\Enums\Gender')
@use('Modules\Hr\Enums\MaritalStatus')
@use('Modules\Hr\Enums\Nationality')
@use('Modules\Hr\Models\Department')
@use('Modules\Hr\Models\Employee')
@use('Modules\Hr\Support\Employees\EnumLabel')

@php
    $date = fn (?\DateTimeInterface $value): ?string => $value?->translatedFormat(__('formats.date'));
    $suffix = app()->getLocale() === 'ar' ? 'ar' : 'en';
    $manager = $this->directManager;
    $serviceYears = $canViewRecords ? $employee->yearsOfServiceOn() : 0;
    $skills = $canViewRecords ? $this->tags($employee->skills) : [];
    $interests = $canViewRecords ? $this->tags($employee->interests) : [];
@endphp

<div class="grid gap-5 @4xl/directory:grid-cols-[18rem_minmax(0,1fr)]">
    <x-ui.panel class="self-start">
        <div class="flex flex-col items-center gap-3 p-5 text-center">
            <x-ui.avatar :src="Employee::photoUrl($employee->id)" class="size-28 rounded-full" />
            <div class="min-w-0">
                <p class="truncate text-base font-bold {{ $ink }}" dir="auto">{{ $employee->name }}</p>
                <p class="truncate text-sm {{ $muted }}" dir="auto">{{ $position?->title ?? __('common.no_active_position') }}</p>
            </div>
            @if ($employee->is_active && $employee->isOnLeaveToday())
                <span class="rounded-md bg-status-warn-soft px-2 py-0.5 text-xs font-semibold text-status-warn" data-test="on-leave">{{ __('hr_employees.profile.on_leave_today') }}</span>
            @endif
        </div>
        <dl class="grid gap-3 border-t border-panel-border p-4">
            <x-hr::employees.fact :label="__('hr_employees.fields.work_email')" :value="$employee->account?->email" ltr />
            <x-hr::employees.fact :label="__('hr_employees.fields.work_phone')" :value="$employee->work_phone" ltr />
            @if (filled($employee->whatsapp_work))
                <x-hr::employees.fact :label="__('hr_employees.fields.whatsapp_work')" :value="$employee->whatsapp_work" ltr />
            @endif
            @if (filled($employee->telegram_work))
                <x-hr::employees.fact :label="__('hr_employees.fields.telegram_work')" :value="$employee->telegram_work" ltr />
            @endif
            @if ($this->canEditSection('work-contact'))
                <div>
                    <x-ui.button size="sm" icon="pencil-square" wire:click="openSection('work-contact')" data-test="edit-work-contact">{{ __('hr_employees.actions.edit_work_contact') }}</x-ui.button>
                </div>
            @endif
        </dl>
    </x-ui.panel>

    <div class="flex min-w-0 flex-col gap-5">
        <x-ui.panel :title="__('hr_employees.profile.work')">
            <dl class="grid gap-4 p-4 @2xl/directory:grid-cols-2 @5xl/directory:grid-cols-3">
                <x-hr::employees.fact :label="__('hr_employees.fields.position')" :value="$position?->title" />
                <x-hr::employees.fact :label="__('common.program')" :value="$position ? Program::nameOf($position->program_id) : null" />
                <x-hr::employees.fact :label="__('hr_employees.fields.department')" :value="$position?->department_id ? Department::nameOf((int) $position->department_id) : null" />
                <x-hr::employees.fact :label="__('hr_employees.fields.job_level')" :value="$position?->jobLevel?->name" />
                <x-hr::employees.fact :label="__('hr_employees.fields.center')" :value="$position ? Center::nameOf($position->center_id) : null" />
                <x-hr::employees.fact :label="__('common.city')" :value="City::nameOf($employee->city_id)" />
                <x-hr::employees.fact :label="__('hr_employees.fields.direct_manager')" :value="$manager?->name" />
                <x-hr::employees.fact :label="__('hr_employees.fields.joined_at')" :value="$date($employee->joined_at)" />
                @if ($canViewRecords)
                    <x-hr::employees.fact :label="__('hr_employees.fields.years_of_service')" :value="trans_choice('hr_employees.count_years', $serviceYears, ['count' => $serviceYears])" />
                @endif
            </dl>
        </x-ui.panel>

        @if ($canViewPrivate)
            <x-ui.panel :title="__('hr_employees.profile.personal')">
                <dl class="grid gap-4 p-4 @2xl/directory:grid-cols-2 @5xl/directory:grid-cols-3">
                    <x-hr::employees.fact :label="__('hr_employees.fields.full_name_ar')" :value="trim($employee->first_name_ar.' '.$employee->father_name_ar.' '.$employee->last_name_ar)" />
                    <x-hr::employees.fact :label="__('hr_employees.fields.full_name_en')" :value="trim($employee->first_name_en.' '.$employee->father_name_en.' '.$employee->last_name_en)" />
                    <x-hr::employees.fact :label="__('hr_employees.fields.mother_name')" :value="$employee->mother_name" />
                    <x-hr::employees.fact :label="__('hr_employees.fields.national_number')" :value="$employee->national_number" ltr />
                    <x-hr::employees.fact :label="__('hr_employees.fields.dob')" :value="$date($employee->dob)" />
                    <x-hr::employees.fact :label="__('hr_employees.fields.pob')" :value="$employee->{'pob_'.$suffix}" />
                    <x-hr::employees.fact :label="__('hr_employees.fields.gender')" :value="EnumLabel::of(Gender::class, $employee->gender)" />
                    <x-hr::employees.fact :label="__('hr_employees.fields.nationality')" :value="EnumLabel::of(Nationality::class, $employee->nationality)" />
                    <x-hr::employees.fact :label="__('hr_employees.fields.marital_status')" :value="EnumLabel::of(MaritalStatus::class, $employee->marital_status)" />
                    <x-hr::employees.fact :label="__('hr_employees.fields.children')" :value="$employee->children" />
                    <x-hr::employees.fact :label="__('hr_employees.fields.civil_registry_office')" :value="$employee->civil_registry_office" />
                    <x-hr::employees.fact :label="__('hr_employees.fields.civil_registry_number')" :value="$employee->civil_registry_number" ltr />
                </dl>
            </x-ui.panel>

            <x-ui.panel :title="__('hr_employees.profile.personal_contact')">
                <x-slot:toolbar>
                    @if ($this->canEditSection('personal-contact'))
                        <x-ui.button size="sm" icon="pencil-square" wire:click="openSection('personal-contact')" data-test="edit-personal-contact">{{ __('common.edit_name', ['name' => __('hr_employees.profile.personal_contact')]) }}</x-ui.button>
                    @endif
                </x-slot:toolbar>
                <dl class="grid gap-4 p-4 @2xl/directory:grid-cols-2">
                    <x-hr::employees.fact :label="__('hr_employees.fields.personal_phone')" :value="$employee->personal_phone" ltr />
                    <x-hr::employees.fact :label="__('hr_employees.fields.personal_email')" :value="$employee->personal_email" ltr />
                    <x-hr::employees.fact :label="__('hr_employees.fields.address_ar')" :value="$employee->address_ar" />
                    <x-hr::employees.fact :label="__('hr_employees.fields.address_en')" :value="$employee->address_en" />
                </dl>
            </x-ui.panel>
        @endif

        @if ($canViewRecords)
            <x-ui.panel :title="__('hr_employees.profile.skills')">
                <x-slot:toolbar>
                    @if ($this->canEditSection('skills'))
                        <x-ui.button size="sm" icon="pencil-square" wire:click="openSection('skills')" data-test="edit-skills">{{ __('common.edit_name', ['name' => __('hr_employees.profile.skills')]) }}</x-ui.button>
                    @endif
                </x-slot:toolbar>
                <div class="grid gap-4 p-4 @2xl/directory:grid-cols-2">
                    @foreach (['skills' => $skills, 'interests' => $interests] as $tagKey => $tagList)
                        <div>
                            <p class="type-label {{ $muted }}">{{ __('hr_employees.fields.'.$tagKey) }}</p>
                            <div class="mt-1.5 flex flex-wrap gap-1.5">
                                @forelse ($tagList as $tag)
                                    <span class="rounded-md bg-secondary-soft px-2 py-0.5 text-xs font-medium {{ $ink }} dark:bg-surface-2" dir="auto">{{ $tag }}</span>
                                @empty
                                    <span class="text-sm {{ $muted }}">—</span>
                                @endforelse
                            </div>
                        </div>
                    @endforeach
                </div>
            </x-ui.panel>
        @endif
    </div>
</div>
