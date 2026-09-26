@php use App\Enums\RecruitmentJobOfferStatus; @endphp
<div>
    @if ($visible && ($offer || $canCreate))
        <div class="mt-4 flex flex-wrap items-center justify-between gap-2 rounded-control border border-neutral-200 p-3 text-sm">
            <h4 class="font-semibold">{{ __('recruitment-applicant.offers.job_offer') }}</h4>
            @if ($offer)
                <a href="{{ route('recruitment.offers.show', ['offerId' => $offer->id]) }}" wire:navigate class="flex items-center gap-2 hover:underline">
                    <span class="text-secondary-medium">v{{ $offer->currentVersion?->version_no }}</span>
                    <span class="rounded-control px-2 py-0.5 text-xs font-semibold {{ RecruitmentJobOfferStatus::classes($offer->status) }}">{{ __('recruitment-applicant.offers.statuses.'.$offer->status) }}</span>
                </a>
            @else
                <a href="{{ route('recruitment.offers.create', ['applicationId' => $application->id]) }}" wire:navigate
                   class="rounded-control border border-primary px-3 py-1 text-sm font-semibold text-primary hover:bg-primary/5">{{ __('recruitment-applicant.offers.create') }}</a>
            @endif
        </div>
    @endif
</div>
