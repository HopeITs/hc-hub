@php
    $type = $attributes->get('type', 'date');
    $isTime = $type === 'time';
    $inputClass = 'w-full py-2 text-sm text-gray-900 placeholder-gray-400 rounded-md border border-gray-300 bg-white shadow-sm focus:outline-none focus:ring-2 focus:ring-secondary-light focus:border-primary transition '
        . ($isTime ? 'ps-3 pe-10 time-input-native' : 'px-3');
@endphp

@if ($isTime)
    <div class="relative w-full">
        <input {{ $attributes->merge(['type' => 'time', 'class' => $inputClass]) }} />
        <div class="pointer-events-none absolute inset-y-0 end-0 flex items-center pe-3">
            <x-icons.clock class="size-4 text-secondary-light" />
        </div>
    </div>
@else
    <input {{ $attributes->merge(['type' => 'date', 'class' => $inputClass]) }} />
@endif

