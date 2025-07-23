@props(['for', 'value'])

<label for="{{ $for }}" {{ $attributes->merge(['class' => 'block mb-1 text-sm font-medium text-title']) }}>
    {{ $value ?? $slot }}
</label>
