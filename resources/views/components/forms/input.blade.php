@props([
    'id' => null,
    'type' => 'text',
    'model' => null,
    'placeholder' => '',
    'autocomplete' => '',
    'showIcon' => true,
    'update' => 'defer',
])

@php
    use Illuminate\Support\Str;

    $inputId = $id ?? Str::slug($model ?? 'input');
    $wireAttr = $model ? "wire:model.$update" : null;
@endphp

@if ($type === 'checkbox')
    <label for="{{ $inputId }}" class="flex items-center gap-2 text-sm text-gray-700">
        <input
            @php
                $base = [
                    'id' => $inputId,
                    'type' => 'checkbox',
                    'class' => 'size-4 bg-white border border-primary text-primary rounded focus:ring-primary focus:ring-0',
                ];
                if ($wireAttr) { $base[$wireAttr] = $model; }
            @endphp
            {{ $attributes->merge($base) }}
        >
        {{ $slot }}
    </label>
@else
    <div class="relative w-full">
        <input
            @php
                $base = [
                    'id' => $inputId,
                    'type' => $type,
                    'class' => 'w-full py-2 px-3 text-sm text-gray-900 placeholder-gray-400 rounded-md border border-gray-300 bg-white shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-200 focus:border-primary-500 transition',
                ];
                if ($wireAttr) { $base[$wireAttr] = $model; }
            @endphp
            {{ $attributes->merge($base) }}
        />


    </div>
@endif
