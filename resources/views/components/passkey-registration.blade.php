{{-- Register a passkey on the security page (laravel/passkeys). The routes are
     passed in so they keep the locale prefix; the page reloads its list after. --}}
@assets
@vite('resources/js/passkeys.js')
@endassets

<div
    x-data="{
        supported: false,
        showForm: false,
        name: '',
        loading: false,
        error: null,
        updateSupport() {
            this.supported = Boolean(window.Passkeys?.isSupported());
        },
        defaultName() {
            const ua = navigator.userAgent;
            const browser = [
                { pattern: /Edg|Edge/, name: 'Edge' },
                { pattern: /OPR|Opera|OPiOS/, name: 'Opera' },
                { pattern: /Firefox|FxiOS/, name: 'Firefox' },
                { pattern: /Chrome|CriOS/, name: 'Chrome' },
                { pattern: /Safari/, name: 'Safari' },
            ].find(({ pattern }) => pattern.test(ua))?.name;
            const os = [
                { pattern: /iPhone/, name: 'iPhone' },
                { pattern: /iPad|Macintosh(?=.*Mobile)/, name: 'iPad' },
                { pattern: /Android/, name: 'Android' },
                { pattern: /Mac/, name: 'Mac' },
                { pattern: /Windows/, name: 'Windows' },
            ].find(({ pattern }) => pattern.test(ua))?.name;

            return [browser, os].filter(Boolean).join(' / ');
        },
        init() {
            this.name = this.defaultName();
            this.updateSupport();
            window.addEventListener('passkeys:ready', () => this.updateSupport(), { once: true });
        },
        async register() {
            if (! this.name.trim()) return;
            this.loading = true;
            this.error = null;
            try {
                await window.Passkeys.register({
                    name: this.name,
                    routes: { options: @js(route('passkey.registration-options')), submit: @js(route('passkey.store')) },
                });
                this.name = this.defaultName();
                this.showForm = false;
                await $wire.loadPasskeys();
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
    <p x-show="! supported" class="text-body text-ink-2">{{ __('security.passkeys_not_supported') }}</p>

    <div x-show="supported && ! showForm" x-cloak>
        <button type="button" x-on:click="showForm = true; $nextTick(() => $refs.passkeyName?.focus())"
                class="inline-flex min-h-11 cursor-pointer items-center gap-2 rounded-control border-[1.5px] border-line bg-card px-4 text-body font-bold text-ink transition hover:bg-hover">
            <i class="fa-solid fa-plus" aria-hidden="true"></i>
            {{ __('security.add_passkey') }}
        </button>
    </div>

    <div x-show="supported && showForm" x-cloak class="grid gap-3 border-s-2 border-primary ps-4">
        <div>
            <x-forms.input-label for="passkey-name" :value="__('security.passkey_name')" />
            <x-forms.input id="passkey-name" x-ref="passkeyName" x-model="name" x-on:keydown.enter.prevent="register()"
                           :placeholder="__('security.passkey_name_placeholder')" />
            <p class="mt-1 text-body text-ink-2">{{ __('security.passkey_name_help') }}</p>
        </div>

        <p x-show="error" x-text="error" x-cloak role="alert" class="text-body text-primary"></p>

        <div class="flex flex-wrap gap-2">
            <x-primary-button type="button" :show-spinner="false" x-on:click="register()" x-bind:disabled="loading || ! name.trim()">
                <span x-show="! loading">{{ __('security.register_passkey') }}</span>
                <span x-show="loading" x-cloak>{{ __('security.registering') }}</span>
            </x-primary-button>
            <button type="button" x-on:click="showForm = false; error = null"
                    class="min-h-11 cursor-pointer rounded-control px-4 text-body font-semibold text-ink-2 transition hover:bg-hover">
                {{ __('security.cancel') }}
            </button>
        </div>
    </div>
</div>
