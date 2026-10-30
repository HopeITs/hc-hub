@props([
    'status',
])

@if ($status)
    {{-- Status is words on neutrals (no green), as media-request's notices. --}}
    <div role="status" {{ $attributes->merge(['class' => 'rounded-control border border-line-soft bg-soft px-3 py-2 text-body font-medium text-ink']) }}>
        {{ $status }}
    </div>
@endif
