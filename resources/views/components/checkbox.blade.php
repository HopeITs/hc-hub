@props(['model'])

<label {{ $attributes->merge(['class' => 'flex items-center']) }}>
    <input
        type="checkbox"
        class="size-4 bg-white border border-borderColor rounded text-primary mr-1"
    />
    {{ $slot }}
</label>
