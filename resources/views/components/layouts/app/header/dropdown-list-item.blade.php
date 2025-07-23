@props([
    'icon' => 'ti ti-user-circle'
])
<li>
    <a {{ $attributes->merge([
    'class' => 'inline-flex items-center text-gray-900 py-2 hover:text-primary' ])}}
        >
        <i class="{{$icon}} me-1"></i>{{$slot}}
    </a>
</li>
