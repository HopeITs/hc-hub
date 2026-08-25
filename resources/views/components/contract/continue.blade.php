@props(['section'])

{{--
    Not a confirm button: the section is already valid and already saved in the
    composer's state by the time this appears. It is a shortcut for "collapse
    this and open the next", offered because it sits exactly where the eye lands
    after the last field. Clicking any other section header does the same thing.
--}}
<div class="mt-5 flex justify-end" x-cloak x-show="isValid('{{ $section }}') && '{{ $section }}' !== 'pay'">
    <button
        type="button"
        class="inline-flex items-center gap-1.5 rounded-control px-2 py-1 text-small font-semibold
               text-primary transition hover:bg-red-50
               focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary"
        x-on:click="nextSection()"
    >
        {{ __('general.composer.continue') }}
        <svg class="size-3.5 rtl:-scale-x-100" viewBox="0 0 20 20" fill="none" stroke="currentColor"
             stroke-width="2" aria-hidden="true">
            <path d="M7.5 4.5l5.5 5.5-5.5 5.5" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
    </button>
</div>
