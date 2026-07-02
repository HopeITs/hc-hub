<div
    x-data="{ open: false, ring: false }"
    @keydown.escape.window="open = false"
    @notif-arrived.window="ring = false; $nextTick(() => { ring = true; setTimeout(() => ring = false, 850) })"
    class="relative">

    <!-- Bell Button -->
    <button
        type="button"
        @click="open = !open"
        :aria-expanded="open"
        aria-haspopup="menu"
        class="relative inline-flex items-center justify-center transition rounded-full cursor-pointer size-9 text-secondary-light hover:bg-gray-100 hover:text-secondary-dark focus:outline-none focus:ring-2 focus:ring-gray-200 dark:text-gray-400 dark:hover:text-white">

        <span :class="ring && 'animate-notif-shake'" class="inline-flex">
            <x-icons.notification class="size-5" />
        </span>

        <!-- Unread Counter -->
        <span class="pointer-events-none absolute -top-0.5 -end-0.5">
            <livewire:notifications.counter />
        </span>
    </button>

    <!-- Dropdown Panel -->
    <div
        x-cloak
        x-show="open"
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 -translate-y-1 scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
        x-transition:leave-end="opacity-0 -translate-y-1 scale-95"
        @click.outside="open = false"
        x-ref="panel"
        x-effect="if (open) { $nextTick(() => {
            $refs.panel.querySelectorAll('.notif-row').forEach((el, i) => {
                el.animate(
                    [{ opacity: 0, transform: 'translateY(6px)' }, { opacity: 1, transform: 'none' }],
                    { duration: 260, delay: i * 45, easing: 'cubic-bezier(0.22, 1, 0.36, 1)', fill: 'backwards' }
                );
            });
        }) }"
        class="absolute end-0 top-full z-50 mt-2 w-80 max-w-[calc(100vw-1.5rem)] origin-top overflow-hidden
               rounded-2xl border border-gray-100 bg-white shadow-[0_25px_80px_-20px_rgba(2,6,23,0.35)] ring-1 ring-black/5
               dark:bg-gray-800 dark:border-gray-700"
        role="menu">

        <!-- Livewire component renders its own header + list -->
        <div class="max-h-[22rem] overflow-y-auto">
            <livewire:notifications.dropdown wire:poll.10s />
        </div>

        <!-- Footer -->
        <a href="{{ route('notifications.index') }}"
           class="flex items-center justify-center gap-2 border-t border-gray-100 bg-gray-50 py-2.5 text-sm font-semibold
                  text-gray-900 transition hover:bg-gray-100">
            <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 14">
                <path d="M10 0C4.612 0 0 5.336 0 7c0 1.742 3.546 7 10 7 6.454 0 10-5.258 10-7 0-1.664-4.612-7-10-7Zm0 10a3 3 0 1 1 0-6 3 3 0 0 1 0 6Z"/>
            </svg>
            @lang('general.view-all')
        </a>
    </div>
</div>
