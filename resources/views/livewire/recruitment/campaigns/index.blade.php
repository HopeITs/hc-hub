<div class="space-y-6 p-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-h3 font-semibold">{{ __('recruitment-campaign.campaigns') }}</h1>
            <p class="mt-1 text-sm text-secondary-medium">{{ __('recruitment-campaign.campaigns_hint') }}</p>
        </div>
        {{-- The permission alone, since the policy's create needs a vacancy and
             none is chosen yet. Each seat is authorized again on the next screen. --}}
        @if (auth()->user()?->hasActivePermission('recruitment-campaigns.manage'))
            <a href="{{ route('recruitment.campaigns.create') }}" wire:navigate
                class="rounded-control bg-primary px-4 py-2 text-sm font-semibold text-white hover:bg-primary-600">
                {{ __('recruitment-campaign.new_campaign') }}
            </a>
        @endif
    </div>

    <livewire:recruitment.campaigns.campaign-table />
</div>
