@props([
    'status',
])

@if ($status)
    <div {{ $attributes->merge(['class' => 'flex items-center gap-1.5 font-medium text-sm text-status-good']) }}>
        <flux:icon.check-circle variant="micro" class="size-4 shrink-0" aria-hidden="true" />
        {{ $status }}
    </div>
@endif
