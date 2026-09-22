<x-slot:title>{{ __('recruitment.invites_title') }}</x-slot:title>

<div class="space-y-6">
    <div>
        <h1 class="text-h2 font-semibold text-neutral-900">
            {{ __('recruitment.invites_title') }}
        </h1>
        <p class="mt-2 text-secondary-medium">
            {{ __('recruitment.invites_description') }}
        </p>
    </div>

    @if (session('recruitment_invite_added'))
        <p role="status" class="rounded-control border border-green-200 bg-green-50 p-4 text-green-800">
            {{ session('recruitment_invite_added') }}
        </p>
    @endif

    <form wire:submit="add" class="flex flex-wrap items-end gap-4 rounded-control border border-neutral-200 bg-white p-4 shadow-e1">
        <div class="min-w-[220px]">
            <x-forms.input-label for="full_name" required :value="__('recruitment.invite_full_name')" />
            <x-forms.input id="full_name" model="full_name" required />
            <x-forms.input-error field="full_name" input-id="full_name" />
        </div>

        <div class="min-w-[260px]">
            <x-forms.input-label for="email" required :value="__('recruitment.invite_email')" />
            <x-forms.input id="email" type="email" model="email" required />
            <x-forms.input-error field="email" input-id="email" />
        </div>

        <x-primary-button type="submit">
            {{ __('recruitment.invite_add') }}
        </x-primary-button>
    </form>

    <x-table>
        <x-slot name="headings">
            <x-table.heading>{{ __('recruitment.invite_full_name') }}</x-table.heading>
            <x-table.heading>{{ __('recruitment.invite_email') }}</x-table.heading>
            <x-table.heading>{{ __('recruitment.invite_status') }}</x-table.heading>
            <x-table.heading>{{ __('recruitment.invite_used_at') }}</x-table.heading>
            <x-table.heading />
        </x-slot>

        <x-slot name="body">
            @forelse ($invites as $invite)
                @php
                    $badgeClass = match ($invite->status) {
                        'used' => 'bg-primary-100 text-primary-800',
                        'cancelled' => 'bg-neutral-200 text-neutral-600',
                        default => 'bg-secondary-light text-secondary-dark',
                    };
                @endphp
                <tr wire:key="campaign-invite-{{ $invite->id }}">
                    <x-table.cell>{{ $invite->full_name }}</x-table.cell>
                    <x-table.cell>{{ $invite->email_normalized }}</x-table.cell>
                    <x-table.cell>
                        <span class="rounded-md px-2 py-1 text-xs font-semibold {{ $badgeClass }}">
                            {{ __('recruitment.invite_status_'.$invite->status) }}
                        </span>
                    </x-table.cell>
                    <x-table.cell>{{ $invite->used_at?->format('d-m-Y H:i') ?? '—' }}</x-table.cell>
                    <x-table.cell>
                        @if (! in_array($invite->status, ['cancelled', 'used'], true))
                            <button
                                type="button"
                                wire:click="cancel({{ $invite->id }})"
                                wire:confirm="{{ __('recruitment.invite_cancel_confirm') }}"
                                class="text-primary hover:underline text-body"
                            >
                                {{ __('recruitment.invite_cancel') }}
                            </button>
                        @endif
                    </x-table.cell>
                </tr>
            @empty
                <x-table.no-data :colspan="5" />
            @endforelse
        </x-slot>

        <x-slot name="pagination"></x-slot>
    </x-table>
</div>
