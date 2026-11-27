{{-- More options: sound, details, desktop popups, how often, and the time zone. --}}
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

{{-- Desktop popups for important and critical notifications. --}}
<section
    aria-labelledby="desktop-popups-heading"
    class="grid gap-2"
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

<div class="mt-2 grid gap-3 sm:grid-cols-2">
    <x-ui.form-select wire:model.live="prefs.frequency" :label="__('notification_settings.general.frequency_label')" :help="__('notification_settings.general.frequency_help')" :disabled="$paused" data-test="pref-frequency">
        @foreach (\App\Models\NotificationPreference::FREQUENCIES as $frequency)
            <option value="{{ $frequency }}">{{ __('notification_settings.general.frequency.'.$frequency) }}</option>
        @endforeach
    </x-ui.form-select>

    @if ($prefs['frequency'] === 'daily')
        <flux:input type="time" wire:model.live.blur="prefs.digest_time" :label="__('notification_settings.general.digest_time')" :disabled="$paused" data-test="pref-digest_time" />
    @endif
</div>
@error('prefs.frequency') <p class="text-sm text-status-critical">{{ $message }}</p> @enderror

<div class="sm:max-w-sm">
    <x-ui.form-select wire:model.live="prefs.timezone" :label="__('notification_settings.schedule.timezone')" :disabled="$paused" searchable data-test="pref-timezone">
        <option value="">{{ __('notification_settings.schedule.timezone_default', ['zone' => config('app.timezone')]) }}</option>
        @foreach (DateTimeZone::listIdentifiers() as $zone)
            <option value="{{ $zone }}">{{ $zone }}</option>
        @endforeach
    </x-ui.form-select>
</div>
@foreach (['digest_time', 'timezone'] as $field)
    @error('prefs.'.$field) <p class="text-sm text-status-critical" role="alert">{{ $message }}</p> @enderror
@endforeach
