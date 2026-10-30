<x-slot:title>
    {{ __('notifications.settings.title') }}
</x-slot:title>

@php($presenter = app(\App\Notifications\NotificationPresenter::class))

<div class="mx-auto w-full max-w-3xl space-y-4 px-4 py-8 sm:px-0">
    <div>
        <h1 class="text-h4 font-semibold text-ink">{{ __('notifications.settings.title') }}</h1>
        <p class="mt-1 text-small text-ink-2">{{ __('notifications.settings.intro') }}</p>
    </div>

    <div class="overflow-hidden rounded-surface border border-line-soft bg-card shadow-e1">
        <h2 class="border-b border-line-soft px-5 py-3 text-body font-semibold text-ink">{{ __('notifications.settings.mutes') }}</h2>

        <ul>
            @forelse ($modules as $key => $module)
                @php($isMuted = in_array($key, $muted, true))
                <li wire:key="mute-{{ $key }}" class="flex items-center gap-3 border-b border-line-soft px-5 py-3.5 last:border-b-0" data-mute-module="{{ $key }}">
                    <span class="inline-flex size-9 shrink-0 items-center justify-center rounded-full bg-surface text-ink" aria-hidden="true">
                        <flux:icon :name="$presenter->moduleIcon($key)" variant="mini" class="size-4.5" />
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="text-body font-semibold text-ink">{{ $presenter->moduleLabel($key) }}</p>
                        <p class="text-caption text-ink-2">{{ $isMuted ? __('notifications.settings.state_muted') : __('notifications.settings.state_on') }}</p>
                    </div>
                    <button
                        type="button"
                        role="switch"
                        aria-checked="{{ $isMuted ? 'false' : 'true' }}"
                        aria-label="{{ __('notifications.settings.toggle', ['module' => $presenter->moduleLabel($key)]) }}"
                        wire:click="toggleMute('{{ $key }}')"
                        wire:loading.attr="disabled"
                        @class([
                            'relative inline-flex h-6 w-11 shrink-0 cursor-pointer items-center rounded-full transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-line disabled:opacity-60',
                            'bg-primary' => ! $isMuted,
                            'bg-line' => $isMuted,
                        ])
                    >
                        <span @class([
                            'inline-block size-5 rounded-full bg-white shadow-e1 transition-transform',
                            'translate-x-5.5 rtl:-translate-x-5.5' => ! $isMuted,
                            'translate-x-0.5 rtl:-translate-x-0.5' => $isMuted,
                        ])></span>
                    </button>
                </li>
            @empty
                <li class="px-5 py-8 text-center text-small text-ink-2" data-state="empty">{{ __('notifications.settings.nothing_to_mute') }}</li>
            @endforelse
        </ul>
    </div>

    <p class="flex items-start gap-2 text-caption text-ink-2">
        <flux:icon.bell-alert variant="micro" class="mt-0.5 size-3.5 shrink-0" />
        {{ __('notifications.settings.direct_note') }}
    </p>
</div>
