<div class="grid gap-3 border-s-2 border-line-soft ps-4" x-data="{ showRecoveryCodes: false }">
    <div>
        <h4 class="flex items-center gap-2 font-semibold text-ink">
            <i class="fa-solid fa-lock text-ink-3" aria-hidden="true"></i>
            {{ __('security.recovery_codes') }}
        </h4>
        <p class="mt-1 text-body text-ink-2">{{ __('security.recovery_codes_description') }}</p>
    </div>

    <div class="flex flex-wrap items-center gap-2">
        <button type="button" x-on:click="showRecoveryCodes = ! showRecoveryCodes"
                x-bind:aria-expanded="showRecoveryCodes.toString()" aria-controls="recovery-codes-section"
                class="inline-flex min-h-11 cursor-pointer items-center gap-2 rounded-control border-[1.5px] border-line bg-card px-4 text-body font-bold text-ink transition hover:bg-hover">
            <span x-show="! showRecoveryCodes">{{ __('security.view_recovery_codes') }}</span>
            <span x-show="showRecoveryCodes" x-cloak>{{ __('security.hide_recovery_codes') }}</span>
        </button>

        @if (filled($recoveryCodes))
            <button type="button" x-show="showRecoveryCodes" x-cloak wire:click="regenerateRecoveryCodes"
                    class="min-h-11 cursor-pointer rounded-control px-4 text-body font-semibold text-ink-2 transition hover:bg-hover">
                {{ __('security.regenerate_codes') }}
            </button>
        @endif
    </div>

    <div x-show="showRecoveryCodes" x-cloak id="recovery-codes-section" class="grid gap-3">
        @error('recoveryCodes')
            <p role="alert" class="text-body text-primary">{{ $message }}</p>
        @enderror

        @if (filled($recoveryCodes))
            <div class="grid grid-cols-2 gap-x-6 gap-y-1 rounded-control bg-soft p-4 font-mono text-body text-ink" role="list"
                 aria-label="{{ __('security.recovery_codes') }}" dir="ltr">
                @foreach ($recoveryCodes as $code)
                    <div role="listitem" class="select-text" wire:loading.class="opacity-50">{{ $code }}</div>
                @endforeach
            </div>
            <p class="text-body text-ink-2">{{ __('security.recovery_codes_use_once') }}</p>
        @endif
    </div>
</div>
