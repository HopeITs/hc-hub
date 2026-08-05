@props(['model'])

<label {{ $attributes->merge(['class' => 'flex items-center']) }}>
    <input
        type="checkbox"
        class="size-4 bg-white border border-primary focus:ring-primary rounded-inset text-primary me-1"
    />
    {{ $slot }}
</label>
