{{--
    The one person card for the tasks page, filled from whichever assignee
    avatar is hovered or focused (see resources/js/person-card.js and the
    data-person-* attributes on the avatars). Shows only work details from HR:
    photo, name in both languages, position · level · department.
--}}
<template x-teleport="body">
    <div
        x-data
        x-show="$store.personCard.open"
        x-cloak
        x-transition:enter="transition ease-out duration-150 motion-reduce:transition-none" x-transition:enter-start="opacity-0 -translate-y-1 scale-[.97]" x-transition:enter-end="opacity-100 translate-y-0 scale-100" x-transition:leave="transition ease-in duration-100 motion-reduce:transition-none" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
        x-on:mouseenter="$store.personCard.keep()"
        x-on:mouseleave="$store.personCard.hide()"
        x-bind:style="`top: ${$store.personCard.top}px; ` + ($store.personCard.right === null ? `left: ${$store.personCard.left}px;` : `right: ${$store.personCard.right}px;`)"
        role="tooltip"
        class="fixed z-50 flex w-72 items-center gap-3 rounded-xl border border-secondary-soft bg-white p-3.5 shadow-lg dark:border-line dark:bg-surface-3 dark:shadow-raised"
        data-test="person-card"
    >
        <img
            x-bind:src="$store.personCard.photo || {{ Illuminate\Support\Js::from(asset(\Modules\Hr\Models\Employee::DefaultAvatar)) }}"
            alt=""
            onerror="this.onerror = null; this.src = '{{ asset(\Modules\Hr\Models\Employee::DefaultAvatar) }}'"
            class="size-14 shrink-0 rounded-full !bg-hr-avatar object-cover"
        />
        <div class="min-w-0">
            <p class="truncate text-sm font-bold text-secondary-dark dark:text-ink" dir="auto" x-text="$store.personCard.name"></p>
            {{-- No dir="auto": the other-language name lines up with the name above it. --}}
            <p class="truncate text-xs text-secondary-medium dark:text-ink-muted" x-show="$store.personCard.altName" x-text="$store.personCard.altName"></p>
            <p class="mt-1 line-clamp-2 text-xs text-secondary-medium dark:text-ink-muted" dir="auto" x-show="$store.personCard.role" x-text="$store.personCard.role"></p>
        </div>
    </div>
</template>
