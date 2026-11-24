@php
    /*
     * Add employee, when accepted job offers are still waiting to be converted.
     * Converting from the offer links the person to their recruitment file and
     * writes the contract from the accepted terms; a record typed in here does
     * neither. Only for who may convert hires, and only for offers they can see
     * (the same gates as the convert page and the offers list).
     */
    $awaitingUser = auth()->user();
    $awaitingQuery = $awaitingUser?->can('recruitment-applicants.convert-hires')
        ? \Modules\Hr\Models\RecruitmentHiringConversion::query()
            ->whereIn('status', [\Modules\Hr\Models\RecruitmentHiringConversion::STATUS_PENDING, \Modules\Hr\Models\RecruitmentHiringConversion::STATUS_FAILED])
            ->whereHas('jobOffer', fn ($offers) => $offers->visibleTo($awaitingUser))
        : null;
    $awaitingCount = $awaitingQuery?->count() ?? 0;
    $awaitingRows = $awaitingCount > 0 ? (clone $awaitingQuery)->with(['applicant', 'jobOffer.position'])->oldest()->take(3)->get() : collect();
@endphp

@if ($awaitingCount > 0)
    <section class="rounded-lg border border-line border-s-4 border-s-status-critical bg-white p-4 dark:bg-surface-1" role="status" data-offers-awaiting-conversion>
        <h2 class="font-semibold">{{ trans_choice('hr_employees.offers_awaiting.title', $awaitingCount, ['count' => $awaitingCount]) }}</h2>
        <p class="mt-1 text-sm text-secondary-medium dark:text-ink-muted">{{ __('hr_employees.offers_awaiting.hint') }}</p>

        <ul class="mt-3 divide-y divide-line">
            @foreach ($awaitingRows as $conversion)
                <li class="flex flex-wrap items-center justify-between gap-2 py-2" wire:key="awaiting-offer-{{ $conversion->id }}">
                    <span class="min-w-0">
                        <span class="font-medium" dir="auto">{{ $conversion->applicant?->full_name ?? '—' }}</span>
                        @if ($conversion->jobOffer?->position)
                            <span class="text-sm text-secondary-medium dark:text-ink-muted"> · <span dir="auto">{{ $conversion->jobOffer->position->title_en ?: $conversion->jobOffer->position->title_ar }}</span></span>
                        @endif
                    </span>
                    <x-ui.button size="sm" :href="route('hr.recruitment.offers.convert', ['offerId' => $conversion->job_offer_id])" wire:navigate>{{ __('hr_employees.offers_awaiting.convert') }}</x-ui.button>
                </li>
            @endforeach
        </ul>

        @if ($awaitingCount > $awaitingRows->count())
            <x-ui.button tone="ghost" size="sm" class="mt-2" :href="route('hr.recruitment.offers.index')" wire:navigate>{{ __('hr_employees.offers_awaiting.all') }}</x-ui.button>
        @endif
    </section>
@endif
