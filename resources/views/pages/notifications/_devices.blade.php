{{-- Devices and app: installing the app, this browser's push subscription, my devices and a real test. --}}
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

{{-- This device: permission and this browser's own push subscription (pushDevice, push-notifications.js). --}}
<section
    id="this-device"
    aria-labelledby="this-device-heading"
    class="{{ $section }} scroll-mt-24"
    x-data="pushDevice"
    x-on:push-device-removed.window="if ($event.detail.id === subscriptionId) { disable() }"
    data-push-device
    data-local-title="{{ __('notification_settings.device.local_title') }}"
    data-local-body="{{ __('notification_settings.device.local_body') }}"
    data-push-configured="{{ $pushConfigured ? 'yes' : 'no' }}"
>
    <h2 id="this-device-heading" class="text-base font-bold">{{ __('notification_settings.device.title') }}</h2>
    <p class="{{ $muted_text }}">{{ __('notification_settings.device.lead') }}</p>

    @if (! $pushConfigured)
        <p class="text-sm font-semibold text-status-critical" data-push-not-configured>{{ __('notification_settings.device.not_configured') }}</p>
    @else
        <p class="text-sm font-semibold" role="status" data-push-device-state>
            <span x-show="subscribed && known" x-cloak class="text-status-good">{{ __('notification_settings.device.state.on') }}</span>
            <span x-show="!(subscribed && known) && !blocker()">{{ __('notification_settings.device.state.off') }}</span>
            @foreach (['denied', 'unsupported', 'insecure', 'ios_install'] as $blocker)
                <span x-show="!(subscribed && known) && blocker() === '{{ $blocker }}'" x-cloak class="text-status-critical">{{ __('notification_settings.device.state.'.$blocker) }}</span>
            @endforeach
            <span x-show="standalone" x-cloak class="{{ $muted_text }} font-normal">· {{ __('notification_settings.devices.installed') }}</span>
        </p>

        <p x-show="subscribed && !known" x-cloak class="{{ $muted_text }}" data-push-device-unknown>{{ __('notification_settings.device.unknown') }}</p>

        <p x-show="iosNeedsInstall" x-cloak class="{{ $muted_text }}">
            {{ __('notification_settings.device.ios_install') }}
            <a href="#desktop-app" class="font-semibold underline">{{ __('notification_settings.device.ios_install_link') }}</a>
        </p>

        <div class="flex flex-wrap items-center gap-3">
            <span x-show="!(subscribed && known)">
                <x-ui.button tone="primary" size="sm" icon="bell-alert" x-on:click="enable()" x-bind:disabled="busy || ['unsupported', 'insecure', 'ios_install'].includes(blocker())" data-test="push-enable">{{ __('notification_settings.device.enable') }}</x-ui.button>
            </span>
            <span x-show="subscribed && known" x-cloak>
                <x-ui.button tone="secondary" size="sm" icon="bell-slash" x-on:click="disable()" x-bind:disabled="busy" data-test="push-disable">{{ __('notification_settings.device.disable') }}</x-ui.button>
            </span>
            <span x-show="permission === 'granted'" x-cloak>
                <x-ui.button tone="ghost" size="sm" x-on:click="localTest($root.dataset.localTitle, $root.dataset.localBody)" data-test="push-local-test">{{ __('notification_settings.device.local_test') }}</x-ui.button>
            </span>
            <span x-show="busy" x-cloak class="{{ $muted_text }}" role="status">{{ __('notification_settings.device.working') }}</span>
        </div>

        <div role="alert">
            @foreach (['denied', 'unsupported', 'not_configured', 'insecure', 'network', 'failed', 'ios_install'] as $code)
                <p x-show="error === '{{ $code }}'" x-cloak class="text-sm text-status-critical">{{ __('notification_settings.device.errors.'.$code) }}</p>
            @endforeach
        </div>

        <div role="status">
            @foreach (['shown', 'denied', 'unsupported', 'failed'] as $result)
                <p x-show="testResult === '{{ $result }}'" x-cloak class="{{ $muted_text }}">{{ __('notification_settings.device.local_result.'.$result) }}</p>
            @endforeach
        </div>

        <p class="{{ $muted_text }}">{{ __('notification_settings.device.os_note') }}</p>
    @endif
</section>

{{-- My devices: always the signed-in person's own push subscriptions. --}}
<section aria-labelledby="devices-heading" class="{{ $section }}" x-data="pushDevice" data-push-devices>
    <h2 id="devices-heading" class="text-base font-bold">{{ __('notification_settings.devices.title') }}</h2>
    <p class="{{ $muted_text }}">{{ __('notification_settings.devices.lead') }}</p>

    <ul class="divide-y divide-secondary-soft dark:divide-line">
        @forelse ($this->devices as $device)
            @php
                $name = $deviceName($device);
                $state = match (true) {
                    $device->isExpired() => 'expired',
                    ! $device->enabled => 'off',
                    $device->failure_count > 0 => 'failing',
                    default => 'active',
                };
            @endphp
            <li class="grid gap-2 py-3" wire:key="device-{{ $device->id }}" x-data="{ editing: false, name: @js((string) $device->device_name) }" data-device="{{ $device->id }}">
                <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
                    <span class="text-sm font-semibold" x-show="!editing">{{ $name }}</span>
                    <span x-show="subscriptionId === {{ $device->id }}" x-cloak class="text-sm font-semibold text-status-good">{{ __('notification_settings.devices.this_device') }}</span>
                    <span class="{{ $muted_text }}">
                        {{ __('notification_settings.devices.type.'.(in_array($device->device_type, \App\Models\PushSubscription::TYPES, true) ? $device->device_type : 'desktop')) }}
                        @if ($device->standalone) · {{ __('notification_settings.devices.installed') }} @endif
                        · {{ __('notification_settings.devices.state.'.$state) }}
                        · {{ $device->last_seen_at ? __('notification_settings.devices.last_active', ['time' => $device->last_seen_at->diffForHumans()]) : __('notification_settings.devices.never') }}
                    </span>
                </div>

                <div x-show="editing" x-cloak class="flex flex-wrap items-end gap-2">
                    <flux:input size="sm" x-model="name" maxlength="80" :aria-label="__('notification_settings.devices.name')" x-on:keydown.enter.prevent="$wire.renameDevice({{ $device->id }}, name).then((saved) => { if (saved) { editing = false } })" x-on:keydown.escape="editing = false" />
                    <x-ui.button tone="primary" size="sm" x-on:click="$wire.renameDevice({{ $device->id }}, name).then((saved) => { if (saved) { editing = false } })">{{ __('notification_settings.devices.save') }}</x-ui.button>
                    <x-ui.button tone="ghost" size="sm" x-on:click="editing = false">{{ __('common.cancel') }}</x-ui.button>
                </div>
                @error('device.'.$device->id) <p class="text-sm text-status-critical" role="alert">{{ $message }}</p> @enderror

                <div class="flex flex-wrap items-center gap-x-5 gap-y-2">
                    <label class="inline-flex items-center gap-2 text-sm">
                        <input type="checkbox" wire:change="setDeviceEnabled({{ $device->id }}, $event.target.checked)" @checked($device->enabled) aria-label="{{ __('notification_settings.devices.enabled_label', ['name' => $name]) }}" class="size-4 accent-primary" data-device-enabled="{{ $device->id }}" />
                        {{ __('notification_settings.devices.enabled') }}
                    </label>
                    <label class="inline-flex items-center gap-2 text-sm">
                        <input type="checkbox" wire:change="setDeviceUrgentOnly({{ $device->id }}, $event.target.checked)" @checked($device->urgent_only) aria-label="{{ __('notification_settings.devices.urgent_only_label', ['name' => $name]) }}" class="size-4 accent-primary" data-device-urgent-only="{{ $device->id }}" />
                        {{ __('notification_settings.devices.urgent_only') }}
                    </label>
                    <span class="flex flex-wrap items-center gap-1">
                        <x-ui.button tone="ghost" size="sm" icon="pencil" x-on:click="editing = true" :aria-label="__('notification_settings.devices.rename_label', ['name' => $name])">{{ __('notification_settings.devices.rename') }}</x-ui.button>
                        <x-ui.button tone="ghost" size="sm" icon="paper-airplane" wire:click="testDevice({{ $device->id }})" :disabled="! $pushConfigured" :aria-label="__('notification_settings.devices.test_label', ['name' => $name])">{{ __('notification_settings.devices.test') }}</x-ui.button>
                        <x-ui.button tone="ghost" size="sm" icon="trash" wire:click="confirmRemoveDevice({{ $device->id }})" :aria-label="__('notification_settings.devices.remove_label', ['name' => $name])" data-device-remove="{{ $device->id }}">{{ __('notification_settings.devices.remove') }}</x-ui.button>
                    </span>
                </div>
            </li>
        @empty
            <li class="py-2.5 {{ $muted_text }}" data-devices-empty>{{ __('notification_settings.devices.empty') }}</li>
        @endforelse
    </ul>
    @if ($this->devices->isNotEmpty())
        <p class="{{ $muted_text }}">{{ __('notification_settings.devices.urgent_only_help') }}</p>
    @endif
</section>

{{-- A real test through the real path. --}}
<section aria-labelledby="test-send-heading" class="{{ $section }}" x-data="pushDevice" data-test-send>
    <h2 id="test-send-heading" class="text-base font-bold">{{ __('notification_settings.test.title') }}</h2>
    <p class="{{ $muted_text }}">{{ __('notification_settings.test.lead') }}</p>

    <div class="flex flex-wrap items-end gap-3">
        <div class="min-w-56">
            <x-ui.form-select wire:model="testTarget" :label="__('notification_settings.test.target')" data-test="test-target">
                @foreach (\App\Notifications\Delivery\NotificationTester::TARGETS as $target)
                    <option value="{{ $target }}">{{ __('notification_settings.test.targets.'.$target) }}</option>
                @endforeach
            </x-ui.form-select>
        </div>
        <x-ui.button tone="primary" size="sm" icon="paper-airplane" x-on:click="$wire.sendTest(subscriptionId)" wire:loading.attr="disabled" wire:target="sendTest" data-test="send-test">{{ __('notification_settings.test.send') }}</x-ui.button>
    </div>

    <div role="status" aria-live="polite" data-test-result>
        @if ($testResult !== null)
            <p @class(['text-sm font-semibold', 'text-status-good' => $testResult['ok'], 'text-status-critical' => ! $testResult['ok']])>{{ $testResult['message'] }}</p>
        @endif
    </div>
</section>
