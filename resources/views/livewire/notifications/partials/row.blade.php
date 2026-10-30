{{-- One bell row. Expects $notification (UserNotification), $state (NotificationRequestState or null), $presenter, $iconButton. --}}
@php
    $isUnread = $notification->read_at === null;
    $safeUrl = $this->safeNotificationUrl($notification->data['url'] ?? null);
    $isMailto = is_string($safeUrl) && str_starts_with($safeUrl, 'mailto:');
    $unavailable = ($state['state'] ?? null) === 'unavailable';
    $body = $unavailable ? __('general.notification-target-unavailable') : $presenter->body($notification->data);
    $moduleKey = $notification->moduleKey();
@endphp

<li
    wire:key="bell-{{ $notification->id }}"
    class="notif-row group/row relative flex gap-3 rounded-xl px-2 py-2.5 transition-colors hover:bg-hover"
    data-notification-row
    data-id="{{ $notification->id }}"
    data-priority="{{ $notification->priority->value }}"
    @if ($isUnread) data-unread @endif
>
    <span @class([
        'relative mt-0.5 flex size-9 shrink-0 items-center justify-center rounded-full',
        'bg-primary text-white' => $notification->priority === \App\Enums\NotificationPriority::Critical,
        'bg-surface text-ink' => $notification->priority !== \App\Enums\NotificationPriority::Critical,
        'opacity-60' => ! $isUnread,
    ]) aria-hidden="true">
        <flux:icon :name="$presenter->moduleIcon($moduleKey)" variant="mini" class="size-[1.125rem]" />
        @if ($isUnread)
            <span class="absolute -end-0.5 -top-0.5 size-2.5 rounded-full bg-primary ring-2 ring-card" data-unread-marker></span>
        @endif
    </span>

    <div class="min-w-0 flex-1">
        <a
            @if ($safeUrl)
                href="{{ $safeUrl }}"
                @if ($isMailto)
                    wire:click="markAsRead('{{ $notification->id }}')"
                @else
                    wire:click.prevent="openNotification('{{ $notification->id }}')"
                @endif
            @elseif ($isUnread)
                role="button"
                tabindex="0"
                wire:click="markAsRead('{{ $notification->id }}')"
                wire:keydown.enter="markAsRead('{{ $notification->id }}')"
            @endif
            class="block cursor-pointer rounded-md focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-line"
            data-notification-link
        >
            <p @class([
                'text-small leading-snug text-pretty',
                'font-semibold text-ink' => $isUnread,
                'text-ink-2' => ! $isUnread,
            ])>{{ $presenter->title($notification->data) }}</p>

            @if ($body)
                <p class="mt-0.5 line-clamp-2 text-caption text-ink-2">{{ $body }}</p>
            @endif

            @if ($state && $state['state'] !== 'info')
                <p class="mt-1 flex flex-wrap items-center gap-x-2 text-caption">
                    @if ($state['state'] === 'awaiting')
                        <span class="rounded-full bg-hover px-2 py-0.5 font-semibold text-primary">@lang('general.notification-state.awaiting')</span>
                    @elseif ($state['state'] === 'handled')
                        <span class="rounded-full bg-hover px-2 py-0.5 font-semibold text-ink-2">@lang('general.notification-state.handled')</span>
                    @else
                        <span class="rounded-full bg-hover px-2 py-0.5 font-semibold text-ink-2">@lang('general.notification-state.unavailable')</span>
                    @endif
                    @if ($state['status'])
                        <span class="text-ink-2">@lang('general.notification-state.status', ['status' => $state['status']])</span>
                    @endif
                </p>
                @if ($state['next_step'] && $state['state'] !== 'handled')
                    <p class="mt-1 text-caption text-ink-2">{{ $state['next_step'] }}</p>
                @endif
            @endif

            <p class="mt-1 flex flex-wrap items-center gap-x-1.5 text-caption text-ink-3">
                @if ($notification->priority !== \App\Enums\NotificationPriority::Info)
                    <span @class([
                        'inline-flex items-center gap-1 font-bold',
                        'text-primary' => $notification->priority === \App\Enums\NotificationPriority::Critical,
                        'text-ink' => $notification->priority === \App\Enums\NotificationPriority::Important,
                    ]) data-priority-label>
                        <flux:icon.bell-alert variant="micro" class="size-3" />
                        {{ $notification->priority->label() }}
                    </span>
                    <span aria-hidden="true">·</span>
                @endif
                <time datetime="{{ $notification->created_at?->toIso8601String() }}">{{ $notification->created_at?->diffForHumans() }}</time>
                <span aria-hidden="true">·</span>
                <span>{{ $presenter->moduleLabel($moduleKey) }}</span>
            </p>
        </a>
    </div>

    <div x-data="{ snoozing: false }" class="relative flex shrink-0 items-start gap-0.5 transition-opacity lg:opacity-0 lg:group-hover/row:opacity-100 lg:group-focus-within/row:opacity-100 lg:has-[[aria-expanded=true]]:opacity-100">
        @if ($isUnread)
            <button type="button" wire:click="markAsRead('{{ $notification->id }}')" class="{{ $iconButton }}" aria-label="{{ __('notifications.mark_read') }}" title="{{ __('notifications.mark_read') }}" data-toggle-read>
                <flux:icon.check variant="micro" />
            </button>
        @else
            <button type="button" wire:click="markUnread('{{ $notification->id }}')" class="{{ $iconButton }}" aria-label="{{ __('notifications.mark_unread') }}" title="{{ __('notifications.mark_unread') }}" data-toggle-read>
                <flux:icon.envelope variant="micro" />
            </button>
        @endif

        <button type="button" x-on:click="snoozing = ! snoozing" x-on:keydown.escape.stop="snoozing = false" x-bind:aria-expanded="snoozing.toString()" class="{{ $iconButton }}" aria-label="{{ __('notifications.snooze') }}" title="{{ __('notifications.snooze') }}" data-snooze-toggle>
            <flux:icon.clock variant="micro" />
        </button>

        <div
            x-show="snoozing"
            x-cloak
            x-transition:enter="transition duration-150 ease-out"
            x-transition:enter-start="opacity-0 -translate-y-1 scale-95"
            x-transition:leave="transition duration-100 ease-in"
            x-transition:leave-end="opacity-0 scale-95"
            x-on:click.outside="snoozing = false"
            x-on:keydown.escape.stop="snoozing = false"
            class="absolute end-0 top-9 z-30 w-48 origin-top-right rounded-xl border border-line-soft bg-card p-1 text-small shadow-e3 rtl:origin-top-left"
            role="menu"
        >
            <p class="px-2.5 pt-1.5 pb-1 text-caption font-bold text-ink-3">{{ __('notifications.snooze') }}</p>
            @foreach (\App\Notifications\Inbox::SNOOZE_PRESETS as $preset)
                <button type="button" role="menuitem" wire:click="snooze('{{ $notification->id }}', '{{ $preset }}')" x-on:click="snoozing = false" class="flex w-full cursor-pointer items-center gap-2 rounded-lg px-2.5 py-2 text-start text-ink hover:bg-hover focus-visible:bg-hover focus-visible:outline-none" data-snooze-preset="{{ $preset }}">
                    <flux:icon.clock variant="micro" class="size-3.5 text-ink-3" />
                    {{ __("notifications.snooze_presets.{$preset}") }}
                </button>
            @endforeach
        </div>
    </div>
</li>
