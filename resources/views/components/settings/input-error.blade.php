@props(['field'])

@error($field)
<p {{ $attributes->merge(['class' => 'text-sm text-red-600 dark:text-red-400 mt-1']) }}>
    {{ $message }}
</p>
@enderror
