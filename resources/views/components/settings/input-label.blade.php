@props(['for', 'value'])

<label for="{{ $for }}" {{ $attributes->merge(['class' => 'block text-sm mb-3 font-medium text-gray-700 dark:text-gray-300']) }}>
    {{ $value ?? $slot }}
</label>
