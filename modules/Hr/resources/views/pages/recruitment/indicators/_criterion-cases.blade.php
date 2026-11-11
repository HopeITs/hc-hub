{{-- The criterion's own truth table: every case it can produce. --}}
<ul class="flex flex-wrap gap-1.5 {{ $class ?? '' }}" aria-label="{{ __('hr_recruitment.indicator_rules.cases_label', ['name' => $name]) }}">
    @if (in_array($key, ['experience', 'qualification'], true))
        <li><a href="#{{ $key }}-title" class="inline-flex min-h-11 items-center gap-1.5 rounded-control border border-panel-border bg-white dark:bg-surface-1 px-3 text-small font-semibold text-secondary-dark shadow-e1 hover:bg-selection sm:min-h-8 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary">{{ __('hr_recruitment.indicator_rules.see_table') }}<flux:icon.arrow-down variant="micro" class="size-3.5" aria-hidden="true" /></a></li>
    @else
        @foreach ($cases as [$outcome, $case])
            <li @class(['inline-flex items-center gap-1 rounded-control px-2 py-0.5 text-caption font-semibold', ($tones[$outcome] ?? \Modules\Hr\Enums\RecruitmentIndicatorOutcome::classes($outcome))])>
                @include('hr::pages.recruitment.indicators._outcome-icon', ['outcome' => $outcome])
                <span class="sr-only">{{ __('hr_recruitment.applicant_ui.indicators.outcomes.'.$outcome) }}:</span>
                {{ __('hr_recruitment.indicator_rules.cases.'.$key.'.'.$case) }}
            </li>
        @endforeach
    @endif
</ul>
