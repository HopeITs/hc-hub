@props(['id', 'maxWidth', 'labelledby' => null, 'label' => null, 'describedby' => null])

@php
    $id = $id ?? md5($attributes->wire('model'));

    switch ($maxWidth ?? '2xl') {
        case 'sm':
            $maxWidth = 'sm:max-w-sm';
            break;
        case 'md':
            $maxWidth = 'sm:max-w-md';
            break;
        case 'lg':
            $maxWidth = 'sm:max-w-lg';
            break;
        case 'xl':
            $maxWidth = 'sm:max-w-xl';
            break;
        case '3xl':
            $maxWidth = 'sm:max-w-3xl';
            break;
        case '4xl':
            $maxWidth = 'sm:max-w-4xl';
            break;
        case '5xl':
            $maxWidth = 'sm:max-w-5xl';
            break;
        case '6xl':
            $maxWidth = 'sm:max-w-6xl';
            break;
        case '7xl':
            $maxWidth = 'sm:max-w-7xl';
            break;
        case '2xl':
        default:
            $maxWidth = 'sm:max-w-2xl';
            break;
    }
@endphp

{{--
    The shared modal. Keyboard and screen-reader behaviour lives here so every
    dialog gets it at once:

      - The panel is role="dialog" aria-modal="true", named by its heading
        (labelledby) or, without one, by `label`.
      - x-trap (Alpine focus plugin, bundled with Livewire) keeps Tab inside,
        hides the rest of the page from assistive tech (.inert), locks the page
        scroll (.noscroll) and returns focus to the control that opened it.
        The trap sits on the root, not the panel, so a select2 dropdown
        rendered into the root (select2-input) stays reachable.
      - Initial focus goes to [autofocus], else the first form field, else the
        panel itself, never to the close button: a form starts where typing
        starts, and a read-only dialog is announced from its heading.
      - Escape is handled on the root, not the window, so it closes only the
        dialog that has focus and never one sitting under the confirm dialog.
--}}
<div
    x-data="{
        show: @entangle($attributes->wire('model')),
        opener: null,
        remember: null,
        lastInside: null,
        observer: null,
        init() {
            {{-- The opener is usually a wire:click button that Livewire disables while the request
                 runs, which drops focus to <body> before the dialog opens. Remember the last
                 control focused outside the dialog instead. --}}
            this.remember = e => { if (! this.show && ! this.$el.contains(e.target)) this.opener = e.target };
            document.addEventListener('focusin', this.remember);

            this.observer = new MutationObserver(() => {
                if (this.show && document.activeElement === document.body) this.recoverFocus(this.lastInside);
            });
            this.observer.observe(this.$el, { childList: true, subtree: true });

            this.$watch('show', open => {
                if (open) {
                    if (document.activeElement !== document.body) this.opener = document.activeElement;
                    setTimeout(() => this.focusInitial(), 30);
                } else {
                    {{-- After the leave transition. x-trap returns focus only to what was focused when it activated. --}}
                    setTimeout(() => this.returnFocus(), 250);
                }
            });
        },
        destroy() {
            document.removeEventListener('focusin', this.remember);
            this.observer?.disconnect();
        },
        {{-- A Livewire re-render can replace the focused field (a changed wire:key), which drops
             focus to <body>, outside the dialog. Put it back on the replacement, else the first field. --}}
        recoverFocus(lost) {
            let selectId = lost?.closest?.('.select2')?.previousElementSibling?.id;
            let id = lost?.id || selectId;

            setTimeout(() => {
                if (! this.show || (document.activeElement && document.activeElement !== document.body)) return;

                let replacement = id && document.getElementById(id);
                if (replacement && selectId) replacement = replacement.nextElementSibling?.querySelector('.select2-selection');

                replacement && this.$refs.panel.contains(replacement) && replacement.getClientRects().length
                    ? replacement.focus()
                    : this.focusInitial();
            }, 60);
        },
        focusInitial(attempt = 0) {
            let panel = this.$refs.panel;

            {{-- x-show reveals the panel on an animation frame, which a background tab can delay. --}}
            if (! panel.getClientRects().length) {
                if (this.show && attempt < 40) setTimeout(() => this.focusInitial(attempt + 1), 25);
                return;
            }

            let fields = 'input:not([type=hidden]):not([disabled]), select:not([disabled]):not(.select2-hidden-accessible), textarea:not([disabled]), .select2-selection, [contenteditable=true]';
            let target = panel.querySelector('[autofocus]')
                || [...panel.querySelectorAll(fields)].find(el => el.getClientRects().length && el.tabIndex !== -1)
                || panel;

            target.focus({ preventScroll: target === panel });
        },
        returnFocus() {
            let active = document.activeElement;
            if (active && active !== document.body && ! this.$el.contains(active)) return;

            let target = this.opener?.isConnected && ! this.opener.disabled
                ? this.opener
                : document.getElementById('main-content');
            target?.focus();
        },
    }"
    x-trap.inert.noscroll.noautofocus="show"
    x-on:focusin="lastInside = $event.target"
    x-on:close.stop="show = false"
    x-on:keydown.escape="if ($event.defaultPrevented || $store.confirm?.open) return; $event.stopPropagation(); show = false"
    x-show="show"
    id="{{ $id }}"
    data-modal-root
    class="fixed inset-0 z-50 overflow-y-auto overscroll-contain"
    style="display: none;"
>
    <div x-show="show" class="fixed inset-0 z-0 transform bg-gray-500/75 transition-all motion-reduce:transition-none"
         aria-hidden="true"
         x-on:click="show = false"
         x-transition:enter="ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
    </div>

    {{-- select2 renders open dropdowns here (select2-input): inside the focus trap, and wire:ignore so
         a Livewire morph of this dialog never removes a dropdown it does not know about. --}}
    <div wire:ignore data-modal-dropdowns class="relative z-20"></div>

    <div class="pointer-events-none relative z-10 flex min-h-full items-center justify-center p-4 sm:p-6">
        <div x-show="show"
             x-ref="panel"
             role="dialog"
             aria-modal="true"
             tabindex="-1"
             @if($labelledby) aria-labelledby="{{ $labelledby }}" @elseif($label) aria-label="{{ $label }}" @endif
             @if($describedby) aria-describedby="{{ $describedby }}" @endif
             class="pointer-events-auto w-full overflow-hidden rounded-surface bg-white shadow-e3 transform transition-all focus:outline-none motion-reduce:transition-none motion-reduce:transform-none {{ $maxWidth }}"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
            {{ $slot }}
        </div>
    </div>
</div>
