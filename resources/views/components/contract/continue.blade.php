@props(['section'])

{{--
    Not a confirm button: the section is already valid and already held in state
    by the time this appears. It is a shortcut for "collapse this and open the
    next", offered because it sits where the eye lands after the last field.
    Clicking any other section header does the same thing.
--}}
<div class="mt-5 flex justify-end border-t border-neutral-100 pt-4"
     x-cloak x-show="isValid('{{ $section }}') && !isLastSection('{{ $section }}')">
    <button
        type="button"
        class="inline-flex items-center gap-2 rounded-control border border-primary bg-white px-4 py-2
               text-body font-semibold text-primary shadow-e1 transition
               hover:bg-primary hover:text-white
               active:scale-[0.98] active:shadow-none
               focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary"
        x-on:click="nextSection()"
    >
        {{ __('general.composer.continue') }}
        <svg class="size-4 rtl:-scale-x-100" viewBox="0 0 20 20" fill="none" stroke="currentColor"
             stroke-width="2" aria-hidden="true">
            <path d="M7.5 4.5l5.5 5.5-5.5 5.5" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
    </button>
</div>
