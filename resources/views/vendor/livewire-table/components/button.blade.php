@props(['size' => 'md', 'active' => false])

<button
    {{
        $attributes->class([
            'flex items-center gap-1 relative bg-white border transition ease-in-out rounded-md shadow-xs h-full text-sm' => true,
            'active:bg-neutral-100 dark:bg-neutral-800 dark:active:bg-neutral-900' => true,
            'border-neutral-200 text-neutral-800 hover:text-neutral-500 focus:border-secondary-medium active:text-neutral-800 dark:border-neutral-700 dark:focus:border-primary dark:text-neutral-300 dark:hover:text-white dark:focus:border-primary dark:active:text-white' => ! $active,
            'border-secondary-medium text-primary dark:border-secondary-medium dark:text-primary' => $active,
            'px-3 py-2' => $size === 'md',
            'px-2 py-1' => $size === 'sm',
        ])
    }}
>
    {{ $slot }}
    @if ($active)
        <span class="absolute -right-1 -top-1 rounded-full shadow-xs bg-primary dark:bg-primary block size-2.5"></span>
    @endif
</button>
