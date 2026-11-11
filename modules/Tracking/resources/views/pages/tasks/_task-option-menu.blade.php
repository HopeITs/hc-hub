@php
    use Modules\Tracking\Enums\TaskPriority;
    use Modules\Tracking\Enums\TaskStatus;
    use App\Support\FluxMarkup;

    $check = FluxMarkup::icon('check', 'outline', 'ms-auto size-4 shrink-0 text-secondary-medium');
@endphp

{{--
    One shared menu for the task list's status and priority triggers, opened by
    whichever trigger was clicked (see the `$store.taskMenu` calls in
    _list-row.blade.php, the composers and the mobile cards). Rendered once per
    page instead of once per row — see task-menu.js.
--}}
<template x-teleport="body">
    <div
        x-data
        x-show="$store.taskMenu.open"
        x-cloak
        x-on:click.outside="$store.taskMenu.close()"
        x-on:keydown.escape.window="$store.taskMenu.close()"
        x-transition:enter="transition ease-out duration-150 motion-reduce:transition-none" x-transition:enter-start="opacity-0 -translate-y-1 scale-[.97]" x-transition:enter-end="opacity-100 translate-y-0 scale-100" x-transition:leave="transition ease-in duration-100 motion-reduce:transition-none" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
        x-bind:style="`top: ${$store.taskMenu.top}px; ` + ($store.taskMenu.right === null ? `left: ${$store.taskMenu.left}px;` : `right: ${$store.taskMenu.right}px;`)"
        role="menu"
        class="fixed z-50 min-w-44 rounded-lg border border-secondary-soft bg-white p-1 shadow-lg dark:border-line dark:bg-surface-3 dark:shadow-raised"
        data-test="task-option-menu"
    >
        <div x-show="$store.taskMenu.kind === 'status'" class="flex flex-col">
            @foreach (TaskStatus::cases() as $statusCase)
                <button
                    type="button"
                    role="menuitemradio"
                    x-bind:aria-checked="$store.taskMenu.current === '{{ $statusCase->value }}' ? 'true' : 'false'"
                    x-on:click="$store.taskMenu.pick($wire, '{{ $statusCase->value }}')"
                    class="{{ FluxMarkup::MenuItem }} cursor-pointer hover:bg-secondary-soft dark:hover:bg-white/[0.06]"
                    data-test="task-status-option-{{ $statusCase->value }}"
                >
                    {{ FluxMarkup::statusPill($statusCase) }}
                    <span x-show="$store.taskMenu.current === '{{ $statusCase->value }}'" style="display: none" class="ms-auto">{{ $check }}</span>
                </button>
            @endforeach
        </div>
        <div x-show="$store.taskMenu.kind === 'priority'" class="flex flex-col" style="display: none">
            @foreach (TaskPriority::cases() as $priorityCase)
                <button
                    type="button"
                    role="menuitemradio"
                    x-bind:aria-checked="$store.taskMenu.current === '{{ $priorityCase->value }}' ? 'true' : 'false'"
                    x-on:click="$store.taskMenu.pick($wire, '{{ $priorityCase->value }}')"
                    class="{{ FluxMarkup::MenuItem }} cursor-pointer gap-2 hover:bg-secondary-soft dark:hover:bg-white/[0.06]"
                    data-test="task-priority-option-{{ $priorityCase->value }}"
                >
                    {{ FluxMarkup::priorityFlag($priorityCase) }}
                    <span class="min-w-0 truncate">{{ $priorityCase->label() }}</span>
                    <span x-show="$store.taskMenu.current === '{{ $priorityCase->value }}'" style="display: none" class="ms-auto">{{ $check }}</span>
                </button>
            @endforeach
        </div>
    </div>
</template>
