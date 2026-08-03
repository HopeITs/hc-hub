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
    @php
        $isTime = $type === 'time';
        $inputClass = 'w-full py-2 text-sm text-gray-900 placeholder-gray-400 rounded-md border border-gray-300 bg-white shadow-sm focus:outline-none focus:ring-2 focus:ring-secondary-light focus:border-primary transition '
            . ($isTime ? 'ps-3 pe-10 time-input-native' : 'px-3');
    @endphp

    <div class="relative w-full">
        <input
            @php
                $base = [
                    'id' => $inputId,
                    'type' => $type,
                    'class' => $inputClass,
                ];
                if ($wireAttr) { $base[$wireAttr] = $model; }
            @endphp
            {{ $attributes->merge($base) }}
        />

        @if ($isTime)
            <div class="pointer-events-none absolute inset-y-0 end-0 flex items-center pe-3">
                <x-icons.clock class="size-4 text-secondary-light" />
            </div>
        @endif
    </div>
@endif
