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

    #[Computed]
    public function canManageRules(): bool
    {
        return auth()->user()->can('notification-settings.manage');
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
    $channelIcons = ['in_app' => 'bell', 'desktop' => 'computer-desktop', 'mobile' => 'device-phone-mobile', 'email' => 'envelope'];
    $paused = ! $prefs['enabled'];
    $weekOrder = app()->getLocale() === 'ar' ? [6, 0, 1, 2, 3, 4, 5] : [0, 1, 2, 3, 4, 5, 6];
    $deviceName = fn (PushSubscription $device): string => filled($device->device_name)
        ? $device->device_name
        : (filled($device->browser) && filled($device->platform)
            ? __('notification_settings.devices.unnamed', ['browser' => $device->browser, 'platform' => $device->platform])
            : __('notification_settings.devices.unknown'));
    $muted_text = 'text-sm text-secondary-medium dark:text-ink-muted';
    $section = 'grid gap-3';
    $switch = 'relative h-6 w-11 shrink-0 cursor-pointer appearance-none rounded-full bg-secondary-soft transition-colors after:absolute after:start-0.5 after:top-0.5 after:size-5 after:rounded-full after:bg-white after:shadow-sm after:transition-transform checked:bg-primary checked:after:translate-x-5 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary disabled:cursor-not-allowed disabled:opacity-50 rtl:checked:after:-translate-x-5 dark:bg-surface-3 dark:checked:bg-brand';
    $chip = 'inline-flex min-h-11 cursor-pointer touch-manipulation items-center gap-2 rounded-xl border border-secondary-soft px-3 text-sm font-medium text-secondary-medium transition-colors has-[:checked]:border-primary has-[:checked]:bg-primary/5 has-[:checked]:text-secondary-dark has-[:disabled]:cursor-not-allowed has-[:disabled]:opacity-50 has-[:focus-visible]:outline-2 has-[:focus-visible]:outline-offset-2 has-[:focus-visible]:outline-primary dark:border-line dark:text-ink-muted dark:has-[:checked]:border-brand-text dark:has-[:checked]:bg-surface-2 dark:has-[:checked]:text-ink';
    $more = 'group/set scroll-mt-24 border-t border-secondary-soft dark:border-line';
    $moreSummary = 'flex min-h-14 cursor-pointer touch-manipulation list-none items-center gap-3 py-3 [&::-webkit-details-marker]:hidden';
    $moreSections = [
        'by-topic' => 'by_topic',
        'more-options' => 'more',
        'devices' => 'devices',
    ];
@endphp

<x-desk.register class="@container/directory" heading-id="notification-settings-heading" :title="__('notifications.settings_link')">
    {{-- One page: what matters is in sight, the rest opens on demand. An address like #desktop-app opens the part it names. --}}
    <div
        class="max-w-3xl"
        x-data="{
            reveal() {
                const target = location.hash.length > 1 ? document.getElementById(location.hash.slice(1)) : null;

                if (! target) {
                    return;
                }

                for (let fold = target.closest('details'); fold; fold = fold.parentElement?.closest('details')) {
                    fold.open = true;
                }

                this.$nextTick(() => target.scrollIntoView({ block: 'start' }));
            },
        }"
        x-init="reveal()"
        x-on:hashchange.window="reveal()"
        data-notification-settings
    >
        <div class="grid gap-10 pb-10">
            {{-- The master switch, and the four places a notification can reach me. --}}
            <section aria-labelledby="general-settings-heading" class="grid gap-5" data-general-settings>
                <h2 id="general-settings-heading" class="sr-only">{{ __('notification_settings.general.title') }}</h2>

                <label class="flex cursor-pointer items-center justify-between gap-4">
                    <span class="min-w-0">
                        <span class="block text-base font-bold">{{ __('notification_settings.general.master_label') }}</span>
                        <span class="block {{ $muted_text }}">{{ __('notification_settings.lead') }}</span>
                    </span>
                    <input type="checkbox" role="switch" wire:model.live="prefs.enabled" class="{{ $switch }}" data-test="pref-enabled" />
                </label>

                {{-- Every save is acknowledged here, politely, without moving focus. --}}
                <p class="-mt-3 min-h-5 text-sm font-semibold" role="status" aria-live="polite" data-save-status="{{ $saveState }}" wire:key="save-status-{{ $saveTick }}">
                    @if ($saveState === 'saved')
                        <span class="text-status-good">{{ __('notification_settings.status.saved') }}</span>
                    @elseif ($saveState === 'error')
                        <span class="text-status-critical">{{ __('notification_settings.status.error') }}</span>
                    @endif
                </p>

                @if ($paused)
                    <p class="-mt-3 text-sm font-semibold text-status-critical" data-notifications-paused>{{ __('notification_settings.general.paused') }}</p>
                @endif

                <fieldset class="grid gap-3" @disabled($paused)>
                    <legend class="mb-3 text-sm font-bold">{{ __('notification_settings.general.channels') }}</legend>
                    <div class="grid grid-cols-2 gap-2 @2xl/directory:grid-cols-4">
                        @foreach ($channels as $channel)
                            <label class="{{ $chip }}" title="{{ __('notification_settings.general.channel_help.'.$channel->value) }}" wire:key="channel-{{ $channel->value }}">
                                <input
                                    type="checkbox"
                                    wire:model.live="prefs.{{ $channel->value }}"
                                    @disabled(! $pushConfigured && in_array($channel, $pushChannels, true))
                                    class="size-4 accent-primary"
                                    data-test="pref-{{ $channel->value }}"
                                />
                                <flux:icon :name="$channelIcons[$channel->value]" variant="mini" class="shrink-0" />
                                <span class="truncate">{{ $channel->label() }}</span>
                            </label>
                        @endforeach
                    </div>
                    @if (! $pushConfigured)
                        <p class="{{ $muted_text }}">{{ __('notification_settings.general.push_off') }}</p>
                    @endif
                </fieldset>
            </section>

            {{-- Quiet hours and working days. --}}
            <section aria-labelledby="schedule-heading" class="grid gap-4" data-schedule-settings>
                <fieldset class="grid gap-4" @disabled($paused)>
                    <label class="flex cursor-pointer items-center justify-between gap-4">
                        <span class="min-w-0">
                            <span id="schedule-heading" class="block text-sm font-bold">{{ __('notification_settings.schedule.quiet_label') }}</span>
                            <span class="block {{ $muted_text }}">{{ __('notification_settings.schedule.quiet_help') }}</span>
                        </span>
                        <input type="checkbox" role="switch" wire:model.live="prefs.quiet_hours" class="{{ $switch }}" data-test="pref-quiet_hours" />
                    </label>

                    @if ($prefs['quiet_hours'])
                        <div class="flex flex-wrap items-end gap-3">
                            <div class="w-36"><flux:input type="time" wire:model.blur="prefs.quiet_start" :label="__('notification_settings.schedule.start')" data-test="pref-quiet_start" /></div>
                            <div class="w-36"><flux:input type="time" wire:model.blur="prefs.quiet_end" :label="__('notification_settings.schedule.end')" data-test="pref-quiet_end" /></div>
                            <label class="{{ $chip }}" title="{{ __('notification_settings.schedule.urgent_help') }}">
                                <input type="checkbox" wire:model.live="prefs.urgent_bypass" class="size-4 accent-primary" data-test="pref-urgent_bypass" />
                                {{ __('notification_settings.schedule.urgent_label') }}
                            </label>
                        </div>
                        @foreach (['quiet_start', 'quiet_end'] as $field)
                            @error('prefs.'.$field) <p class="text-sm text-status-critical" role="alert">{{ $message }}</p> @enderror
                        @endforeach
                    @endif

                    <div>
                        <p class="text-sm font-bold" id="working-days-label" title="{{ __('notification_settings.schedule.days_help') }}">{{ __('notification_settings.schedule.days') }}</p>
                        <div class="mt-3 flex flex-wrap gap-2" role="group" aria-labelledby="working-days-label">
                            @foreach ($weekOrder as $day)
                                <label class="{{ $chip }}" wire:key="day-{{ $day }}">
                                    <input type="checkbox" value="{{ $day }}" wire:model.live="workingDays" class="sr-only" data-working-day="{{ $day }}" />
                                    {{ __('notification_settings.days.'.$day) }}
                                </label>
                            @endforeach
                        </div>
                        @error('workingDays') <p class="mt-1 text-sm text-status-critical" role="alert">{{ $message }}</p> @enderror
                    </div>
                </fieldset>
            </section>
        </div>

        {{-- Everything else, folded away. --}}
        @foreach ($moreSections as $id => $key)
            <details id="{{ $id }}" class="{{ $more }}" wire:ignore.self wire:key="more-{{ $id }}">
                <summary class="{{ $moreSummary }}">
                    <span class="min-w-0 flex-1">
                        <span class="block text-sm font-bold">{{ __("notification_settings.more.{$key}.title") }}</span>
                        <span class="block {{ $muted_text }}">{{ __("notification_settings.more.{$key}.hint") }}</span>
                    </span>
                    <flux:icon.chevron-right variant="micro" class="shrink-0 transition-transform group-open/set:rotate-90 rtl:-scale-x-100" />
                </summary>

                <div class="grid gap-10 pt-3 pb-10">
                    @if ($id === 'by-topic')
                        @include('pages.notifications._topics')
                    @elseif ($id === 'more-options')
                        @include('pages.notifications._options')
                    @else
                        @include('pages.notifications._devices')
                    @endif
                </div>
            </details>
        @endforeach

        {{-- The organization's own rules, for the people who manage them. Loaded when first opened. --}}
        @if ($this->canManageRules)
            <details id="organization-rules" class="{{ $more }}" wire:ignore.self data-org-rules>
                <summary class="{{ $moreSummary }}">
                    <span class="min-w-0 flex-1">
                        <span class="block text-sm font-bold">{{ __('notification_settings.more.rules.title') }}</span>
                        <span class="block {{ $muted_text }}">{{ __('notification_settings.more.rules.hint') }}</span>
                    </span>
                    <flux:icon.chevron-right variant="micro" class="shrink-0 transition-transform group-open/set:rotate-90 rtl:-scale-x-100" />
                </summary>

                <div class="pt-3 pb-10">
                    <livewire:notifications.rules lazy />
                </div>
            </details>
        @endif
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
