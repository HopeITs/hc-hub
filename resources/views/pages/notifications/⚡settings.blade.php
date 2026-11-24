<?php

use App\Enums\NotificationChannel;
use App\Models\NotificationMute;
use App\Models\NotificationPreference;
use App\Models\Permission;
use App\Models\PushSubscription;
use App\Notifications\Delivery\NotificationTester;
use App\Notifications\Delivery\ReceivableModules;
use App\Notifications\Delivery\WebPushSender;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Notification settings. Every control saves on its own as soon as it
 * changes: the value is validated, written to the person's
 * NotificationPreference and acknowledged in a polite live region. A value
 * that is not allowed is put back to what was saved, so the page never shows
 * a setting that is not in force.
 *
 * The device list and every device action go through
 * auth()->user()->pushSubscriptions(), so an id from the browser can only
 * ever reach the signed-in person's own devices.
 *
 * What only the browser knows (permission, this browser's subscription) is
 * the Alpine component `pushDevice` (resources/js/push-notifications.js).
 *
 * Module mutes and the module x channel table are two different things and
 * both stay: a mute stops a module's team-wide updates everywhere, the table
 * only chooses which channel a module's notifications use.
 */
new #[Layout('layouts::desk'), Title('titles.notification_settings')] class extends Component {
    /** Plain switches of notification_preferences, by column. */
    public const array BOOLEANS = ['enabled', 'in_app', 'desktop', 'mobile', 'email', 'sound', 'previews', 'quiet_hours', 'urgent_bypass'];

    public const array FIELDS = [...self::BOOLEANS, 'frequency', 'digest_time', 'quiet_start', 'quiet_end', 'timezone'];

    /** Test sends per person per minute: each one is a real push or email. */
    private const int TEST_LIMIT = 5;

    public bool $popups = false;

    /** @var list<string> */
    public array $muted = [];

    /** @var array<string, mixed> the columns in FIELDS */
    public array $prefs = [];

    /** @var list<int|string> Carbon dayOfWeek, 0 = Sunday */
    public array $workingDays = [];

    /** @var array<string, array{in_app: bool, desktop: bool, mobile: bool, email: bool}> module => channel => on */
    public array $matrix = [];

    public string $testTarget = 'devices';

    /** @var array{ok: bool, message: string}|null */
    public ?array $testResult = null;

    /** '' | 'saved' | 'error': what the live region last announced. */
    public string $saveState = '';

    /** Bumped on every outcome, so the live region announces a repeated "Saved" again. */
    public int $saveTick = 0;

    public bool $showRemoveDevice = false;

    public ?int $removingDeviceId = null;

    public function mount(): void
    {
        $this->popups = auth()->user()->notification_popups;
        $this->muted = $this->mutedModules();
        $this->load(NotificationPreference::for(auth()->user()));
    }

    public function updatedPrefs(mixed $value, string $key): void
    {
        if (! in_array($key, self::FIELDS, true)) {
            $this->revert();

            return;
        }

        $validator = Validator::make(['value' => $value], ['value' => $this->ruleFor($key)], [], ['value' => __('notification_settings.fields.'.$key)]);

        if ($validator->fails()) {
            $this->fail('prefs.'.$key, $validator->errors()->first('value'));

            return;
        }

        $value = match (true) {
            in_array($key, self::BOOLEANS, true) => (bool) $value,
            $key === 'timezone' => blank($value) ? null : (string) $value,
            default => (string) $value,
        };

        $preference = $this->persist(function (NotificationPreference $preference) use ($key, $value): void {
            $preference->{$key} = $value;
        });

        if (in_array($key, ['sound', 'enabled'], true)) {
            $this->dispatch('notif-sound-changed', on: $preference->enabled && $preference->sound);
        }
    }

    public function updatedWorkingDays(): void
    {
        $validator = Validator::make(['days' => $this->workingDays], [
            'days' => ['required', 'array', 'min:1', 'max:7'],
            'days.*' => ['integer', 'between:0,6', 'distinct'],
        ], [
            'days.required' => __('notification_settings.schedule.days_required'),
            'days.min' => __('notification_settings.schedule.days_required'),
        ], ['days' => __('notification_settings.fields.working_days'), 'days.*' => __('notification_settings.fields.working_days')]);

        if ($validator->fails()) {
            $this->fail('workingDays', $validator->errors()->first());

            return;
        }

        $days = array_map(intval(...), $this->workingDays);
        sort($days);

        $this->persist(function (NotificationPreference $preference) use ($days): void {
            $preference->working_days = count($days) === 7 ? null : $days;
        });
    }

    /**
     * One cell of the module x channel table.
     */
    public function setModuleChannel(string $module, string $channel, bool $on): void
    {
        $channel = NotificationChannel::tryFrom($channel);

        if ($channel === null || ! in_array($module, $this->matrixModules, true)) {
            $this->fail('matrix', __('notification_settings.status.invalid'));

            return;
        }

        $this->persist(function (NotificationPreference $preference) use ($module, $channel, $on): void {
            $preference->setCategoryChannel($module, $channel, $on);
        });
    }

    /**
     * The table's rows: the modules I receive, then "system" (tests and
     * messages the platform raises itself).
     *
     * @return list<string>
     */
    #[Computed]
    public function matrixModules(): array
    {
        return [...app(ReceivableModules::class)->for(auth()->user()), NotificationPreference::SYSTEM];
    }

    /**
     * @return Collection<int, PushSubscription>
     */
    #[Computed]
    public function devices(): Collection
    {
        return auth()->user()->pushSubscriptions()->latest('last_seen_at')->latest('id')->get();
    }

    /**
     * Whether the name was saved (the inline editor closes on true).
     */
    public function renameDevice(int $id, string $name): bool
    {
        $device = $this->device($id);
        $name = trim($name);

        $validator = Validator::make(['name' => $name], ['name' => ['nullable', 'string', 'max:80']], [], ['name' => __('notification_settings.fields.device_name')]);

        if ($validator->fails()) {
            $this->resetErrorBag();
            $this->addError('device.'.$id, $validator->errors()->first('name'));
            $this->announce('error');

            return false;
        }

        $device->update(['device_name' => $name === '' ? null : $name]);
        $this->deviceSaved();

        return true;
    }

    public function setDeviceEnabled(int $id, bool $on): void
    {
        $this->device($id)->update(['enabled' => $on]);
        $this->deviceSaved();
    }

    public function setDeviceUrgentOnly(int $id, bool $on): void
    {
        $this->device($id)->update(['urgent_only' => $on]);
        $this->deviceSaved();
    }

    public function confirmRemoveDevice(int $id): void
    {
        $this->removingDeviceId = $this->device($id)->id;
        $this->showRemoveDevice = true;
    }

    public function cancelRemoveDevice(): void
    {
        $this->showRemoveDevice = false;
        $this->removingDeviceId = null;
    }

    public function removeDevice(): void
    {
        $id = $this->removingDeviceId;
        $this->cancelRemoveDevice();

        if ($id === null) {
            return;
        }

        $this->device($id)->delete();
        $this->deviceSaved();

        $this->dispatch('push-device-removed', id: $id);
    }

    /**
     * Send a real test. $subscriptionId is this browser's subscription (from
     * pushDevice) for the "device" target, or the device a row's Test button
     * names; either way it must be one of the person's own.
     */
    public function sendTest(?int $subscriptionId = null, ?string $target = null): void
    {
        $target ??= $this->testTarget;
        $user = auth()->user();

        if (! in_array($target, NotificationTester::TARGETS, true)) {
            $this->testResult = ['ok' => false, 'message' => __('notification_settings.test.invalid_target')];

            return;
        }

        if ($target === 'device') {
            if ($subscriptionId === null) {
                $this->testResult = ['ok' => false, 'message' => __('notification_settings.test.no_device')];

                return;
            }

            $this->device($subscriptionId);
        } else {
            $subscriptionId = null;
        }

        $key = 'notification-test:'.$user->getKey();

        if (RateLimiter::tooManyAttempts($key, self::TEST_LIMIT)) {
            $this->testResult = ['ok' => false, 'message' => __('notification_settings.test.throttled', ['seconds' => RateLimiter::availableIn($key)])];

            return;
        }

        RateLimiter::hit($key, 60);

        try {
            $result = app(NotificationTester::class)->send($user, $target, $subscriptionId);
        } catch (Throwable $exception) {
            report($exception);
            $result = ['ok' => false, 'message' => __('notification_settings.test.failed')];
        }

        $this->testResult = ['ok' => (bool) $result['ok'], 'message' => (string) $result['message']];

        unset($this->devices);
    }

    public function testDevice(int $id): void
    {
        $this->sendTest($id, 'device');
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

    private function load(NotificationPreference $preference): void
    {
        foreach (self::FIELDS as $field) {
            $this->prefs[$field] = in_array($field, self::BOOLEANS, true)
                ? (bool) $preference->{$field}
                : (string) ($preference->{$field} ?? '');
        }

        $this->workingDays = $preference->workingDays();
        $this->matrix = $preference->matrix($this->matrixModules);
    }

    /**
     * @return list<mixed>
     */
    private function ruleFor(string $key): array
    {
        return match (true) {
            in_array($key, self::BOOLEANS, true) => ['required', 'boolean'],
            $key === 'frequency' => ['required', 'string', Rule::in(NotificationPreference::FREQUENCIES)],
            $key === 'timezone' => ['nullable', 'string', Rule::in(DateTimeZone::listIdentifiers())],
            default => ['required', 'string', 'regex:/^([01]\d|2[0-3]):[0-5]\d$/'],
        };
    }

    /**
     * Write one change, then show every value as it was stored.
     *
     * @param  callable(NotificationPreference): void  $change
     */
    private function persist(callable $change): NotificationPreference
    {
        $preference = NotificationPreference::for(auth()->user());
        $change($preference);
        $preference->save();

        $this->resetErrorBag();
        $this->load($preference);
        $this->announce('saved');

        return $preference;
    }

    private function fail(string $field, string $message): void
    {
        $this->revert();
        $this->resetErrorBag();
        $this->addError($field, $message);
        $this->announce('error');
    }

    private function revert(): void
    {
        $this->load(NotificationPreference::for(auth()->user()));
    }

    private function device(int $id): PushSubscription
    {
        return auth()->user()->pushSubscriptions()->findOrFail($id);
    }

    private function deviceSaved(): void
    {
        unset($this->devices);
        $this->resetErrorBag();
        $this->announce('saved');
        $this->dispatch('push-devices-updated');
    }

    private function announce(string $state): void
    {
        $this->saveState = $state;
        $this->saveTick++;
    }
}; ?>

@php
    $pushConfigured = WebPushSender::configured();
    $presenter = app(\App\Notifications\NotificationPresenter::class);
    $channels = NotificationChannel::cases();
    $pushChannels = [NotificationChannel::Desktop, NotificationChannel::Mobile];
    $paused = ! $prefs['enabled'];
    $weekOrder = app()->getLocale() === 'ar' ? [6, 0, 1, 2, 3, 4, 5] : [0, 1, 2, 3, 4, 5, 6];
    $deviceName = fn (PushSubscription $device): string => filled($device->device_name)
        ? $device->device_name
        : (filled($device->browser) && filled($device->platform)
            ? __('notification_settings.devices.unnamed', ['browser' => $device->browser, 'platform' => $device->platform])
            : __('notification_settings.devices.unknown'));
    $muted_text = 'text-sm text-secondary-medium dark:text-ink-muted';
    $section = 'grid gap-3 border-t border-secondary-soft pt-8 dark:border-line';
@endphp

<x-desk.register class="@container/directory" heading-id="notification-settings-heading" :title="__('notifications.settings_link')">
    <p class="{{ $muted_text }}">{{ __('notification_settings.lead') }}</p>

    {{-- Every save is acknowledged here, politely, without moving focus. --}}
    <p class="mt-1 min-h-5 text-sm font-semibold" role="status" aria-live="polite" data-save-status="{{ $saveState }}" wire:key="save-status-{{ $saveTick }}">
        @if ($saveState === 'saved')
            <span class="text-status-good">{{ __('notification_settings.status.saved') }}</span>
        @elseif ($saveState === 'error')
            <span class="text-status-critical">{{ __('notification_settings.status.error') }}</span>
        @endif
    </p>

    <div class="mt-4 grid max-w-2xl gap-10">
    

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

        {{-- General: the master switch, the four channels, sound, details and how often. --}}
        <section aria-labelledby="general-settings-heading" class="{{ $section }}" data-general-settings>
            <h2 id="general-settings-heading" class="text-base font-bold">{{ __('notification_settings.general.title') }}</h2>

            <label class="flex items-start gap-3">
                <input type="checkbox" wire:model.live="prefs.enabled" class="mt-1 size-4 accent-primary" data-test="pref-enabled" />
                <span>
                    <span class="block text-sm font-semibold">{{ __('notification_settings.general.master_label') }}</span>
                    <span class="block {{ $muted_text }}">{{ __('notification_settings.general.master_help') }}</span>
                </span>
            </label>
            @if ($paused)
                <p class="ps-7 text-sm font-semibold text-status-critical" data-notifications-paused>{{ __('notification_settings.general.paused') }}</p>
            @endif

            <fieldset class="mt-2 grid gap-3" @disabled($paused)>
                <legend class="mb-2 text-sm font-bold">{{ __('notification_settings.general.channels') }}</legend>
                @foreach ($channels as $channel)
                    <label class="flex items-start gap-3" wire:key="channel-{{ $channel->value }}">
                        <input
                            type="checkbox"
                            wire:model.live="prefs.{{ $channel->value }}"
                            @disabled(! $pushConfigured && in_array($channel, $pushChannels, true))
                            class="mt-1 size-4 accent-primary"
                            data-test="pref-{{ $channel->value }}"
                        />
                        <span>
                            <span class="block text-sm font-semibold">{{ $channel->label() }}</span>
                            <span class="block {{ $muted_text }}">{{ __('notification_settings.general.channel_help.'.$channel->value) }}</span>
                        </span>
                    </label>
                @endforeach
                @if (! $pushConfigured)
                    <p class="ps-7 {{ $muted_text }}">{{ __('notification_settings.general.push_off') }}</p>
                @endif
            </fieldset>

            <fieldset class="mt-2 grid gap-3" @disabled($paused)>
                @foreach (['sound' => ['sound_label', 'sound_help'], 'previews' => ['previews_label', 'previews_help']] as $field => [$label, $help])
                    <label class="flex items-start gap-3" wire:key="pref-{{ $field }}">
                        <input type="checkbox" wire:model.live="prefs.{{ $field }}" class="mt-1 size-4 accent-primary" data-test="pref-{{ $field }}" />
                        <span>
                            <span class="block text-sm font-semibold">{{ __('notification_settings.general.'.$label) }}</span>
                            <span class="block {{ $muted_text }}">{{ __('notification_settings.general.'.$help) }}</span>
                        </span>
                    </label>
                @endforeach
            </fieldset>

            <div class="mt-2 grid gap-3 sm:grid-cols-2">
                <x-ui.form-select wire:model.live="prefs.frequency" :label="__('notification_settings.general.frequency_label')" :help="__('notification_settings.general.frequency_help')" :disabled="$paused" data-test="pref-frequency">
                    @foreach (NotificationPreference::FREQUENCIES as $frequency)
                        <option value="{{ $frequency }}">{{ __('notification_settings.general.frequency.'.$frequency) }}</option>
                    @endforeach
                </x-ui.form-select>

                @if ($prefs['frequency'] === 'daily')
                    <flux:input type="time" wire:model.blur="prefs.digest_time" :label="__('notification_settings.general.digest_time')" :disabled="$paused" data-test="pref-digest_time" />
                @endif
            </div>
            @error('prefs.frequency') <p class="text-sm text-status-critical">{{ $message }}</p> @enderror
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

        {{-- Module x channel: only chooses the place. Mutes (below) are a different thing. --}}
        <section aria-labelledby="module-channels-heading" class="{{ $section }}" data-module-channels>
            <h2 id="module-channels-heading" class="text-base font-bold">{{ __('notification_settings.matrix.title') }}</h2>
            <p class="{{ $muted_text }}">{{ __('notification_settings.matrix.help') }}</p>
            @error('matrix') <p class="text-sm text-status-critical" role="alert">{{ $message }}</p> @enderror

            @foreach ($channels as $channel)
                @if (! $prefs[$channel->value])
                    <p class="{{ $muted_text }}" wire:key="matrix-off-{{ $channel->value }}">{{ __('notification_settings.matrix.channel_off', ['channel' => $channel->label()]) }}</p>
                @endif
            @endforeach

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-secondary-soft text-start dark:border-line">
                            <th scope="col" class="py-2 text-start font-semibold">{{ __('notification_settings.matrix.topic') }}</th>
                            @foreach ($channels as $channel)
                                <th scope="col" class="px-2 py-2 text-center font-semibold">{{ $channel->label() }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-secondary-soft dark:divide-line">
                        @foreach ($this->matrixModules as $module)
                            <tr wire:key="matrix-{{ $module }}">
                                <th scope="row" class="py-2.5 text-start font-medium">{{ $presenter->moduleLabel($module) }}</th>
                                @foreach ($channels as $channel)
                                    <td class="px-2 py-2.5 text-center">
                                        <input
                                            type="checkbox"
                                            wire:change="setModuleChannel('{{ $module }}', '{{ $channel->value }}', $event.target.checked)"
                                            @checked($matrix[$module][$channel->value] ?? false)
                                            @disabled($paused || ! $prefs[$channel->value] || (! $pushConfigured && in_array($channel, $pushChannels, true)))
                                            aria-label="{{ __('notification_settings.matrix.cell', ['module' => $presenter->moduleLabel($module), 'channel' => $channel->label()]) }}"
                                            class="size-4 accent-primary"
                                            data-matrix-cell="{{ $module }}.{{ $channel->value }}"
                                        />
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        {{-- Module mutes: team-wide updates only; anything addressed to me always arrives. --}}
        <section aria-labelledby="module-mutes-heading" class="grid gap-2 border-t border-secondary-soft pt-8 dark:border-line" data-module-mutes>
            <h2 id="module-mutes-heading" class="text-base font-bold">{{ __('notifications.mutes_label') }}</h2>
            <p class="text-sm text-secondary-medium dark:text-ink-muted">{{ __('notifications.mutes_help') }}</p>
            <p class="text-sm text-secondary-medium dark:text-ink-muted" data-mutes-versus-matrix>{{ __('notification_settings.mutes_versus_matrix') }}</p>

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

        {{-- Quiet hours, working days and the time zone they are read in. --}}
        <section aria-labelledby="schedule-heading" class="{{ $section }}" data-schedule-settings>
            <h2 id="schedule-heading" class="text-base font-bold">{{ __('notification_settings.schedule.title') }}</h2>
            <p class="{{ $muted_text }}">{{ __('notification_settings.schedule.lead') }}</p>

            <fieldset class="grid gap-4" @disabled($paused)>
                <label class="flex items-start gap-3">
                    <input type="checkbox" wire:model.live="prefs.quiet_hours" class="mt-1 size-4 accent-primary" data-test="pref-quiet_hours" />
                    <span>
                        <span class="block text-sm font-semibold">{{ __('notification_settings.schedule.quiet_label') }}</span>
                        <span class="block {{ $muted_text }}">{{ __('notification_settings.schedule.quiet_help') }}</span>
                    </span>
                </label>

                @if ($prefs['quiet_hours'])
                    <div class="grid gap-3 ps-7 sm:grid-cols-2">
                        <flux:input type="time" wire:model.blur="prefs.quiet_start" :label="__('notification_settings.schedule.start')" data-test="pref-quiet_start" />
                        <flux:input type="time" wire:model.blur="prefs.quiet_end" :label="__('notification_settings.schedule.end')" data-test="pref-quiet_end" />
                    </div>

                    <label class="flex items-start gap-3 ps-7">
                        <input type="checkbox" wire:model.live="prefs.urgent_bypass" class="mt-1 size-4 accent-primary" data-test="pref-urgent_bypass" />
                        <span>
                            <span class="block text-sm font-semibold">{{ __('notification_settings.schedule.urgent_label') }}</span>
                            <span class="block {{ $muted_text }}">{{ __('notification_settings.schedule.urgent_help') }}</span>
                        </span>
                    </label>
                @endif

                <div>
                    <p class="text-sm font-semibold" id="working-days-label">{{ __('notification_settings.schedule.days') }}</p>
                    <p class="{{ $muted_text }}">{{ __('notification_settings.schedule.days_help') }}</p>
                    <div class="mt-2 flex flex-wrap gap-x-5 gap-y-2" role="group" aria-labelledby="working-days-label">
                        @foreach ($weekOrder as $day)
                            <label class="inline-flex items-center gap-2 text-sm" wire:key="day-{{ $day }}">
                                <input type="checkbox" value="{{ $day }}" wire:model.live="workingDays" class="size-4 accent-primary" data-working-day="{{ $day }}" />
                                {{ __('notification_settings.days.'.$day) }}
                            </label>
                        @endforeach
                    </div>
                    @error('workingDays') <p class="mt-1 text-sm text-status-critical" role="alert">{{ $message }}</p> @enderror
                </div>
            </fieldset>

            <div class="sm:max-w-sm">
                <x-ui.form-select wire:model.live="prefs.timezone" :label="__('notification_settings.schedule.timezone')" :disabled="$paused" searchable data-test="pref-timezone">
                    <option value="">{{ __('notification_settings.schedule.timezone_default', ['zone' => config('app.timezone')]) }}</option>
                    @foreach (DateTimeZone::listIdentifiers() as $zone)
                        <option value="{{ $zone }}">{{ $zone }}</option>
                    @endforeach
                </x-ui.form-select>
            </div>
            @foreach (['quiet_start', 'quiet_end', 'digest_time', 'timezone'] as $field)
                @error('prefs.'.$field) <p class="text-sm text-status-critical" role="alert">{{ $message }}</p> @enderror
            @endforeach
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
                                {{ __('notification_settings.devices.type.'.(in_array($device->device_type, PushSubscription::TYPES, true) ? $device->device_type : 'desktop')) }}
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
                        @foreach (NotificationTester::TARGETS as $target)
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
    </div>

    <x-ui.confirm-modal
        wire:model="showRemoveDevice"
        close="cancelRemoveDevice"
        :title="__('notification_settings.devices.remove_title')"
        :message="__('notification_settings.devices.remove_message')"
        confirm="removeDevice"
        :confirm-label="__('notification_settings.devices.remove_confirm')"
        confirm-test="confirm-remove-device"
    />
</x-desk.register>
