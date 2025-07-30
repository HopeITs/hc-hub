@props(['field'])

@error($field)
<p {{ $attributes->merge(['class' => 'text-primary text-sm mt-1']) }}>
    {{ $message }}
</p>
@enderror
