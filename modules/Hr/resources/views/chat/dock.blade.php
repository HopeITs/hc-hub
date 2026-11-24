{{--
    The chat dock (Modules\Hr\Livewire\Chat\Dock). Bottom corner on the start side
    (left in English, right in Arabic), just past the sidebar and following it
    as it opens and closes: the "Chats" button sits at the start and tabs line
    up after it, newest nearest. On a phone the bar is not shown; the header
    icon opens the list and an opened chat fills the screen until it is closed.

--}}
<div x-data="chatDock(@js('hc-chat-tabs:'.auth()->id()))"
     x-on:chat-tabs-changed.window="save($event.detail.tabs)"
     x-on:keydown.escape.window="if ($wire.launcherOpen) $wire.toggleLauncher()"
     {{-- Livewire leaves the root's own attributes alone, so the display that
          chat.js sets (hidden on the full chats page) survives re-renders. --}}
     wire:ignore.self
     {{-- Messages chat.js shows itself (upload errors), in the page's language. --}}
     data-texts="{{ json_encode([
         'tooManyFiles' => __('hr_chat.errors.too_many_files', ['max' => config('chat.max_files_per_message')]),
         'tooLarge' => __('hr_chat.errors.too_large', ['name' => ':name', 'max' => (int) (config('chat.max_file_kb') / 1024)]),
         'tooLargeGeneric' => __('hr_chat.errors.upload_too_large'),
         'uploadFailed' => __('hr_chat.errors.upload_failed'),
     ], JSON_UNESCAPED_UNICODE) }}"
     data-testid="chat-dock">

    {{-- The bar, in reading order: launcher, newest tab ... oldest, "+N". It
         starts where the page content starts (sidebar + the content's 2rem
         padding, layouts/app) and rests on the footer's top line while the
         footer is on screen (--chat-dock-lift, chat.js). --}}
    <div class="fixed bottom-[var(--chat-dock-lift,0px)] start-4 z-[45] flex items-end gap-2 md:start-[calc(var(--side-w)+1rem)]">

        {{-- The launcher. --}}
        <button type="button" wire:click="toggleLauncher"
                aria-expanded="{{ $launcherOpen ? 'true' : 'false' }}"
                class="flex h-10 w-32 items-center gap-2 rounded-t-[10px] bg-primary px-3 text-sm font-semibold text-white max-md:hidden"
                data-testid="chat-launcher">
            <svg class="size-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12a8 8 0 0 1-11.5 7.2L4 21l1.8-5.2A8 8 0 1 1 21 12z"/></svg>
            <span class="flex-1 truncate text-start">{{ __('hr_chat.ui.chats') }}</span>
            @if ($unreadChats > 0)
                <span class="min-w-[18px] rounded-full bg-white px-1 text-center text-[11px] font-bold text-brand-text"
                      aria-label="{{ trans_choice('hr_chat.ui.unread_chats', $unreadChats, ['count' => $unreadChats]) }}">{{ $unreadChats }}</span>
            @endif
        </button>

        {{-- Every tab's window, newest first so it sits beside the launcher.
             Those behind "+N" stay mounted but hidden on desktop. --}}
        @foreach ($visible as $tab)
            <livewire:hr.chat.window :conversation-id="$tab['id']" :open="$tab['open']" :in-bar="true"
                                  :mobile-active="$mobileActiveId === $tab['id']"
                                  wire:key="chat-window-{{ $tab['id'] }}" />
        @endforeach
        @foreach ($overflow as $tab)
            <livewire:hr.chat.window :conversation-id="$tab['id']" :open="$tab['open']" :in-bar="false"
                                  :mobile-active="$mobileActiveId === $tab['id']"
                                  wire:key="chat-window-{{ $tab['id'] }}" />
        @endforeach

        @if ($overflow->isNotEmpty())
            <div x-data="{ open: false }" class="relative max-md:hidden" x-on:click.outside="open = false">
                <button type="button" x-on:click="open = ! open"
                        class="flex h-10 min-w-11 items-center justify-center rounded-t-[10px] border border-b-0 border-line bg-surface-1 px-3 text-sm font-semibold text-ink-muted hover:text-ink"
                        aria-label="{{ __('hr_chat.ui.more_chats', ['count' => $overflow->count()]) }}"
                        data-testid="chat-overflow">
                    +{{ $overflow->count() }}
                    @if ($overflow->sum(fn ($t) => $summaries[$t['id']]['unread'] ?? 0) > 0)
                        <span class="ms-1 size-2 rounded-full bg-primary" aria-hidden="true"></span>
                    @endif
                </button>
                <div x-show="open" x-cloak
                     class="absolute bottom-full start-0 mb-1 w-56 rounded-xl border border-line bg-surface-1 p-1 shadow-overlay">
                    @foreach ($overflow as $tab)
                        @php
                            $item = $summaries[$tab['id']];
                        @endphp
                        <button type="button" wire:click="openConversation({{ $tab['id'] }})" x-on:click="open = false"
                                class="flex w-full items-center gap-2 rounded-lg px-2 py-1.5 text-start hover:bg-surface-2">
                            <x-hr::chat.avatar :item="$item" size="size-7" />
                            <span class="min-w-0 flex-1 truncate text-sm text-ink">{{ $item['title'] }}</span>
                            @if ($item['unread'] > 0)
                                <span class="rounded-full px-1.5 text-[11px] font-semibold text-white {{ $item['muted'] ? 'bg-ink-faint' : 'bg-primary' }}">{{ $item['unread'] }}</span>
                            @endif
                        </button>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    {{-- The chats list / new-chat picker: a panel above the launcher on
         desktop, the whole screen on a phone. --}}
    @if ($launcherOpen)
        <section role="dialog" aria-modal="false" aria-labelledby="chat-launcher-title"
                 class="fixed z-[9600] flex flex-col overflow-hidden border border-line bg-surface-1 shadow-overlay
                        max-md:inset-0
                        md:bottom-[calc(2.75rem+var(--chat-dock-lift,0px))] md:start-[calc(var(--side-w)+1rem)] md:h-[min(30rem,calc(100dvh-8rem))] md:w-80 md:rounded-xl"
                 data-testid="chat-launcher-panel">

            @if ($launcherMode === 'chats')
                <header class="flex items-center gap-2 border-b border-line px-3 py-2">
                    <h2 id="chat-launcher-title" class="flex-1 text-sm font-semibold text-ink">{{ __('hr_chat.ui.chats') }}</h2>
                    <button type="button" wire:click="showSettings"
                            class="grid size-9 place-items-center rounded-full text-ink-muted hover:bg-surface-2 hover:text-ink"
                            aria-label="{{ __('hr_chat.ui.settings') }}" title="{{ __('hr_chat.ui.settings') }}" data-testid="chat-settings">
                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.8l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.8-.3 1.7 1.7 0 0 0-1 1.5V21a2 2 0 1 1-4 0v-.1a1.7 1.7 0 0 0-1.1-1.5 1.7 1.7 0 0 0-1.8.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0 .3-1.8 1.7 1.7 0 0 0-1.5-1H3a2 2 0 1 1 0-4h.1a1.7 1.7 0 0 0 1.5-1.1 1.7 1.7 0 0 0-.3-1.8l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.7 1.7 0 0 0 1.8.3H9a1.7 1.7 0 0 0 1-1.5V3a2 2 0 1 1 4 0v.1a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.8-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0-.3 1.8V9a1.7 1.7 0 0 0 1.5 1H21a2 2 0 1 1 0 4h-.1a1.7 1.7 0 0 0-1.5 1z"/></svg>
                    </button>
                    {{-- The full chats page, like a tab's maximise (no pencil: searching a
                         name already offers colleagues to start a chat with). --}}
                    <button type="button" x-on:click="maximize(@js(route('hr.chat')))"
                            class="grid size-9 place-items-center rounded-full text-ink-muted hover:bg-surface-2 hover:text-ink max-md:hidden"
                            aria-label="{{ __('hr_chat.ui.maximize') }}" title="{{ __('hr_chat.ui.maximize') }}" data-testid="chat-launcher-maximize">
                        <svg class="size-4 rtl:-scale-x-100" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 3h6v6M9 21H3v-6M21 3l-7 7M3 21l7-7"/></svg>
                    </button>
                    <button type="button" wire:click="toggleLauncher"
                            class="grid size-9 place-items-center rounded-full text-ink-muted hover:bg-surface-2 hover:text-ink"
                            aria-label="{{ __('hr_chat.ui.close') }}" title="{{ __('hr_chat.ui.close') }}">
                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>
                    </button>
                </header>

                <div class="px-3 py-2">
                    <input type="search" wire:model.live.debounce.300ms="search"
                           placeholder="{{ __('hr_chat.ui.search_chats') }}" aria-label="{{ __('hr_chat.ui.search_chats') }}"
                           class="border border-line bg-surface-1 px-3 text-ink focus:outline-none focus:ring-1 focus:ring-primary h-9 w-full rounded-full text-sm">
                </div>

                @include('hr::chat.partials.list', ['openAction' => 'openConversation', 'startAction' => 'startWith', 'emptyAction' => 'showPeople'])
            @elseif ($launcherMode === 'settings')
                <header class="flex items-center gap-2 border-b border-line px-3 py-2">
                    <button type="button" wire:click="showChats"
                            class="grid size-9 place-items-center rounded-full text-ink-muted hover:bg-surface-2 hover:text-ink"
                            aria-label="{{ __('hr_chat.ui.back') }}" title="{{ __('hr_chat.ui.back') }}">
                        <svg class="size-4 rtl:-scale-x-100" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
                    </button>
                    <h2 id="chat-launcher-title" class="flex-1 text-sm font-semibold text-ink">{{ __('hr_chat.ui.settings') }}</h2>
                </header>

                <div class="px-3 py-3">
                    <button type="button" wire:click="toggleSound" role="switch" aria-checked="{{ $sound ? 'true' : 'false' }}"
                            class="flex w-full items-center gap-3 rounded-lg px-2 py-2 text-start hover:bg-surface-2"
                            data-testid="chat-sound-toggle">
                        <svg class="size-5 shrink-0 text-ink-muted" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M11 5 6 9H2v6h4l5 4z"/>@if ($sound)<path d="M15.5 8.5a5 5 0 0 1 0 7M19 5a10 10 0 0 1 0 14"/>@else<path d="m22 9-6 6M16 9l6 6"/>@endif</svg>
                        <span class="flex-1 text-sm font-semibold text-ink">{{ __('hr_chat.ui.sound') }}</span>
                        <span @class(['relative h-5 w-9 shrink-0 rounded-full transition-colors', 'bg-primary' => $sound, 'bg-line' => ! $sound])>
                            <span @class(['absolute top-0.5 size-4 rounded-full bg-white transition-all', 'end-0.5' => $sound, 'start-0.5' => ! $sound])></span>
                        </span>
                    </button>
                    <p class="mt-2 px-2 text-[12px] leading-relaxed text-ink-faint">{{ __('hr_chat.ui.sound_hint') }}</p>
                </div>
            @else
                <header class="flex items-center gap-2 border-b border-line px-3 py-2">
                    <button type="button" wire:click="showChats"
                            class="grid size-9 place-items-center rounded-full text-ink-muted hover:bg-surface-2 hover:text-ink"
                            aria-label="{{ __('hr_chat.ui.back') }}" title="{{ __('hr_chat.ui.back') }}">
                        <svg class="size-4 rtl:-scale-x-100" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
                    </button>
                    <h2 id="chat-launcher-title" class="flex-1 text-sm font-semibold text-ink">{{ __('hr_chat.ui.new_chat') }}</h2>
                </header>

                <div class="px-3 py-2">
                    <input type="search" wire:model.live.debounce.300ms="peopleSearch" x-init="$el.focus()"
                           placeholder="{{ __('hr_chat.ui.search_people') }}" aria-label="{{ __('hr_chat.ui.search_people') }}"
                           class="border border-line bg-surface-1 px-3 text-ink focus:outline-none focus:ring-1 focus:ring-primary h-9 w-full rounded-full text-sm">
                    @error('people')
                        <p class="mt-1 text-sm text-brand-text" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                <div class="min-h-0 flex-1 overflow-y-auto px-1 pb-2" data-csb-exclude>
                    @forelse ($people as $person)
                        <button type="button" wire:click="startWith({{ $person['id'] }})" wire:key="chat-person-{{ $person['id'] }}"
                                class="flex w-full items-center gap-2.5 rounded-lg px-2 py-2 text-start hover:bg-surface-2"
                                data-testid="chat-person">
                            <img src="{{ $person['avatar'] }}" alt="" class="size-9 shrink-0 rounded-full object-cover">
                            <span class="min-w-0 flex-1 truncate text-sm font-semibold text-ink">{{ $person['name'] }}</span>
                        </button>
                    @empty
                        <p class="px-6 py-10 text-center text-sm text-ink-muted">{{ __('hr_chat.ui.no_people') }}</p>
                    @endforelse
                </div>
            @endif
        </section>
    @endif
</div>
