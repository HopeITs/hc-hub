@props(['version', 'canSeeSalary' => false, 'compact' => false])

{{-- One offer version's terms. The salary goes through OfferTerms::salary(), which prints a placeholder unless $canSeeSalary. --}}
@use('Modules\Hr\Support\Recruitment\OfferTerms')
@php
    $muted = 'text-secondary-medium dark:text-ink-muted';
    $fmt = fn ($date) => $date?->translatedFormat(__('formats.date')) ?? '—';
    $terms = $version->terms_json ?? [];
@endphp

<dl {{ $attributes->class([$compact ? 'grid grid-cols-2 gap-3 md:grid-cols-4' : 'grid gap-4 sm:grid-cols-2 lg:grid-cols-3', 'text-sm']) }}>
    <div>
        <dt class="{{ $muted }}">{{ __('hr_recruitment.applicant_ui.offers.salary') }}</dt>
        <dd class="font-medium tabular-nums" dir="auto">{{ OfferTerms::salary($version, $canSeeSalary) }}</dd>
    </div>
    <div>
        <dt class="{{ $muted }}">{{ __('hr_recruitment.applicant_ui.offers.start_date') }}</dt>
        <dd class="font-medium tabular-nums">{{ $fmt($version->start_date) }}</dd>
    </div>
    <div>
        <dt class="{{ $muted }}">{{ __('hr_recruitment.applicant_ui.offers.probation_days') }}</dt>
        <dd class="font-medium">{{ $version->probation_days === null ? '—' : __('hr_recruitment.applicant_ui.offers.days', ['count' => (int) $version->probation_days]) }}</dd>
    </div>
    <div>
        <dt class="{{ $muted }}">{{ __('hr_recruitment.applicant_ui.offers.contract_type') }}</dt>
        <dd class="font-medium" dir="auto">{{ $version->contractType?->name ?? '—' }}</dd>
    </div>
    @unless ($compact)
        <div>
            <dt class="{{ $muted }}">{{ __('hr_recruitment.applicant_ui.offers.employment_type') }}</dt>
            <dd class="font-medium">{{ OfferTerms::employmentType(isset($terms['employment_type']) ? (int) $terms['employment_type'] : null) }}</dd>
        </div>
        <div>
            <dt class="{{ $muted }}">{{ __('hr_recruitment.applicant_ui.offers.place') }}</dt>
            <dd class="font-medium">{{ OfferTerms::place(isset($terms['place']) ? (int) $terms['place'] : null) }}</dd>
        </div>
        <div>
            <dt class="{{ $muted }}">{{ __('hr_recruitment.applicant_ui.offers.end_date') }}</dt>
            <dd class="font-medium tabular-nums">{{ $fmt($version->end_date) }}</dd>
        </div>
        <div>
            <dt class="{{ $muted }}">{{ __('hr_recruitment.applicant_ui.offers.weekly_hours') }}</dt>
            <dd class="font-medium tabular-nums">{{ $version->weekly_hours ?? '—' }}</dd>
        </div>
        @if (filled($terms['notes'] ?? null))
            <div class="sm:col-span-2 lg:col-span-3">
                <dt class="{{ $muted }}">{{ __('hr_recruitment.applicant_ui.offers.other_terms') }}</dt>
                <dd class="whitespace-pre-line" dir="auto">{{ $terms['notes'] }}</dd>
            </div>
        @endif
    @endunless
</dl>
