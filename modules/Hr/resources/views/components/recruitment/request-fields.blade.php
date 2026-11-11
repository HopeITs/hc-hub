@props([
    'data' => [],
    'editing' => false,
    'positions',
    'vacancies',
    'cities',
    'programs',
    'centers',
    'centerRequired' => true,
    'departments',
    'jobLevels',
    'contractTypes',
    'lockedFields' => [],
    'requestedFields' => [],
    'form' => [],
])

{{--
    The request form itself, shared by the new-request, edit and direct-open
    pages. Sections appear only for the chosen type, in the order a requester
    thinks about it: what (overview, and the new post when one is proposed),
    why and when, how (arrangement and contract) and for how much. Closing a
    post or a vacancy needs only a reason and a date.

    Every list is narrowed by the page; that is a convenience, never the
    guard: the draft service re-derives the same sets and refuses the rest.
--}}
@php
    $Type = \Modules\Hr\Enums\RecruitmentRequestType::class;
    $type = (int) ($data['request_type'] ?? 0);
    $newPosition = $type === $Type::CreatePosition->value;
    $hires = in_array($type, [$Type::FillVacancy->value, $Type::IncreaseHeadcount->value, $Type::CreatePosition->value], true);
    $closes = in_array($type, [$Type::DecreaseHeadcount->value, $Type::ClosePosition->value, $Type::CloseVacancy->value], true);
    $isFill = $type === $Type::FillVacancy->value;

    // Decrease headcount is not offered for new requests.
    $types = collect([$Type::FillVacancy, $Type::IncreaseHeadcount, $Type::CreatePosition, $Type::ClosePosition, $Type::CloseVacancy])
        ->mapWithKeys(fn ($case) => [$case->value => \Modules\Hr\Support\Recruitment\Labels::enum('RecruitmentRequestType', $case->value)])->all();

    $targetField = $newPosition ? 'direct_manager_id' : 'position_id';
    $selectedPosition = $positions->firstWhere('id', (int) ($data[$targetField] ?? 0));

    $availableSeats = null;
    if ($newPosition) {
        $availableSeats = max(0, (int) ($data['max_emp'] ?? 0));
    } elseif ($selectedPosition && $isFill) {
        $availableSeats = $vacancies->isNotEmpty()
            ? $vacancies->sum(fn ($vacancy) => max(0, (int) $vacancy->needed_count - (int) $vacancy->filled_count))
            : max(0, (int) $selectedPosition->max_emp - (int) $selectedPosition->exist_emp);
    }

    $choices = fn (string $group, string $prefix) => collect($form[$group] ?? [])
        ->map(fn ($value) => ['value' => $value, 'label' => __('hr_recruitment.'.$prefix.$value)])->values()->all();

    // Earliest dates the service accepts.
    $tomorrow = now()->addDay()->toDateString();
    $validDate = fn ($value) => is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ? $value : null;
    $startMin = $validDate($data['needed_by_date'] ?? null) ?? $tomorrow;
    $endMin = $validDate($data['volunteer_period_start_date'] ?? null) ?? $startMin;

    $locked = fn (string $field): bool => in_array($field, $lockedFields, true);
    $lockedReason = __('hr_recruitment.hub.field_locked');
    $asked = fn (string $field): bool => in_array($field, $requestedFields, true) && ! in_array($field, $lockedFields, true);
    $muted = 'text-secondary-medium dark:text-ink-muted';
    $grid = 'grid gap-x-6 gap-y-5 p-4 md:grid-cols-2';
    $askedRing = 'rounded-md border-s-2 border-primary ps-3 dark:border-brand';
@endphp

<div class="space-y-6">
    {{-- Overview --}}
    <x-ui.panel :title="__('hr_recruitment.form_ui.overview')" :subtitle="__('hr_recruitment.form_ui.overview_lead')" :clip="false">
        <div class="space-y-5 p-4">
            @if ($editing)
                <div>
                    <p class="text-sm font-semibold">{{ __('hr_recruitment.type') }}</p>
                    <div class="mt-1.5 rounded-md bg-secondary-soft/50 p-3 dark:bg-surface-2">
                        <p class="font-semibold">{{ \Modules\Hr\Support\Recruitment\Labels::enum('RecruitmentRequestType', $type) }}</p>
                        @if (array_key_exists($type, $types))
                            <p class="text-sm {{ $muted }}">{{ __('hr_recruitment.form_ui.type_desc_'.$type) }}</p>
                        @endif
                    </div>
                    <p class="mt-1 text-xs {{ $muted }}">{{ __('hr_recruitment.form_ui.type_fixed') }}</p>
                </div>
            @else
                <fieldset class="min-w-0">
                    <legend class="mb-1.5 text-sm font-semibold">{{ __('hr_recruitment.type') }} <span class="text-status-critical" aria-hidden="true">*</span></legend>
                    <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($types as $value => $name)
                            <label wire:key="type-{{ $value }}" class="min-w-0 cursor-pointer">
                                <input type="radio" class="peer sr-only" name="data_request_type" value="{{ $value }}" wire:model.live="data.request_type" @checked($type === $value)>
                                <span class="flex h-full flex-col rounded-md border border-panel-border p-3 transition peer-checked:border-primary peer-checked:bg-secondary-soft/60 peer-focus-visible:ring-2 peer-focus-visible:ring-primary dark:peer-checked:border-brand dark:peer-checked:bg-surface-2">
                                    <span class="font-semibold">{{ $name }}</span>
                                    <span class="mt-0.5 text-sm {{ $muted }}">{{ __('hr_recruitment.form_ui.type_desc_'.$value) }}</span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                    @error('data.request_type')<p class="mt-1 text-sm text-status-critical">{{ $message }}</p>@enderror
                </fieldset>
            @endif

            <div class="grid gap-x-6 gap-y-5 md:grid-cols-2">
                <div class="min-w-0 md:col-span-2" wire:key="target-{{ $targetField }}">
                    <x-ui.form-select wire:model.live="data.{{ $targetField }}" :label="__('hr_recruitment.'.($newPosition ? 'parent_position' : 'position'))" :placeholder="__('hr_recruitment.hub.select')" required
                        :help="__($newPosition ? 'hr_recruitment.parent_help' : 'hr_recruitment.target_help')" :disabled="$locked($targetField)" :locked-reason="$locked($targetField) ? $lockedReason : null" wrap-labels>
                        @foreach ($positions as $position)
                            <option value="{{ $position->id }}">{{ $position->title }} · {{ $position->program?->name }} · {{ $position->city?->name }}</option>
                        @endforeach
                    </x-ui.form-select>
                    @if ($positions->isEmpty())
                        <p role="status" class="mt-2 text-sm {{ $muted }}">{{ __('hr_recruitment.no_targets') }}</p>
                    @endif
                    @if ($selectedPosition)
                        <p class="mt-3 flex flex-wrap items-center gap-x-2 gap-y-1 rounded-md bg-secondary-soft/50 px-3 py-2 text-sm dark:bg-surface-2" data-position-summary>
                            <span class="font-semibold">{{ $selectedPosition->title }}</span>
                            @if ($selectedPosition->program?->name)<span aria-hidden="true">·</span><span>{{ $selectedPosition->program->name }}</span>@endif
                            @if ($selectedPosition->city?->name)<span aria-hidden="true">·</span><span>{{ $selectedPosition->city->name }}</span>@endif
                            @if ($isFill && $availableSeats !== null)
                                <span aria-hidden="true">·</span><span class="tabular-nums">{{ __('hr_recruitment.form_ui.seats_open', ['count' => $availableSeats]) }}</span>
                            @endif
                        </p>
                    @endif
                </div>

                @if ($isFill && $vacancies->count() > 1)
                    <div class="min-w-0 md:col-span-2">
                        <x-ui.form-select wire:model="data.vacancy_id" :label="__('hr_recruitment.vacancy')" :placeholder="__('hr_recruitment.hub.select')" :help="__('hr_recruitment.form_ui.vacancy_help')">
                            @foreach ($vacancies as $vacancy)
                                <option value="{{ $vacancy->id }}">{{ __('hr_recruitment.vacancy') }} #{{ $vacancy->id }} · {{ __('hr_recruitment.remaining_slots', ['count' => $vacancy->needed_count - $vacancy->filled_count]) }}</option>
                            @endforeach
                        </x-ui.form-select>
                    </div>
                @endif
            </div>
        </div>
    </x-ui.panel>

    @if ($newPosition)
        <x-ui.panel :title="__('hr_recruitment.proposed_position')" :subtitle="__('hr_recruitment.form_ui.proposed_lead')" :clip="false">
            <div class="{{ $grid }}">
                <x-ui.form-input wire:model="data.title_en" :label="__('hr_recruitment.title_en')" required dir="ltr" :disabled="$locked('title_en')" />
                <x-ui.form-input wire:model="data.title_ar" :label="__('hr_recruitment.title_ar')" required dir="rtl" :disabled="$locked('title_ar')" />
                <x-ui.form-select wire:model.live="data.city_id" :label="__('hr_recruitment.city')" :placeholder="__('hr_recruitment.hub.select')" required :disabled="$locked('city_id')" :locked-reason="$locked('city_id') ? $lockedReason : null">
                    @foreach ($cities as $city)<option value="{{ $city->id }}">{{ $city->name }}</option>@endforeach
                </x-ui.form-select>
                <x-ui.form-select wire:model.live="data.program_id" :label="__('hr_recruitment.program')" :placeholder="__('hr_recruitment.hub.select')" required :disabled="$locked('program_id')" :locked-reason="$locked('program_id') ? $lockedReason : null">
                    @foreach ($programs as $program)<option value="{{ $program->id }}">{{ $program->name }}</option>@endforeach
                </x-ui.form-select>
                <x-ui.form-select wire:model="data.center_id" :label="__('hr_recruitment.center')" :placeholder="__('hr_recruitment.hub.select')" :required="$centerRequired" :help="$centerRequired ? null : __('hr_recruitment.center_optional_help')" :disabled="$locked('center_id')" :locked-reason="$locked('center_id') ? $lockedReason : null" wire:key="centers-{{ $data['city_id'] ?? 0 }}">
                    @foreach ($centers as $center)<option value="{{ $center->id }}">{{ $center->name }}</option>@endforeach
                </x-ui.form-select>
                <x-ui.form-select wire:model="data.department_id" :label="__('hr_recruitment.department')" :placeholder="__('hr_recruitment.hub.select')" required :disabled="$locked('department_id')" :locked-reason="$locked('department_id') ? $lockedReason : null">
                    @foreach ($departments as $department)<option value="{{ $department->id }}">{{ $department->name }}</option>@endforeach
                </x-ui.form-select>
                <x-ui.form-select wire:model="data.job_level_id" :label="__('hr_recruitment.job_level')" :placeholder="__('hr_recruitment.hub.select')" required :disabled="$locked('job_level_id')" :locked-reason="$locked('job_level_id') ? $lockedReason : null">
                    @foreach ($jobLevels as $jobLevel)<option value="{{ $jobLevel->id }}">{{ $jobLevel->name }}</option>@endforeach
                </x-ui.form-select>
                <x-ui.form-input wire:model.live.debounce.500ms="data.max_emp" :label="__('hr_recruitment.max_emp')" type="number" min="1" step="1" inputmode="numeric" required :help="__('hr_recruitment.form_ui.max_emp_help')" :disabled="$locked('max_emp')" />
                <div class="min-w-0 md:col-span-2"><x-ui.form-textarea wire:model="data.qualifications" :label="__('hr_recruitment.qualifications')" rows="4" required dir="auto" :disabled="$locked('qualifications')" /></div>
                <div class="min-w-0 md:col-span-2"><x-ui.form-textarea wire:model="data.responsibilities" :label="__('hr_recruitment.responsibilities')" rows="4" required dir="auto" :disabled="$locked('responsibilities')" /></div>
            </div>
        </x-ui.panel>
    @endif

    @if ($hires)
        <x-ui.panel :title="__('hr_recruitment.form_ui.requirements')" :subtitle="__('hr_recruitment.form_ui.requirements_lead')" :clip="false">
            <div class="{{ $grid }}">
                <div @class([$askedRing => $asked('recruitment_reason')])>
                    <x-ui.form-select wire:model.live="data.recruitment_reason" :label="__('hr_recruitment.recruitment_reason')" :placeholder="__('hr_recruitment.hub.select')" required :disabled="$locked('recruitment_reason')" :locked-reason="$locked('recruitment_reason') ? $lockedReason : null">
                        @foreach ($choices('recruitment_reasons', 'reason_') as $choice)<option value="{{ $choice['value'] }}">{{ $choice['label'] }}</option>@endforeach
                    </x-ui.form-select>
                </div>
                <div @class([$askedRing => $asked('priority')])>
                    <x-ui.form-segmented wire:model="data.priority" :label="__('hr_recruitment.priority')" :options="$choices('priorities', 'priority_')" required :disabled="$locked('priority')" />
                </div>

                @if (($data['recruitment_reason'] ?? null) === 'other')
                    <div @class(['min-w-0 md:col-span-2', $askedRing => $asked('other_reason')])>
                        <x-ui.form-textarea wire:model="data.other_reason" :label="__('hr_recruitment.other_reason')" rows="2" required dir="auto" :disabled="$locked('other_reason')" />
                    </div>
                @endif

                <div @class([$askedRing => $asked('needed_by_date')])>
                    <x-ui.form-input wire:model.blur="data.needed_by_date" :label="__('hr_recruitment.needed_by_date')" type="date" :min="$tomorrow" required :help="__('hr_recruitment.form_ui.needed_by_help')" :disabled="$locked('needed_by_date')" />
                </div>

                @if ($type === $Type::IncreaseHeadcount->value)
                    <div @class([$askedRing => $asked('additional_count')])>
                        <x-ui.form-input wire:model="data.additional_count" :label="__('hr_recruitment.additional_count')" type="number" min="1" step="1" inputmode="numeric" required :help="__('hr_recruitment.form_ui.additional_count_help')" :disabled="$locked('additional_count')" />
                    </div>
                @else
                    <div @class([$askedRing => $asked('volunteers_required')])>
                        <x-ui.form-input wire:model="data.volunteers_required" :label="__('hr_recruitment.volunteers_required')" type="number" min="1" step="1" inputmode="numeric" :max="$availableSeats ?: null" required
                            :help="$isFill && $availableSeats !== null ? __('hr_recruitment.volunteers_required_help', ['count' => $availableSeats]) : __('hr_recruitment.form_ui.volunteers_required_new_help')" :disabled="$locked('volunteers_required')" />
                    </div>
                @endif

                <div @class(['min-w-0 md:col-span-2', $askedRing => $asked('detailed_explanation')])>
                    <x-ui.form-textarea wire:model="data.detailed_explanation" :label="__('hr_recruitment.detailed_explanation')" rows="5" required dir="auto" :placeholder="__('hr_recruitment.form_ui.justification_placeholder')" :help="__('hr_recruitment.detailed_explanation_help')" :disabled="$locked('detailed_explanation')" />
                </div>
            </div>
        </x-ui.panel>

        <x-ui.panel :title="__('hr_recruitment.form_ui.arrangement')" :subtitle="__('hr_recruitment.form_ui.arrangement_lead')" :clip="false">
            <div class="{{ $grid }}">
                <div @class([$askedRing => $asked('volunteer_type')])>
                    <x-ui.form-segmented wire:model.live="data.volunteer_type" :label="__('hr_recruitment.volunteer_type')" :options="$choices('volunteer_types', 'volunteer_type_')" required :disabled="$locked('volunteer_type')" />
                </div>
                <div @class([$askedRing => $asked('weekly_hours')])>
                    <x-ui.form-input wire:model="data.weekly_hours" :label="__('hr_recruitment.weekly_hours')" type="number" min="1" max="168" step="1" inputmode="numeric" required :disabled="$locked('weekly_hours')" />
                </div>
                <div @class([$askedRing => $asked('volunteer_period_start_date')])>
                    <x-ui.form-input wire:model.blur="data.volunteer_period_start_date" :label="__('hr_recruitment.volunteer_period_start_date')" type="date" :min="$startMin" required :help="__('hr_recruitment.form_ui.period_start_help')" :disabled="$locked('volunteer_period_start_date')" />
                </div>
                <div @class([$askedRing => $asked('volunteer_period_end_date')])>
                    <x-ui.form-input wire:model="data.volunteer_period_end_date" :label="__('hr_recruitment.volunteer_period_end_date')" type="date" :min="$endMin" required :help="__('hr_recruitment.form_ui.period_end_help')" :disabled="$locked('volunteer_period_end_date')" />
                </div>
                <div @class([$askedRing => $asked('contract_type_id')])>
                    <x-ui.form-select wire:model="data.contract_type_id" :label="__('hr_recruitment.contract_type')" :placeholder="__('hr_recruitment.hub.select')" required :disabled="$locked('contract_type_id')" :locked-reason="$locked('contract_type_id') ? $lockedReason : null">
                        @foreach ($contractTypes as $contractType)<option value="{{ $contractType->id }}">{{ $contractType->name }}</option>@endforeach
                    </x-ui.form-select>
                </div>
            </div>
        </x-ui.panel>

        {{-- The amount, its currency and what it is paid per are one figure. --}}
        <x-ui.panel :title="__('hr_recruitment.form_ui.compensation')" :subtitle="__('hr_recruitment.form_ui.compensation_lead')" :clip="false">
            <div class="{{ $grid }}">
                <div @class([$askedRing => $asked('salary_amount') || $asked('currency')])>
                    <x-ui.form-input wire:model="data.salary_amount" :label="__('hr_recruitment.salary_amount')" type="number" min="0" step="1" inputmode="numeric" required dir="ltr" :help="__('hr_recruitment.form_ui.salary_help')" :disabled="$locked('salary_amount')" />
                </div>
                <div @class([$askedRing => $asked('currency')])>
                    <x-ui.form-segmented wire:model="data.currency" :label="__('hr_recruitment.currency')" required :disabled="$locked('currency')"
                        :options="collect([1, 2, 3])->map(fn ($value) => ['value' => $value, 'label' => \Modules\Hr\Enums\Currency::getLocalizedName($value)[app()->getLocale()]])->all()" />
                </div>
                <div @class([$askedRing => $asked('salary_per')])>
                    <x-ui.form-segmented wire:model="data.salary_per" :label="__('hr_recruitment.salary_per')" required :disabled="$locked('salary_per')"
                        :options="collect([1, 2, 3])->map(fn ($value) => ['value' => $value, 'label' => \Modules\Hr\Enums\SalaryPer::getLocalizedName($value)['value']])->all()" />
                </div>
            </div>
        </x-ui.panel>
    @endif

    @if ($closes)
        <x-ui.panel :title="__('hr_recruitment.form_ui.closure')" :subtitle="__('hr_recruitment.form_ui.closure_lead')" :clip="false">
            <div class="{{ $grid }}">
                @if ($type === $Type::DecreaseHeadcount->value)
                    <x-ui.form-input wire:model="data.reduce_by" :label="__('hr_recruitment.reduce_by')" type="number" min="1" step="1" inputmode="numeric" required :disabled="$locked('reduce_by')" />
                @endif
                <x-ui.form-input wire:model="data.effective_date" :label="__('hr_recruitment.effective_date')" type="date" required :help="__('hr_recruitment.form_ui.effective_date_help')" :disabled="$locked('effective_date')" />
                <div class="min-w-0 md:col-span-2">
                    <x-ui.form-textarea wire:model="data.reason" :label="__('hr_recruitment.reason')" rows="4" required dir="auto" :placeholder="__('hr_recruitment.form_ui.closure_reason_placeholder')" :disabled="$locked('reason')" />
                </div>
            </div>
        </x-ui.panel>
    @endif
</div>
