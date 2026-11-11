{{-- The automatic CV reading. A reading aid, never a decision: every line shows what it was read from. Expects $analysis. --}}
@php
    $muted = 'text-secondary-medium dark:text-ink-muted';
    $facts = $analysis->facts_json ?? [];
    $comparison = $analysis->comparison_json ?? [];
    $pill = fn (string $outcome) => 'ms-1 inline-flex rounded-md px-2 py-0.5 text-xs font-semibold '.($outcome === 'unknown'
        ? 'bg-secondary-soft text-secondary-dark dark:bg-surface-3 dark:text-ink'
        : 'border border-panel-border');
    $years = $facts['experience']['years'] ?? null;
    $experience = $comparison['experience'] ?? ['outcome' => 'not_applicable'];
    $education = $comparison['education'] ?? ['outcome' => 'not_applicable'];
    $skills = $comparison['skills'] ?? ['outcome' => 'not_applicable', 'items' => []];
@endphp
<details class="mt-3 rounded-lg border border-panel-border p-3 text-sm" @if ($analysis->isCompleted()) open @endif>
    <summary class="cursor-pointer font-semibold">
        {{ __('hr_recruitment.applicant_ui.cv_analysis.title') }}
        <span class="font-normal {{ $muted }}">· {{ __('hr_recruitment.applicant_ui.cv_analysis.methods.'.($analysis->extraction_method ?? 'none')) }} · <span class="tabular-nums">{{ $analysis->analyzed_at?->format('Y-m-d H:i') }}</span></span>
    </summary>
    <p class="mt-2 text-xs {{ $muted }}">{{ __('hr_recruitment.applicant_ui.cv_analysis.disclaimer') }}</p>

    @if (! $analysis->isCompleted())
        <p class="mt-2">{{ __('hr_recruitment.applicant_ui.cv_analysis.statuses.'.$analysis->status) }}</p>
    @else
        <dl class="mt-3 grid gap-3 sm:grid-cols-2">
            <div>
                <dt class="text-xs {{ $muted }}">{{ __('hr_recruitment.applicant_ui.cv_analysis.experience') }}</dt>
                <dd>
                    {{ $years === null ? __('hr_recruitment.applicant_ui.cv_analysis.not_found') : trans_choice('hr_recruitment.applicant_ui.cv_analysis.years', $years, ['count' => $years]) }}
                    @if ($experience['outcome'] !== 'not_applicable')
                        <span class="{{ $pill($experience['outcome']) }}">{{ __('hr_recruitment.applicant_ui.cv_analysis.outcomes.'.$experience['outcome']) }} · {{ __('hr_recruitment.applicant_ui.cv_analysis.required_years', ['count' => $experience['required'] ?? 0]) }}</span>
                    @endif
                    @if (! empty($facts['experience']['ranges']))
                        <span class="block text-xs {{ $muted }}" dir="ltr">{{ collect($facts['experience']['ranges'])->map(fn ($range) => $range[0].'–'.$range[1])->implode(', ') }}</span>
                    @endif
                    @if (($facts['experience']['stated'] ?? null) !== null)
                        <span class="block text-xs {{ $muted }}">{{ __('hr_recruitment.applicant_ui.cv_analysis.stated', ['count' => $facts['experience']['stated']]) }}</span>
                    @endif
                </dd>
            </div>
            <div>
                <dt class="text-xs {{ $muted }}">{{ __('hr_recruitment.applicant_ui.cv_analysis.education') }}</dt>
                <dd>
                    @if ($facts['education'] ?? null)
                        {{ __('hr_recruitment.applicant_ui.indicators.levels.'.$facts['education']['level']) }} <span class="{{ $muted }}" dir="auto">(«{{ $facts['education']['keyword'] }}»)</span>
                    @else
                        {{ __('hr_recruitment.applicant_ui.cv_analysis.not_found') }}
                    @endif
                    @if ($education['outcome'] !== 'not_applicable')
                        <span class="{{ $pill($education['outcome']) }}">{{ __('hr_recruitment.applicant_ui.cv_analysis.outcomes.'.$education['outcome']) }}@isset($education['required']) · {{ __('hr_recruitment.applicant_ui.cv_analysis.required_level', ['level' => __('hr_recruitment.applicant_ui.indicators.levels.'.$education['required'])]) }}@endisset</span>
                    @endif
                </dd>
            </div>
            <div>
                <dt class="text-xs {{ $muted }}">{{ __('hr_recruitment.applicant_ui.cv_analysis.languages') }}</dt>
                <dd>{{ collect($facts['languages'] ?? [])->map(fn ($language) => __('hr_recruitment.applicant_ui.cv_analysis.language_names.'.$language))->implode(__('hr_recruitment.applicant_ui.list_separator')) ?: __('hr_recruitment.applicant_ui.cv_analysis.not_found') }}</dd>
            </div>
            <div>
                <dt class="text-xs {{ $muted }}">{{ __('hr_recruitment.applicant_ui.cv_analysis.skills_detected') }}</dt>
                <dd class="flex flex-wrap gap-1">
                    @forelse ($facts['skills'] ?? [] as $skill)
                        <span class="rounded-md bg-secondary-soft px-1.5 py-0.5 text-xs dark:bg-surface-3" dir="auto">{{ $skill }}</span>
                    @empty
                        {{ __('hr_recruitment.applicant_ui.cv_analysis.not_found') }}
                    @endforelse
                </dd>
            </div>
        </dl>

        @if ($skills['outcome'] === 'assessed')
            <div class="mt-3">
                <p class="font-medium">{{ __('hr_recruitment.applicant_ui.cv_analysis.required_skills', ['matched' => $skills['matched'], 'total' => $skills['total']]) }}</p>
                <ul class="mt-1 space-y-0.5">
                    @foreach ($skills['items'] as $item)
                        <li>
                            <span @class(['font-bold', $muted => ! $item['matched']])>{{ $item['matched'] ? '✓' : '—' }}</span>
                            <span dir="auto">{{ $item['term'] }}</span>
                            @if ($item['matched'] && $item['evidence'])
                                <span class="text-xs {{ $muted }}">({{ __('hr_recruitment.applicant_ui.cv_analysis.found_as', ['word' => $item['evidence']]) }})</span>
                            @elseif (! $item['matched'])
                                <span class="text-xs {{ $muted }}">({{ __('hr_recruitment.applicant_ui.cv_analysis.not_mentioned') }})</span>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    @endif
</details>
