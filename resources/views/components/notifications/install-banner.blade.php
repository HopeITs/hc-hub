{{--
    A small invitation to install the app, in the signed-in layout. It shows
    only while the browser holds an install prompt, or on iPhone/iPad Safari
    where Share is the way in; never when installed, and not for 30 days after
    "Not now" (appInstallBanner, resources/js/app-install.js). With a prompt
    its button opens the install-app modal; on iOS it links to the steps on
    the notification settings page. The settings page itself leaves it out.
--}}
<div
    x-data="appInstallBanner"
    x-show="visible"
    x-cloak
    x-on:hc-app-install-change.window="refresh($event.detail)"
    x-transition.opacity.duration.160ms
    role="region"
    aria-label="{{ __('notifications.app.banner_label') }}"
    class="fixed inset-x-3 bottom-3 z-50 sm:inset-x-auto sm:start-5 sm:bottom-5 sm:w-[26rem]"
    data-install-banner
>
    <div class="flex items-center gap-3 rounded-[1.25rem] border border-secondary-soft bg-white p-3 text-secondary-dark shadow-[0_1px_2px_rgb(88_89_91/0.1),0_24px_56px_-16px_rgb(88_89_91/0.45)] dark:border-line dark:bg-surface-3 dark:text-ink dark:shadow-[0_24px_56px_-16px_rgb(0_0_0/0.6)]">
        <img src="{{ asset('app-icons/icon-192.png') }}" alt="" width="40" height="40" class="size-10 shrink-0 rounded-full" data-no-dim />
        <p class="min-w-0 flex-1 text-sm">{{ __('notifications.app.banner_text') }}</p>
        <div class="flex shrink-0 items-center gap-1">
            <x-ui.button x-show="state === 'available'" type="button" tone="primary" size="sm" x-on:click="$flux.modal('install-app').show()" aria-haspopup="dialog" data-test="install-banner-install">
                {{ __('notifications.app.install_short') }}
            </x-ui.button>
            <x-ui.button x-show="state !== 'available'" tone="primary" size="sm" :href="route('notification-settings.edit').'#desktop-app'" wire:navigate data-test="install-banner-how">
                {{ __('notifications.app.how_to_install') }}
            </x-ui.button>
            <x-ui.button type="button" tone="ghost" size="sm" icon="x-mark" x-on:click="dismiss()" :aria-label="__('notifications.app.not_now')" :title="__('notifications.app.not_now')" data-test="install-banner-dismiss" />
        </div>
    </div>
</div>
