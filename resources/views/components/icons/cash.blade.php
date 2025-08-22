@props([
    'title' => null,
])

<svg
    {{ $attributes->merge(['class' => 'w-5 h-5']) }}
    xmlns="http://www.w3.org/2000/svg"
    viewBox="0 0 24 24"
    fill="none"
    stroke="currentColor"
    stroke-width="2"
    stroke-linecap="round"
    stroke-linejoin="round"
    aria-hidden="{{ $title ? 'false' : 'true' }}"
    role="img"
>
    @if($title)
        <title>{{ $title }}</title>
    @endif
    <!-- Simple dollar sign -->
    <path d="M12 1v20"/>
    <path d="M17 7c0-2.5-2-4-5-4s-5 1.5-5 4c0 2.5 2 4 5 4s5 1.5 5 4c0 2.5-2 4-5 4s-5-1.5-5-4"/>
</svg>
