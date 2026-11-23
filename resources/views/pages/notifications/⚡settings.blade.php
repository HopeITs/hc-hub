<?php

use App\Models\NotificationMute;
use App\Models\Permission;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::desk'), Title('titles.notification_settings')] class extends Component {
    public bool $popups = false;

    /** @var list<string> */
    public array $muted = [];

    public function mount(): void
    {
        $this->popups = auth()->user()->notification_popups;
        $this->muted = $this->mutedModules();
    }

    public function updatedPopups(): void
    {
        auth()->user()->forceFill(['notification_popups' => $this->popups])->save();

        Flux::toast(variant: 'success', text: $this->popups ? __('notifications.popups_on') : __('notifications.popups_off'));
    }

    /**
     * Modules I receive notifications about, and so may mute.
     *
     * @return list<string>
     */
    #[Computed]
    public function mutableModules(): array
    {
        $user = auth()->user();

        return array_values(array_filter(array_keys(config('notifications.modules')), function (string $module) use ($user): bool {
            if (in_array($module, config('notifications.open_modules', []), true)) {
                return true;
            }

            $permission = config("notifications.view_permissions.{$module}", "{$module}.view");

            return Permission::query()->where('name', $permission)->exists() && $user->can($permission);
        }));
    }

    public function toggleMute(string $module): void
    {
        if (! in_array($module, $this->mutableModules, true)) {
            return;
        }

        $mute = NotificationMute::query()->where('user_id', auth()->id())->where('module', $module)->first();

        $muting = $mute === null;

        $muting
            ? NotificationMute::query()->create(['user_id' => auth()->id(), 'module' => $module])
            : $mute->delete();

        $this->muted = $this->mutedModules();

        Flux::toast(
            variant: 'success',
            text: __($muting ? 'notifications.module_muted' : 'notifications.module_unmuted', [
                'module' => app(\App\Notifications\NotificationPresenter::class)->moduleLabel($module),
            ]),
        );
    }

    /**
     * @return list<string>
     */
    private function mutedModules(): array
    {
        return NotificationMute::query()->where('user_id', auth()->id())->orderBy('module')->pluck('module')->all();
    }
}; ?>

<x-desk.register class="@container/directory" heading-id="notification-settings-heading" :title="__('notifications.settings_link')">
    

    <div class="grid max-w-2xl gap-10">
        {{-- The installable app: its own window, Start-menu entry and branded notifications. --}}
        @php
            $installPlatforms = ['desktop_chromium', 'desktop_safari', 'android', 'ios', 'ios_non_safari', 'firefox_android', 'firefox', 'other'];
        @endphp

        <section
            id="desktop-app"
            aria-labelledby="desktop-app-heading"
            class="grid scroll-mt-24 gap-3"
            x-data="{
                state: window.hcApp?.state() ?? 'unavailable',
                platform: window.hcApp?.platform() ?? 'other',
                init() {
                    window.addEventListener('hc-app-install-change', (event) => (this.state = event.detail));
                },
            }"
            data-desktop-app
        >
            <div class="flex items-start gap-4">
                <img src="{{ asset('app-icons/icon-192.png') }}" alt="" width="48" height="48" class="size-12 shrink-0 rounded-full" data-no-dim />
                <div class="min-w-0">
                    <h2 id="desktop-app-heading" class="text-base font-bold">{{ __('notifications.app.heading') }}</h2>
                    <p class="text-sm text-secondary-medium dark:text-ink-muted">{{ __('notifications.app.help') }}</p>
                </div>
            </div>

            <div class="grid gap-4 ps-16">
                <p x-show="state === 'installed'" x-cloak class="inline-flex items-center gap-2 text-sm font-semibold text-status-good" data-app-installed>
                    <flux:icon.check-circle variant="mini" />
                    {{ __('notifications.app.installed') }}
                </p>

                <div x-show="state === 'available'" x-cloak>
                    <x-ui.button tone="primary" size="sm" icon="arrow-down-tray" x-on:click="$flux.modal('install-app').show()" aria-haspopup="dialog" data-test="install-app-button">
                        {{ __('notifications.app.install') }}
                    </x-ui.button>
                </div>

                {{-- No browser prompt: the steps for this device. --}}
                <div x-show="state === 'unavailable'" class="grid gap-1" data-app-install-hint>
                    <p class="text-sm font-semibold">{{ __('notifications.app.this_device') }}</p>
                    @foreach ($installPlatforms as $installPlatform)
                        <div x-show="platform === '{{ $installPlatform }}'" x-cloak data-install-steps="{{ $installPlatform }}">
                            @include('pages.notifications._install-steps', ['platform' => $installPlatform])
                        </div>
                    @endforeach
                </div>

                <details x-show="state !== 'installed'" x-cloak class="group grid gap-3" data-install-other-devices>
                    <summary class="flex cursor-pointer list-none items-center gap-2 text-sm font-semibold">
                        <flux:icon.chevron-right variant="micro" class="transition-transform group-open:rotate-90 rtl:-scale-x-100" />
                        {{ __('notifications.app.other_devices') }}
                    </summary>
                    <div class="mt-3 grid gap-5">
                        @foreach ($installPlatforms as $installPlatform)
                            <div x-show="platform !== '{{ $installPlatform }}'" x-cloak data-install-steps="{{ $installPlatform }}">
                                @include('pages.notifications._install-steps', ['platform' => $installPlatform])
                            </div>
                        @endforeach
                    </div>
                </details>
            </div>
        </section>
        {{-- Desktop popups for important and critical notifications. --}}
        <section
            aria-labelledby="desktop-popups-heading"
            class="grid gap-2 border-t border-secondary-soft pt-8 dark:border-line"
            x-data="{
                permission: 'Notification' in window ? Notification.permission : 'unsupported',
                testing: false,
                async ask() {
                    if (this.permission === 'default') {
                        this.permission = await Notification.requestPermission();
                    }
                },
                async testPopup() {
                    await this.ask();

                    if (this.permission !== 'granted') {
                        return;
                    }

                    this.testing = true;
                    window.setTimeout(async () => {
                        await window.hcShowDesktopPopup?.({
                            id: 'test-' + Date.now(),
                            module_label: @js(__('notifications.modules.system')),
                            text: @js(__('notifications.test_message')),
                            priority: 'important',
                            url: @js(route('notification-settings.edit')),
                            read_url: null,
                            icon: @js(app(\App\Notifications\NotificationPresenter::class)->desktopIcon('system', 'important')),
                            image: @js(app(\App\Notifications\NotificationPresenter::class)->desktopBanner('system', 'important', app()->getLocale())),
                            created_at: new Date().toISOString(),
                        });
                        this.testing = false;
                    }, 5000);
                },
            }"
            data-popup-preference
        >
            <h2 id="desktop-popups-heading" class="sr-only">{{ __('notifications.popups_label') }}</h2>
            <label class="flex items-start gap-3">
                <input
                    type="checkbox"
                    wire:model.live="popups"
                    x-on:change="document.body.dataset.notificationPopups = $event.target.checked ? 'on' : 'off'; if ($event.target.checked) { ask() }"
                    class="mt-1 size-4 accent-primary"
                    data-test="notification-popups-toggle"
                />
                <span>
                    <span class="block text-sm font-semibold">{{ __('notifications.popups_label') }}</span>
                    <span class="block text-sm text-secondary-medium dark:text-ink-muted">{{ __('notifications.popups_help') }}</span>
                </span>
            </label>
            <p x-show="permission === 'denied'" x-cloak class="ps-7 text-sm text-status-critical">{{ __('notifications.popups_blocked') }}</p>
            <p x-show="permission === 'unsupported'" x-cloak class="ps-7 text-sm text-secondary-medium dark:text-ink-muted">{{ __('notifications.popups_unsupported') }}</p>

            <div x-show="permission !== 'unsupported' && permission !== 'denied'" class="mt-2 flex flex-wrap items-center gap-3 ps-7">
                <x-ui.button tone="ghost" size="sm" icon="bell-alert" x-on:click="testPopup()" x-bind:disabled="testing" data-test="notification-popup-test">{{ __('notifications.popup_test') }}</x-ui.button>
                <span x-show="testing" x-cloak class="text-sm text-secondary-medium dark:text-ink-muted" role="status">{{ __('notifications.popup_test_waiting') }}</span>
            </div>
        </section>

        {{-- Module mutes: team-wide updates only; anything addressed to me always arrives. --}}
        <section aria-labelledby="module-mutes-heading" class="grid gap-2 border-t border-secondary-soft pt-8 dark:border-line" data-module-mutes>
            <h2 id="module-mutes-heading" class="text-base font-bold">{{ __('notifications.mutes_label') }}</h2>
            <p class="text-sm text-secondary-medium dark:text-ink-muted">{{ __('notifications.mutes_help') }}</p>

            <ul class="mt-2 divide-y divide-secondary-soft dark:divide-line">
                @forelse ($this->mutableModules as $module)
                    <li class="flex items-center justify-between gap-3 py-2.5" wire:key="mute-{{ $module }}">
                        <span @class(['text-sm font-medium', 'text-secondary-light line-through' => in_array($module, $muted, true)])>{{ app(\App\Notifications\NotificationPresenter::class)->moduleLabel($module) }}</span>
                        <label class="inline-flex cursor-pointer items-center gap-2 text-sm text-secondary-medium dark:text-ink-muted">
                            <input type="checkbox" wire:click="toggleMute('{{ $module }}')" @checked(in_array($module, $muted, true)) aria-label="{{ __('notifications.mute_toggle', ['module' => app(\App\Notifications\NotificationPresenter::class)->moduleLabel($module)]) }}" class="size-4 accent-primary" data-mute-toggle="{{ $module }}" />
                            {{ __('notifications.muted') }}
                        </label>
                    </li>
                @empty
                    <li class="py-2.5 text-sm text-secondary-medium dark:text-ink-muted" data-mutes-empty>{{ __('notifications.mutes_empty') }}</li>
                @endforelse
            </ul>
        </section>
    </div>
</x-desk.register>
