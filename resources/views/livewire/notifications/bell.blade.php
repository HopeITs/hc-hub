@php
    $presenter = app(\App\Notifications\NotificationPresenter::class);
    $total = $counts['total'];
    $critical = $counts['critical'];
    $chipModules = $counts['modules'];

    if ($module !== '' && ! array_key_exists($module, $chipModules)) {
        $chipModules[$module] = 0;
    }

    $iconButton = 'inline-flex size-8 cursor-pointer items-center justify-center rounded-lg text-ink-3 transition-colors hover:bg-hover hover:text-ink focus-visible:outline-2 focus-visible:outline-line';
@endphp

<div
    x-data="{ open: false, ring: false }"
    x-on:keydown.escape.window="open = false"
    x-on:click.outside="open = false"
    x-on:notif-arrived.window="ring = false; $nextTick(() => { ring = true; setTimeout(() => ring = false, 850) })"
    wire:poll.10s="refreshCounts"
    class="relative"
    data-notification-bell
>
    <button
        type="button"
        x-on:click="open = ! open; if (open) { $wire.loadPanel() }"
        x-bind:aria-expanded="open.toString()"
        aria-haspopup="dialog"
        aria-label="{{ trans_choice('notifications.bell_label', $total, ['count' => $total]) }}"
        class="relative inline-flex size-11 cursor-pointer items-center justify-center rounded-[14px] text-secondary-light transition hover:bg-white hover:text-secondary-dark hover:shadow-e1 focus:outline-none focus:ring-2 focus:ring-neutral-200 sm:size-9"
        data-test="notification-bell-button"
    >
        <span x-bind:class="ring && 'animate-notif-shake'" class="inline-flex">
            <svg width="15" height="15" viewBox="0 0 28.55 29.92" fill="currentColor" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M15.04,29.92h-1.38c-2.69-.32-4.88-2.29-5.43-4.96H3.77c-1.16,0-2.21-.51-2.9-1.35-.74-.89-1.04-2.05-.78-3.2l2.53-11.37C3.73,4.06,8.02.41,13.1.03c5.44-.41,10.56,3.11,12.01,8.35l3.31,11.93c.32,1.15,0,2.36-.73,3.27-.68.84-1.72,1.38-2.87,1.38h-4.35c-.54,2.67-2.74,4.65-5.42,4.97ZM24.84,22.46c.45,0,.78-.26.97-.52.24-.34.29-.7.18-1.11l-3.25-11.7c-1.13-4.08-4.97-6.79-9.14-6.62s-7.65,3.1-8.56,7.19l-2.49,11.22c-.17.77.38,1.53,1.19,1.53h21.11ZM17.85,24.96h-7c.54,1.52,1.97,2.5,3.54,2.49s2.98-1,3.47-2.49Z"/></svg>
        </span>

        @if ($total > 0)
            <span
                class="pointer-events-none absolute -end-0.5 -top-0.5 inline-flex h-[17px] min-w-[17px] items-center justify-center rounded-full bg-primary px-[4px] text-caption font-bold leading-none tabular-nums text-white shadow-e1 ring-2 ring-white"
                x-bind:class="ring && 'animate-notif-pop'"
                data-unread-count
                @if ($critical > 0) data-critical-unread @endif
            >{{ $total > 99 ? '99+' : $total }}</span>
        @endif
    </button>

    {{-- On phones the panel is fixed between 12px gutters (anchored to the
         bell it ran off the edge at 320-375px); from `sm` it hangs from the
         bell, its end edge lined up with the bell's. The list scrolls inside. --}}
    <div
        x-cloak
        x-show="open"
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 -translate-y-1 scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
        x-transition:leave-end="opacity-0 -translate-y-1 scale-95"
        role="dialog"
        aria-label="{{ __('notifications.inbox') }}"
        class="z-50 mt-2 flex origin-top flex-col overflow-hidden rounded-surface border border-line-soft bg-card text-ink shadow-e3 max-sm:fixed max-sm:inset-x-3 max-sm:top-[4.5rem] max-sm:mt-0 max-sm:w-auto sm:absolute sm:end-0 sm:top-full sm:w-[26rem] sm:max-w-[calc(100vw-1.5rem)]"
        data-notification-panel
    >
        {{-- Header --}}
        <div class="flex items-start justify-between gap-3 px-4 pt-4">
            <div class="min-w-0">
                <p class="text-h6 font-bold leading-tight">{{ __('notifications.inbox') }}</p>
                <p class="mt-0.5 text-caption font-semibold text-ink-2">
                    @if ($total > 0)
                        <span class="tabular-nums">{{ trans_choice('states.notifications-summary', $total) }}</span>
                        @if ($critical > 0)
                            <span aria-hidden="true" class="text-ink-3">·</span>
                            <span class="tabular-nums text-primary">{{ trans_choice('notifications.critical_count', $critical, ['count' => $critical]) }}</span>
                        @endif
                    @else
                        {{ __('notifications.caught_up') }}
                    @endif
                </p>
            </div>
            <div class="-me-1.5 flex items-center gap-0.5">
                <a href="{{ route('notifications.index') }}" wire:navigate class="{{ $iconButton }}" aria-label="{{ __('notifications.full_page') }}" title="{{ __('notifications.full_page') }}">
                    <flux:icon.arrows-pointing-out variant="mini" class="size-4" />
                </a>
                <a href="{{ route('notifications.settings') }}" wire:navigate class="{{ $iconButton }}" aria-label="{{ __('notifications.settings_link') }}" title="{{ __('notifications.settings_link') }}" data-bell-settings>
                    <flux:icon.cog-6-tooth variant="mini" class="size-4" />
                </a>
            </div>
        </div>

        {{-- Tabs --}}
        <div class="px-4 pt-3">
            <div class="grid grid-cols-3 gap-1 rounded-xl bg-hover p-1 text-small" role="tablist">
                @foreach (['all', 'unread', 'alerts'] as $key)
                    <button
                        type="button"
                        role="tab"
                        wire:click="showTab('{{ $key }}')"
                        aria-selected="{{ $tab === $key ? 'true' : 'false' }}"
                        aria-controls="notification-panel-{{ $this->getId() }}"
                        @class([
                            'inline-flex min-h-8 cursor-pointer items-center justify-center gap-1.5 rounded-lg px-2 font-semibold transition-colors',
                            'bg-card text-ink shadow-e1' => $tab === $key,
                            'text-ink-2 hover:text-ink' => $tab !== $key,
                        ])
                        data-bell-tab="{{ $key }}"
                    >
                        {{ __("notifications.tabs.{$key}") }}
                        @if ($key === 'unread' && $total > 0)
                            <span class="inline-flex min-w-4.5 items-center justify-center rounded-full bg-primary px-1 text-[0.625rem] font-bold leading-4.5 tabular-nums text-white">{{ $total > 99 ? '99+' : $total }}</span>
                        @endif
                    </button>
                @endforeach
            </div>
        </div>

        {{-- Module chips, once more than one module has something unread --}}
        @if (count($chipModules) > 1 || $module !== '')
            <div class="mt-2.5 flex gap-1.5 overflow-x-auto px-4 pb-1 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden" data-bell-modules>
                @foreach ($chipModules as $chip => $chipCount)
                    <button
                        type="button"
                        wire:click="showModule('{{ $chip }}')"
                        wire:key="bell-chip-{{ $chip }}"
                        aria-pressed="{{ $module === $chip ? 'true' : 'false' }}"
                        @class([
                            'inline-flex min-h-8 shrink-0 cursor-pointer items-center gap-1.5 rounded-full border ps-2.5 pe-3 text-caption font-semibold whitespace-nowrap transition-colors',
                            'border-ink bg-ink text-card' => $module === $chip,
                            'border-line-soft text-ink hover:border-line' => $module !== $chip,
                        ])
                        data-bell-module="{{ $chip }}"
                    >
                        <flux:icon :name="$presenter->moduleIcon($chip)" variant="micro" class="size-3.5" />
                        {{ $presenter->moduleLabel($chip) }}
                        @if ($chipCount > 0)
                            <span class="tabular-nums opacity-70">{{ $chipCount }}</span>
                        @endif
                    </button>
                @endforeach
            </div>
        @endif

        {{-- Announces loading and the unread total politely. --}}
        <p class="sr-only" role="status" aria-live="polite" aria-atomic="true">
            <span wire:loading wire:target="loadPanel,showTab,showModule,markAllAsRead,retryLoad">{{ __('states.notifications-loading') }}</span>
            <span wire:loading.remove wire:target="loadPanel,showTab,showModule,markAllAsRead,retryLoad">{{ $loadFailed ? __('general.state-error-title') : trans_choice('states.notifications-summary', $total) }}</span>
        </p>

        {{-- Rows --}}
        <div id="notification-panel-{{ $this->getId() }}" role="tabpanel" class="mt-1 max-h-[min(22rem,calc(100dvh-10rem))] min-h-0 overflow-y-auto overscroll-contain px-2 pb-2" wire:loading.class="opacity-60" wire:target="showTab,showModule">
            @if (! $opened)
                <div class="space-y-1 px-2 py-2" data-panel-loading>
                    @foreach ([0.9, 0.7, 0.8] as $width)
                        <div class="flex gap-3 py-2.5" aria-hidden="true">
                            <span class="size-9 shrink-0 animate-pulse rounded-full bg-hover"></span>
                            <span class="flex-1 space-y-2 pt-1">
                                <span class="block h-3 animate-pulse rounded-full bg-hover" style="width: {{ $width * 100 }}%"></span>
                                <span class="block h-2.5 w-1/3 animate-pulse rounded-full bg-hover"></span>
                            </span>
                        </div>
                    @endforeach
                </div>
            @elseif ($loadFailed)
                <x-ui.load-error class="m-2 min-h-40" />
            @else
                @forelse ($sections as $section => $rows)
                    <div @class(['mt-2 rounded-xl bg-hover p-1' => $section === 'pinned']) @if ($section === 'pinned') data-bell-pinned @endif>
                        <p @class([
                            'px-2 pt-3 pb-1 text-caption font-bold',
                            'flex items-center gap-1.5 text-primary' => $section === 'pinned',
                            'text-ink-3' => $section !== 'pinned',
                        ])>
                            @if ($section === 'pinned')
                                <flux:icon.bell-alert variant="micro" class="size-3.5" />
                            @endif
                            {{ __("notifications.sections.{$section}") }}
                        </p>
                        <ol>
                            @foreach ($rows as $notification)
                                @include('livewire.notifications.partials.row', ['notification' => $notification, 'state' => $states[$notification->id] ?? null])
                            @endforeach
                        </ol>
                    </div>
                @empty
                    <div class="flex flex-col items-center justify-center gap-2 px-4 py-12 text-center" data-state="empty">
                        <span class="inline-flex size-11 items-center justify-center rounded-full bg-hover text-ink-3" aria-hidden="true">
                            <x-icons.notification class="size-5" />
                        </span>
                        <span class="text-body font-semibold text-ink">@lang('general.no-notifications')</span>
                        <span class="text-small text-ink-3">@lang('states.notifications-empty-help')</span>
                    </div>
                @endforelse
            @endif
        </div>

        {{-- Footer --}}
        <div class="flex items-center justify-between gap-3 border-t border-line-soft px-4 py-2.5 text-small">
            <button type="button" wire:click="markAllAsRead" @disabled($total === 0) class="inline-flex cursor-pointer items-center gap-1.5 font-semibold text-ink-2 transition-colors hover:text-ink disabled:cursor-default disabled:opacity-40">
                <flux:icon.check-circle variant="micro" />
                @lang('general.mark-all-read')
            </button>
            <a href="{{ route('notifications.index') }}" wire:navigate class="group/all inline-flex items-center gap-1 font-semibold text-primary">
                @lang('general.view-all')
                <flux:icon.arrow-right variant="micro" class="size-4 transition-transform group-hover/all:translate-x-0.5 rtl:rotate-180 rtl:group-hover/all:-translate-x-0.5" />
            </a>
        </div>
    </div>
</div>
