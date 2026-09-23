<section class="mt-5 rounded-card border border-neutral-200 bg-white p-5 shadow-e1">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h2 class="text-subtitle font-bold text-neutral-900">{{ __('recruitment.public_link.title') }}</h2>
            <p class="mt-1 text-body text-neutral-500">{{ __('recruitment.public_link.help') }}</p>
        </div>
        @if ($activeLink)
            <span class="rounded-full bg-green-100 px-3 py-1 text-small font-semibold text-green-800">
                {{ __('recruitment.public_link.active') }}
            </span>
        @else
            <span class="rounded-full bg-neutral-100 px-3 py-1 text-small font-semibold text-neutral-600">
                {{ __('recruitment.public_link.not_active') }}
            </span>
        @endif
    </div>

    @if ($notice)
        <div class="mt-4 rounded-control p-3 text-body {{ $noticeType === 'error' ? 'bg-red-50 text-red-800' : 'bg-green-50 text-green-800' }}" role="status">
            {{ $notice }}
        </div>
    @endif

    <div class="mt-5 grid gap-4 md:grid-cols-2">
        <div>
            <label for="public-link-mode" class="mb-1 block text-body font-semibold text-neutral-800">{{ __('recruitment.public_link.mode') }}</label>
            <select id="public-link-mode" wire:model.live="linkMode" class="w-full cursor-pointer rounded-control border border-neutral-300 bg-white px-3 py-2.5 text-body">
                <option value="public_open">{{ __('recruitment.public_link.public_open') }}</option>
                <option value="invitation_only">{{ __('recruitment.public_link.invitation_only') }}</option>
            </select>
            @error('linkMode') <p class="mt-1 text-small text-red-700">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="public-link-expiry" class="mb-1 block text-body font-semibold text-neutral-800">{{ __('recruitment.public_link.expires_at') }}</label>
            <input id="public-link-expiry" type="datetime-local" wire:model="expiresAt" class="w-full rounded-control border border-neutral-300 bg-white px-3 py-2.5 text-body">
            @error('expiresAt') <p class="mt-1 text-small text-red-700">{{ $message }}</p> @enderror
        </div>
    </div>

    <div class="mt-4 flex flex-wrap gap-2">
        <button type="button" wire:click="generate" wire:loading.attr="disabled" class="cursor-pointer rounded-control bg-primary px-4 py-2.5 text-body font-semibold text-white hover:bg-primary/90 disabled:cursor-wait disabled:opacity-60">
            {{ $activeLink ? __('recruitment.public_link.regenerate') : __('recruitment.public_link.generate') }}
        </button>
        @if ($activeLink)
            <button type="button" x-on:click="$dispatch('open-recruitment-confirmation', { scope: 'public-link-manager', action: 'disable', args: [{{ $activeLink->id }}], message: @js(__('recruitment.public_link.disable_confirm')), confirmLabel: @js(__('recruitment.public_link.disable')), tone: 'danger' })" class="cursor-pointer rounded-control border border-red-300 bg-white px-4 py-2.5 text-body font-semibold text-red-700 hover:bg-red-50">
                {{ __('recruitment.public_link.disable') }}
            </button>
        @endif
    </div>

    @if ($generatedUrl)
        <div class="mt-4 rounded-control border border-green-200 bg-green-50 p-4">
            <label for="generated-public-url" class="block text-small font-semibold text-green-900">{{ __('recruitment.public_link.copy_now') }}</label>
            <div class="mt-2 flex gap-2" x-data="{ copied: false }">
                <input x-ref="url" id="generated-public-url" type="text" readonly value="{{ $generatedUrl }}" class="min-w-0 flex-1 rounded-control border border-green-300 bg-white px-3 py-2 text-body" dir="ltr">
                <button type="button" x-on:click="navigator.clipboard.writeText($refs.url.value); copied = true; setTimeout(() => copied = false, 1800)" class="cursor-pointer rounded-control bg-neutral-900 px-4 py-2 text-body font-semibold text-white hover:bg-neutral-800">
                    <span x-show="!copied">{{ __('recruitment.public_link.copy') }}</span>
                    <span x-cloak x-show="copied">{{ __('recruitment.public_link.copied') }}</span>
                </button>
            </div>
            <p class="mt-2 text-small text-green-800">{{ __('recruitment.public_link.copy_warning') }}</p>
        </div>
    @elseif ($activeLink)
        <p class="mt-4 rounded-control bg-amber-50 p-3 text-small text-amber-900">{{ __('recruitment.public_link.hash_only_notice') }}</p>
    @endif

    @if ($linkMode === 'invitation_only' && $campaign->link_mode !== 'invitation_only')
        <p class="mt-4 rounded-control bg-blue-50 p-3 text-small text-blue-900">{{ __('recruitment.public_link.generate_invitation_first') }}</p>
    @endif

    @if ($linkMode === 'invitation_only' && $campaign->link_mode === 'invitation_only')
        <div class="mt-6 border-t border-neutral-200 pt-5">
            <h3 class="font-bold text-neutral-900">{{ __('recruitment.public_link.invites') }}</h3>
            <p class="mt-1 text-small text-neutral-500">{{ __('recruitment.public_link.invites_help') }}</p>

            <div class="mt-4 grid gap-3 md:grid-cols-[1fr_1fr_auto]">
                <div>
                    <label for="invite-name" class="mb-1 block text-small font-semibold text-neutral-700">{{ __('recruitment.public_link.full_name') }}</label>
                    <input id="invite-name" type="text" wire:model="inviteName" class="w-full rounded-control border border-neutral-300 px-3 py-2 text-body">
                    @error('inviteName') <p class="mt-1 text-small text-red-700">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="invite-email" class="mb-1 block text-small font-semibold text-neutral-700">{{ __('recruitment.public_link.email') }}</label>
                    <input id="invite-email" type="email" wire:model="inviteEmail" class="w-full rounded-control border border-neutral-300 px-3 py-2 text-body" dir="ltr">
                    @error('inviteEmail') <p class="mt-1 text-small text-red-700">{{ $message }}</p> @enderror
                </div>
                <button type="button" wire:click="addInvite" class="mt-6 cursor-pointer self-start rounded-control border border-primary px-4 py-2 text-body font-semibold text-primary hover:bg-primary/5">
                    {{ __('recruitment.public_link.add_invite') }}
                </button>
            </div>

            <div class="mt-4 overflow-x-auto">
                <table class="w-full text-start text-body">
                    <thead class="border-b border-neutral-200 text-small uppercase text-neutral-500">
                        <tr>
                            <th class="px-3 py-2 text-start">{{ __('recruitment.public_link.full_name') }}</th>
                            <th class="px-3 py-2 text-start">{{ __('recruitment.public_link.email') }}</th>
                            <th class="px-3 py-2 text-start">{{ __('recruitment.status') }}</th>
                            <th class="px-3 py-2"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-100">
                        @forelse ($invites as $invite)
                            <tr wire:key="campaign-invite-{{ $invite->id }}">
                                <td class="px-3 py-2">{{ $invite->full_name }}</td>
                                <td class="px-3 py-2" dir="ltr">{{ $invite->email_normalized }}</td>
                                <td class="px-3 py-2">{{ __('recruitment.public_link.invite_status_'.$invite->status) }}</td>
                                <td class="px-3 py-2 text-end">
                                    <button type="button" wire:click="cancelInvite({{ $invite->id }})" class="cursor-pointer text-small font-semibold text-red-700 hover:underline">
                                        {{ __('recruitment.public_link.cancel_invite') }}
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-3 py-5 text-center text-neutral-500">{{ __('recruitment.public_link.no_invites') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif
    <x-recruitment.confirmation-modal scope="public-link-manager" />
</section>
