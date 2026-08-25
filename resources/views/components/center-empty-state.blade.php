@props(['title', 'description'])

<div {{ $attributes->class(['flex min-h-72 items-center justify-center rounded-surface border border-dashed border-neutral-300 bg-white p-[clamp(1.5rem,4vw,3rem)] text-center shadow-e1 dark:border-neutral-700 dark:bg-neutral-900']) }}>
    <div class="max-w-sm">
        <span class="mx-auto flex size-14 items-center justify-center rounded-full bg-neutral-100 text-primary dark:bg-neutral-800">
            <x-icons.users class="size-7" />
        </span>
        <h2 class="mt-4 text-h4 font-bold text-secondary-dark dark:text-white">{{ $title }}</h2>
        <p class="mt-2 text-body leading-relaxed text-secondary-light">{{ $description }}</p>
    </div>
</div>
