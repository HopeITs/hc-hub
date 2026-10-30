@props([
    'title',
    'description' => null,
])

<div class="flex w-full flex-col gap-1 text-center">
    <h1 class="text-h2 font-bold text-ink">{{ $title }}</h1>
    @if ($description)
        <p class="text-body text-ink-2">{{ $description }}</p>
    @endif
</div>
