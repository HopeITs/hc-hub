@props([
    'optionsRoute' => 'passkey.confirm-options',
    'submitRoute' => 'passkey.confirm',
    'label' => __('security.confirm_with_passkey'),
    'loadingLabel' => __('security.confirming'),
    'separator' => __('security.or_confirm_with_password'),
])

{{-- Verify with a passkey (laravel/passkeys). Shown only where the browser
     supports WebAuthn; the routes are passed in so they keep the locale prefix. --}}
@vite('resources/js/passkeys.js')

<div
    x-data="{
        supported: false,
        loading: false,
        error: null,
        updateSupport() {
            this.supported = Boolean(window.Passkeys?.isSupported());
        },
        init() {
            this.updateSupport();
            window.addEventListener('passkeys:ready', () => this.updateSupport(), { once: true });
        },
        async verify() {
            this.loading = true;
            this.error = null;
            try {
                const response = await window.Passkeys.verify({
                    routes: { options: @js(route($optionsRoute)), submit: @js(route($submitRoute)) },
                });
                window.location.href = response?.redirect || @js(route('dashboard'));
            } catch (e) {
                if (e.constructor?.name !== 'UserCancelledError') {
                    this.error = e.message;
                }
            } finally {
                this.loading = false;
            }
        },
    }"
>
    <template x-if="supported">
        <div class="flex flex-col gap-6">
            <div class="grid gap-2">
                <button
                    type="button"
                    x-on:click="verify()"
                    x-bind:disabled="loading"
                    class="flex min-h-11 w-full cursor-pointer items-center justify-center gap-2 rounded-control border-[1.5px] border-line bg-card px-[18px] text-body font-bold text-ink transition hover:bg-hover disabled:cursor-not-allowed disabled:opacity-45"
                >
                    <i class="fa-solid fa-fingerprint" aria-hidden="true"></i>
                    <span x-show="! loading">{{ $label }}</span>
                    <span x-show="loading" x-cloak>{{ $loadingLabel }}</span>
                </button>
                <p x-show="error" x-text="error" x-cloak role="alert" class="text-center text-body text-primary"></p>
            </div>

            <div class="flex items-center gap-3 text-body text-ink-2" aria-hidden="true">
                <span class="h-px flex-1 bg-line-soft"></span>
                <span>{{ $separator }}</span>
                <span class="h-px flex-1 bg-line-soft"></span>
            </div>
        </div>
    </template>
</div>
