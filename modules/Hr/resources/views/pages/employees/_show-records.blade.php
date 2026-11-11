{{--
    The Records tab of an employee profile: education, past jobs and courses.
    Included by ⚡show.blade.php. HR in the employee's city adds, edits and
    removes rows through the page's record dialog (ManagesEmployeeRecords).
--}}
@use('Modules\Hr\Enums\ContractPlace')
@use('Modules\Hr\Enums\ContractType')
@use('Modules\Hr\Enums\EducationStatus')
@use('Modules\Hr\Enums\EducationType')
@use('Modules\Hr\Support\Employees\EnumLabel')

@php
    $suffix = app()->getLocale() === 'ar' ? 'ar' : 'en';
    $date = fn (?\DateTimeInterface $value): string => $value?->translatedFormat(__('formats.date')) ?? '—';
    $files = $this->recordFiles;
@endphp

<div class="flex flex-col gap-5">
    <x-ui.panel :title="__('hr_employees.records.education')" :count="$this->educations->count()">
        <x-slot:toolbar>
            @if ($canEdit)
                <x-ui.button size="sm" icon="plus" wire:click="openRecord('education')" data-test="add-education">{{ __('common.add') }}</x-ui.button>
            @endif
        </x-slot:toolbar>
        @if ($this->educations->isEmpty())
            <p class="p-4 text-sm {{ $muted }}">{{ __('hr_employees.records.none') }}</p>
        @else
            <ul class="divide-y divide-panel-border">
                @foreach ($this->educations as $education)
                    <li class="flex flex-wrap items-center gap-3 px-4 py-3" wire:key="education-{{ $education->id }}">
                        <div class="min-w-0 flex-1">
                            <p class="font-semibold {{ $ink }}" dir="auto">{{ $education->name }}@if (filled($education->specialization)) · {{ $education->specialization }}@endif</p>
                            <p class="text-xs {{ $muted }}">{{ EnumLabel::of(EducationType::class, $education->degree) }} · {{ EnumLabel::of(EducationStatus::class, $education->status) }}</p>
                            @if (filled($education->notes))
                                <p class="mt-1 text-sm {{ $muted }}" dir="auto">{{ $education->notes }}</p>
                            @endif
                        </div>
                        <x-hr::employees.record-actions kind="education" :id="$education->id" :file="$files['education:'.$education->id] ?? null" :editable="$canEdit" :name="$education->name" />
                    </li>
                @endforeach
            </ul>
        @endif
    </x-ui.panel>

    <x-ui.panel :title="__('hr_employees.records.experience')" :count="$this->experiences->count()">
        <x-slot:toolbar>
            @if ($canEdit)
                <x-ui.button size="sm" icon="plus" wire:click="openRecord('experience')" data-test="add-experience">{{ __('common.add') }}</x-ui.button>
            @endif
        </x-slot:toolbar>
        @if ($this->experiences->isEmpty())
            <p class="p-4 text-sm {{ $muted }}">{{ __('hr_employees.records.none') }}</p>
        @else
            <ul class="divide-y divide-panel-border">
                @foreach ($this->experiences as $experience)
                    <li class="flex flex-wrap items-center gap-3 px-4 py-3" wire:key="experience-{{ $experience->id }}">
                        <div class="min-w-0 flex-1">
                            <p class="font-semibold {{ $ink }}" dir="auto">{{ $experience->job_title }} · {{ $experience->company }}</p>
                            <p class="text-xs {{ $muted }}">
                                {{ $date($experience->start_date) }} – {{ $experience->end_date ? $date($experience->end_date) : __('hr_employees.records.present') }}
                                · {{ EnumLabel::of(ContractPlace::class, $experience->place) }} · {{ EnumLabel::of(ContractType::class, $experience->type) }}
                            </p>
                        </div>
                        <x-hr::employees.record-actions kind="experience" :id="$experience->id" :file="$files['experience:'.$experience->id] ?? null" :editable="$canEdit" :name="$experience->job_title" />
                    </li>
                @endforeach
            </ul>
        @endif
    </x-ui.panel>

    <x-ui.panel :title="__('hr_employees.records.courses')" :count="$this->courses->count()">
        <x-slot:toolbar>
            @if ($canEdit)
                <x-ui.button size="sm" icon="plus" wire:click="openRecord('course')" data-test="add-course">{{ __('common.add') }}</x-ui.button>
            @endif
        </x-slot:toolbar>
        @if ($this->courses->isEmpty())
            <p class="p-4 text-sm {{ $muted }}">{{ __('hr_employees.records.none') }}</p>
        @else
            <ul class="divide-y divide-panel-border">
                @foreach ($this->courses as $course)
                    <li class="flex flex-wrap items-center gap-3 px-4 py-3" wire:key="course-{{ $course->id }}">
                        <div class="min-w-0 flex-1">
                            <p class="font-semibold {{ $ink }}" dir="auto">{{ $course->title }}</p>
                            <p class="text-xs {{ $muted }}">
                                {{ __('hr_employees.records.organizations.'.\Illuminate\Support\Str::snake($course->organization ?: 'Other')) }}
                                @if ($course->category !== null) · {{ $course->category->{'name_'.$suffix} }} @endif
                                @if ($course->end_date !== null) · {{ $date($course->end_date) }} @endif
                                @if (filled($course->duration)) · {{ $course->duration }} @endif
                                · {{ __('hr_employees.records.course_status.'.(int) $course->status) }}
                                @if ($course->has_certificate) · {{ __('hr_employees.records.fields.has_certificate') }} @endif
                            </p>
                        </div>
                        <x-hr::employees.record-actions kind="course" :id="$course->id" :file="$files['course:'.$course->id] ?? null" :editable="$canEdit" :name="$course->title" />
                    </li>
                @endforeach
            </ul>
        @endif
    </x-ui.panel>
</div>
