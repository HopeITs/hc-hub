<div class="space-y-6 p-6">
    @if (session('recruitment_saved'))
        <p role="status" class="rounded-control border border-green-200 bg-green-50 p-4 text-green-800">{{ session('recruitment_saved') }}</p>
    @endif

    @if ($errors->any())
        <div role="alert" class="rounded-control border border-red-200 bg-red-50 p-4 text-red-800">
            <ul class="list-inside list-disc">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <section class="rounded-surface border border-neutral-200 bg-white p-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="text-h3 font-semibold">{{ $campaign->vacancy?->position?->title ?? '—' }}</h1>
                <p class="mt-1 text-sm text-secondary-medium">
                    {{ __('recruitment-campaign.modes.'.$campaign->link_mode) }}
                    &middot; {{ __('recruitment-campaign.created_by') }}:
                    {{ $campaign->creator?->profile?->name ?? $campaign->creator?->name ?? '—' }}
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <a href="{{ route('recruitment.campaigns.publishing', $campaign) }}" wire:navigate
                    class="cursor-pointer rounded-control bg-primary px-4 py-2 text-sm font-semibold text-white hover:bg-primary-600">
                    {{ __('recruitment.campaign.manage_publishing') }}
                </a>
                @php $badge = \App\Enums\HiringCampaignStatus::getLocalizedName($effective); @endphp
                <span class="rounded-control bg-{{ $badge['color'] }}/10 px-3 py-1 text-sm font-semibold text-{{ $badge['color'] }}">
                    {{ $badge['value'] }}
                </span>
            </div>
        </div>

        {{-- Both read from the clock, not the column: a campaign whose window
             shut an hour ago says so here even before any sweep has run. --}}
        <dl class="mt-6 grid gap-4 sm:grid-cols-2">
            <div>
                <dt class="text-sm text-secondary-medium">{{ __('recruitment-campaign.publicly_visible') }}</dt>
                <dd class="font-medium">{{ $publiclyVisible ? __('recruitment-campaign.yes') : __('recruitment-campaign.no') }}</dd>
            </div>
            <div>
                <dt class="text-sm text-secondary-medium">{{ __('recruitment-campaign.accepts_submissions') }}</dt>
                <dd class="font-medium">{{ $acceptsSubmissions ? __('recruitment-campaign.yes') : __('recruitment-campaign.no') }}</dd>
            </div>
            <div>
                <dt class="text-sm text-secondary-medium">{{ __('recruitment-campaign.starts_at') }}</dt>
                <dd class="font-medium">{{ $campaign->starts_at?->format('Y-m-d H:i') ?? '—' }}</dd>
            </div>
            <div>
                <dt class="text-sm text-secondary-medium">{{ __('recruitment-campaign.ends_at') }}</dt>
                <dd class="font-medium">{{ $campaign->ends_at?->format('Y-m-d H:i') ?? '—' }}</dd>
            </div>
            @if ($campaign->closedBy)
                <div>
                    <dt class="text-sm text-secondary-medium">{{ __('recruitment-campaign.closed_by') }}</dt>
                    <dd class="font-medium">{{ $campaign->closedBy->profile?->name ?? $campaign->closedBy->name }}</dd>
                </div>
            @endif
        </dl>
    </section>

    @if ($mayManage)
        <section class="rounded-surface border border-neutral-200 bg-white p-6">
            <h2 class="text-h4 font-semibold">{{ __('recruitment-campaign.window') }}</h2>
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div>
                    <x-forms.input-label for="startsAt">{{ __('recruitment-campaign.starts_at') }}</x-forms.input-label>
                    <input id="startsAt" type="datetime-local" wire:model="startsAt" class="mt-1 w-full rounded-control border-neutral-300">
                </div>
                <div>
                    <x-forms.input-label for="endsAt">{{ __('recruitment-campaign.ends_at') }}</x-forms.input-label>
                    <input id="endsAt" type="datetime-local" wire:model="endsAt" class="mt-1 w-full rounded-control border-neutral-300">
                </div>
            </div>
            <div class="mt-4">
                <x-primary-button type="button" wire:click="saveWindow" wire:loading.attr="disabled" wire:target="saveWindow">
                    {{ __('recruitment-campaign.save_window') }}
                </x-primary-button>
            </div>
        </section>

        <section class="rounded-surface border border-primary/30 bg-primary/5 p-6">
            <h2 class="text-h4 font-semibold">{{ __('recruitment-campaign.actions') }}</h2>

            @if ($available)
                <div class="mt-4">
                    <x-forms.input-label for="reason">{{ __('recruitment-campaign.reason') }}</x-forms.input-label>
                    <textarea id="reason" rows="2" wire:model="reason" class="mt-1 w-full rounded-control border-neutral-300"></textarea>
                    <p class="mt-1 text-sm text-secondary-medium">{{ __('recruitment-campaign.reason_hint') }}</p>
                </div>

                {{-- Which buttons appear is asked of the state machine, so the
                     screen cannot offer a move the server would refuse. --}}
                <div class="mt-4 flex flex-wrap gap-3">
                    @foreach ($available as $action)
                        <button type="button" wire:key="action-{{ $action }}"
                            wire:click="act('{{ $action }}')"
                            @if (in_array($action, ['close', 'disable'], true))
                                wire:confirm="{{ __('recruitment-campaign.confirm.'.$action) }}"
                            @endif
                            wire:loading.attr="disabled" wire:target="act"
                            class="rounded-control border px-4 py-2 text-sm font-semibold
                                {{ in_array($action, ['close', 'disable'], true)
                                    ? 'border-red-300 text-red-700 hover:bg-red-50'
                                    : 'border-neutral-300 hover:bg-neutral-50' }}">
                            {{ __('recruitment-campaign.act.'.$action) }}
                        </button>
                    @endforeach
                </div>
            @else
                <p class="mt-2 text-sm text-secondary-medium">{{ __('recruitment-campaign.nothing_available') }}</p>
            @endif
        </section>
    @endif
</div>
