{{--
    Donor requests waiting for the General Manager (W-04, D-57). Rendered only
    for holders of donors.approve: pendingRequests is empty for everyone else.
--}}
@if ($this->pendingRequests->isNotEmpty())
    <x-ui.panel :title="__('donors.pending_requests')" data-pending-requests>
        <ul class="divide-y divide-secondary-soft dark:divide-line" role="list">
            @foreach ($this->pendingRequests as $request)
                <li class="flex flex-wrap items-center justify-between gap-3 py-2.5" wire:key="pending-donor-{{ $request->id }}">
                    <div class="min-w-0">
                        <p class="font-semibold" dir="auto">{{ $request->name }}</p>
                        <p class="text-xs text-secondary-medium dark:text-ink-muted">
                            {{ __('donors.requested_by_on', ['name' => $request->requester?->name ?? __('notifications.system'), 'date' => $request->created_at?->translatedFormat('j M Y')]) }}
                        </p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <x-ui.button type="button" tone="primary" size="sm" icon="check" wire:click="approveDonor({{ $request->id }})" wire:loading.attr="disabled" wire:target="approveDonor({{ $request->id }})" data-test="approve-donor">
                            {{ __('donors.approve') }}
                        </x-ui.button>
                        <x-ui.button type="button" tone="secondary" size="sm" icon="x-mark" wire:click="confirmDecline({{ $request->id }})" data-test="decline-donor">
                            {{ __('donors.decline') }}
                        </x-ui.button>
                    </div>
                </li>
            @endforeach
        </ul>
    </x-ui.panel>
@endif
