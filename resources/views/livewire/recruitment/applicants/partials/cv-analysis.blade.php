{{-- A reading aid, never a decision: every line shows what it was read from. --}}
@php
    $facts = $analysis->facts_json ?? [];
    $comparison = $analysis->comparison_json ?? [];
    $outcomeClasses = [
        'met' => 'bg-green-50 text-green-800',
        'not_met' => 'bg-red-50 text-red-800',
        'unknown' => 'bg-neutral-100 text-secondary-medium',
    ];
@endphp

<details class="mt-4 rounded-control border border-neutral-200 p-4 text-sm" @if ($analysis->isCompleted()) open @endif>
    <summary class="cursor-pointer font-semibold">
        {{ __('recruitment-applicant.cv_analysis.title') }}
        <span class="ms-1 text-xs font-normal text-secondary-medium">
            &middot; {{ __('recruitment-applicant.cv_analysis.methods.'.($analysis->extraction_method ?? 'none')) }}
            &middot; {{ $analysis->analyzed_at?->format('Y-m-d H:i') }}
        </span>
    </summary>

    <p class="mt-2 text-xs text-secondary-medium">{{ __('recruitment-applicant.cv_analysis.disclaimer') }}</p>

    @if (! $analysis->isCompleted())
        <p class="mt-3 rounded-control bg-amber-50 p-3 text-amber-800">{{ __('recruitment-applicant.cv_analysis.statuses.'.$analysis->status) }}</p>
    @else
        <dl class="mt-3 grid gap-4 sm:grid-cols-2">
            {{-- Experience --}}
            <div>
                <dt class="font-medium">{{ __('recruitment-applicant.cv_analysis.experience') }}</dt>
                <dd class="mt-1">
                    @php $years = $facts['experience']['years'] ?? null; @endphp
                    {{ $years === null ? __('recruitment-applicant.cv_analysis.not_found') : trans_choice('recruitment-applicant.cv_analysis.years', $years, ['count' => $years]) }}
                    @php $exp = $comparison['experience'] ?? ['outcome' => 'not_applicable']; @endphp
                    @if ($exp['outcome'] !== 'not_applicable')
                        <span class="ms-1 rounded-control px-2 py-0.5 text-xs font-semibold {{ $outcomeClasses[$exp['outcome']] ?? '' }}">
                            {{ __('recruitment-applicant.cv_analysis.outcomes.'.$exp['outcome']) }}
                            &middot; {{ __('recruitment-applicant.cv_analysis.required_years', ['count' => $exp['required'] ?? 0]) }}
                        </span>
                    @endif
                    @if (! empty($facts['experience']['ranges']))
                        <span class="block text-xs text-secondary-medium" dir="ltr">
                            {{ collect($facts['experience']['ranges'])->map(fn ($r) => $r[0].'–'.$r[1])->implode(', ') }}
                        </span>
                    @endif
                    @if (($facts['experience']['stated'] ?? null) !== null)
                        <span class="block text-xs text-secondary-medium">{{ __('recruitment-applicant.cv_analysis.stated', ['count' => $facts['experience']['stated']]) }}</span>
                    @endif
                </dd>
            </div>

            {{-- Education --}}
            <div>
                <dt class="font-medium">{{ __('recruitment-applicant.cv_analysis.education') }}</dt>
                <dd class="mt-1">
                    @if ($facts['education'] ?? null)
                        {{ __('recruitment-applicant.indicators.levels.'.$facts['education']['level']) }}
                        <span class="text-xs text-secondary-medium">(«{{ $facts['education']['keyword'] }}»)</span>
                    @else
                        {{ __('recruitment-applicant.cv_analysis.not_found') }}
                    @endif
                    @php $edu = $comparison['education'] ?? ['outcome' => 'not_applicable']; @endphp
                    @if ($edu['outcome'] !== 'not_applicable')
                        <span class="ms-1 rounded-control px-2 py-0.5 text-xs font-semibold {{ $outcomeClasses[$edu['outcome']] ?? '' }}">
                            {{ __('recruitment-applicant.cv_analysis.outcomes.'.$edu['outcome']) }}
                            @isset($edu['required'])
                                &middot; {{ __('recruitment-applicant.cv_analysis.required_level', ['level' => __('recruitment-applicant.indicators.levels.'.$edu['required'])]) }}
                            @endisset
                        </span>
                    @endif
                </dd>
            </div>

            {{-- Languages --}}
            <div>
                <dt class="font-medium">{{ __('recruitment-applicant.cv_analysis.languages') }}</dt>
                <dd class="mt-1">
                    {{ collect($facts['languages'] ?? [])->map(fn ($l) => __('recruitment-applicant.cv_analysis.language_names.'.$l))->implode(__('recruitment-applicant.list_separator')) ?: __('recruitment-applicant.cv_analysis.not_found') }}
                </dd>
            </div>

            {{-- General skills from the dictionary --}}
            <div>
                <dt class="font-medium">{{ __('recruitment-applicant.cv_analysis.skills_detected') }}</dt>
                <dd class="mt-1 flex flex-wrap gap-1">
                    @forelse ($facts['skills'] ?? [] as $skill)
                        <span class="rounded-control bg-blue-50 px-2 py-0.5 text-xs text-blue-800">{{ $skill }}</span>
                    @empty
                        {{ __('recruitment-applicant.cv_analysis.not_found') }}
                    @endforelse
                </dd>
            </div>
        </dl>

        {{-- Required skills from the request, one by one --}}
        @php $skills = $comparison['skills'] ?? ['outcome' => 'not_applicable', 'items' => []]; @endphp
        @if ($skills['outcome'] === 'assessed')
            <div class="mt-4">
                <p class="font-medium">
                    {{ __('recruitment-applicant.cv_analysis.required_skills', ['matched' => $skills['matched'], 'total' => $skills['total']]) }}
                </p>
                <ul class="mt-2 space-y-1">
                    @foreach ($skills['items'] as $item)
                        <li class="flex items-start gap-2">
                            <span @class(['font-bold', 'text-green-700' => $item['matched'], 'text-secondary-medium' => ! $item['matched']])>{{ $item['matched'] ? '✓' : '—' }}</span>
                            <span>
                                {{ $item['term'] }}
                                @if ($item['matched'] && $item['evidence'])
                                    <span class="text-xs text-secondary-medium">({{ __('recruitment-applicant.cv_analysis.found_as', ['word' => $item['evidence']]) }})</span>
                                @elseif (! $item['matched'])
                                    <span class="text-xs text-secondary-medium">({{ __('recruitment-applicant.cv_analysis.not_mentioned') }})</span>
                                @endif
                            </span>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    @endif
</details>
