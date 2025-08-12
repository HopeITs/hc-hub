@props([
    'id' => null,
    'type' => 'text',
    'model' => null,
    'placeholder' => '',
    'autocomplete' => '',
    'showIcon' => true,
])

@php
    use Illuminate\Support\Str;

    $inputId = $id ?? Str::slug($model ?? 'input');
    $hasModel = !empty($attributes->wire('model'));
@endphp

@if ($type === 'checkbox')
    {{-- Checkbox Input --}}
    <label for="{{ $inputId }}" class="flex items-center gap-2 text-sm text-gray-700">
        <input
            {{$attributes->merge([
    'id' => $inputId,
    'type' => 'checkbox',
    'class' => 'size-4 bg-white border border-primary text-primary rounded focus:ring-primary',
    'wire:model.defer' => $model
])}}

        >
        {{ $slot }}
    </label>
@else
    {{-- Text, Email, Number, etc. --}}
    <div class="relative w-full">
        <input
            {{$attributes->merge([
                'id' => $inputId,
                'type' => $type,
                'class' => 'w-full py-2 px-3 text-sm text-gray-900 placeholder-gray-400 rounded-md border border-gray-300 bg-white shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-200 focus:border-primary-500 transition',
                'wire:model.defer' => $model
            ])}}

        />

        {{-- Optional Right Icons --}}
        @if ($showIcon)
            @if ($type === 'email')
                <span class="absolute top-2.5 end-3 text-gray-400">
                    <i class="fa-regular fa-envelope text-sm"></i>
                </span>
            @elseif ($type === 'number')
                <span class="absolute top-2.5 end-3 text-gray-400">
                    <i class="fa-solid fa-hashtag text-sm"></i>
                </span>
            @elseif ($type === 'date')
                <span class="absolute top-2.5 end-3 text-gray-400">
                    <i class="fa-regular fa-calendar text-sm"></i>
                </span>
            @endif
        @endif
    </div>
@endif
