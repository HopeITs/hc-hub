<x-slot:title>{{ __('recruitment.create') }}</x-slot:title>

<div class="mx-auto max-w-5xl space-y-6">
    <a class="text-primary hover:underline" href="{{ route('recruitment.requests.index') }}">{{ __('recruitment.back') }}</a>
    <header>
        <h1 class="text-h2 font-semibold text-neutral-900">{{ __('recruitment.create') }}</h1>
        <p class="mt-2 text-secondary-medium">{{ __('recruitment.create_description') }}</p>
    </header>
    <form wire:submit="save" class="space-y-6">
        @if ($errors->any())
            <div role="alert" class="rounded-control border border-red-200 bg-red-50 p-4 text-red-800">
                <ul class="list-inside list-disc">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif
        <section class="space-y-5 rounded-surface border border-neutral-200 bg-white p-6">
            <h2 class="text-h4 font-semibold">{{ __('recruitment.request_scope') }}</h2>
            @php
                $type = (int) ($data['request_type'] ?? 0);
                $newPosition = $type === \App\Enums\RecruitmentRequestType::CreatePosition;
                $types = collect(\App\Enums\RecruitmentRequestType::getValues())->reject(fn ($value) => $value === 0)
                    ->mapWithKeys(fn ($value) => [$value => \App\Enums\RecruitmentRequestType::getLocalizedName($value)['value']])->all();
                $positionOptions = $positions->mapWithKeys(fn ($position) => [$position->id => $position->title.' · '.$position->program?->name.' · '.$position->city?->name])->all();
            @endphp
            @include('livewire.recruitment.requests.partials.select', ['field' => 'request_type', 'label' => 'type', 'options' => $types, 'required' => true])
            @include('livewire.recruitment.requests.partials.select', ['field' => $newPosition ? 'direct_manager_id' : 'position_id', 'label' => $newPosition ? 'parent_position' : 'position', 'options' => $positionOptions, 'required' => true])
            <p class="text-sm text-secondary-medium">{{ __($newPosition ? 'recruitment.parent_help' : 'recruitment.target_help') }}</p>
            @if ($positions->isEmpty())<p role="status" class="text-sm text-primary">{{ __('recruitment.no_targets') }}</p>@endif
            @if ($type === \App\Enums\RecruitmentRequestType::CloseVacancy || ($type === \App\Enums\RecruitmentRequestType::FillVacancy && $vacancies->count() > 1))
                @include('livewire.recruitment.requests.partials.select', ['field' => 'vacancy_id', 'label' => 'vacancy', 'options' => $vacancies->mapWithKeys(fn ($vacancy) => [$vacancy->id => __('recruitment.vacancy').' #'.$vacancy->id.' · '.__('recruitment.remaining_slots', ['count' => $vacancy->needed_count - $vacancy->filled_count])])->all(), 'required' => true])
            @endif
        </section>
        @if ($newPosition)
            <section class="space-y-5 rounded-surface border border-neutral-200 bg-white p-6">
                <h2 class="text-h4 font-semibold">{{ __('recruitment.proposed_position') }}</h2>
                <div class="grid gap-5 sm:grid-cols-2">
                    @include('livewire.recruitment.requests.partials.select', ['field' => 'city_id', 'label' => 'city', 'options' => $cities->pluck('name', 'id')->all(), 'required' => true])
                    @include('livewire.recruitment.requests.partials.select', ['field' => 'program_id', 'label' => 'program', 'options' => $programs->pluck('name', 'id')->all(), 'required' => true])
                    @include('livewire.recruitment.requests.partials.select', ['field' => 'center_id', 'label' => 'center', 'options' => $centers->pluck('name', 'id')->all(), 'required' => $centerRequired])
                    @unless ($centerRequired)<p class="text-sm text-secondary-medium sm:col-span-2">{{ __('recruitment.center_optional_help') }}</p>@endunless
                    @include('livewire.recruitment.requests.partials.select', ['field' => 'department_id', 'label' => 'department', 'options' => $departments->pluck('name', 'id')->all(), 'required' => true])
                    @include('livewire.recruitment.requests.partials.select', ['field' => 'job_level_id', 'label' => 'job_level', 'options' => $jobLevels->pluck('name', 'id')->all(), 'required' => true])
                    @foreach (['title_en' => 'text', 'title_ar' => 'text', 'max_emp' => 'number'] as $field => $inputType)
                        @include('livewire.recruitment.requests.partials.input')
                    @endforeach
                </div>
                @foreach (['qualifications', 'responsibilities'] as $field)
                    @include('livewire.recruitment.requests.partials.textarea')
                @endforeach
            </section>
        @endif
        <section class="space-y-5 rounded-surface border border-neutral-200 bg-white p-6">
            <h2 class="text-h4 font-semibold">{{ __('recruitment.request_content') }}</h2>
            @if (in_array($type, [1, 2, 4], true))
                @php
                    $form = config('recruitment.request_form');
                    $options = fn (string $group, string $prefix) => collect($form[$group])
                        ->mapWithKeys(fn ($value) => [$value => __('recruitment.'.$prefix.$value)])->all();
                @endphp
                @include('livewire.recruitment.requests.partials.select', ['field' => 'recruitment_reason', 'label' => 'recruitment_reason', 'options' => $options('recruitment_reasons', 'reason_'), 'required' => true])
                @if (($data['recruitment_reason'] ?? null) === 'other')
                    @include('livewire.recruitment.requests.partials.textarea', ['field' => 'other_reason'])
                @endif
                <div class="grid gap-5 sm:grid-cols-2">
                    @include('livewire.recruitment.requests.partials.select', ['field' => 'priority', 'label' => 'priority', 'options' => $options('priorities', 'priority_'), 'required' => true])
                    @include('livewire.recruitment.requests.partials.select', ['field' => 'volunteer_type', 'label' => 'volunteer_type', 'options' => $options('volunteer_types', 'volunteer_type_'), 'required' => true])
                    @include('livewire.recruitment.requests.partials.input', ['field' => 'weekly_hours', 'inputType' => 'number'])
                    @include('livewire.recruitment.requests.partials.input', ['field' => 'needed_by_date', 'inputType' => 'date'])
                    @include('livewire.recruitment.requests.partials.input', ['field' => 'volunteer_period_start_date', 'inputType' => 'date'])
                    @include('livewire.recruitment.requests.partials.input', ['field' => 'volunteer_period_end_date', 'inputType' => 'date'])
                </div>
            @endif
            @if ($type === 2)
                @include('livewire.recruitment.requests.partials.input', ['field' => 'additional_count', 'inputType' => 'number'])
            @elseif ($type === 3)
                @include('livewire.recruitment.requests.partials.input', ['field' => 'reduce_by', 'inputType' => 'number'])
            @endif
            @if (in_array($type, [3, 5, 6], true))
                @include('livewire.recruitment.requests.partials.textarea', ['field' => 'reason'])
            @endif
            @if (in_array($type, [3, 5], true))
                @include('livewire.recruitment.requests.partials.input', ['field' => 'effective_date', 'inputType' => 'date'])
            @endif
            @if (in_array($type, [1, 2, 4], true))
                <div class="grid gap-5 sm:grid-cols-2">
                    @include('livewire.recruitment.requests.partials.input', ['field' => 'salary_amount', 'inputType' => 'number'])
                    @include('livewire.recruitment.requests.partials.select', ['field' => 'currency', 'label' => 'currency', 'options' => collect([1, 2, 3])->mapWithKeys(fn ($value) => [$value => \App\Enums\Currency::getLocalizedName($value)[app()->getLocale()]])->all(), 'required' => true])
                    @include('livewire.recruitment.requests.partials.select', ['field' => 'salary_per', 'label' => 'salary_per', 'options' => collect([1, 2, 3])->mapWithKeys(fn ($value) => [$value => \App\Enums\SalaryPer::getLocalizedName($value)['value']])->all(), 'required' => true])
                    @include('livewire.recruitment.requests.partials.select', ['field' => 'contract_type_id', 'label' => 'contract_type', 'options' => $contractTypes->pluck('name', 'id')->all(), 'required' => true])
                </div>
                @include('livewire.recruitment.requests.partials.textarea', ['field' => 'detailed_explanation'])
            @endif
        </section>
        <footer class="flex flex-wrap items-center justify-between gap-4 rounded-surface border border-neutral-200 bg-white p-5">
            <p class="text-sm text-secondary-medium">{{ __('recruitment.draft_help') }}</p>
            <x-primary-button type="submit" wire:target="save">{{ __('recruitment.save_draft') }}</x-primary-button>
        </footer>
    </form>
</div>
