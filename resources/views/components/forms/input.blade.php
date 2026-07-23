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
    <label for="{{ $inputId }}" class="flex items-center gap-2 text-body text-neutral-800">
        <input
            @php
                $base = [
                    'id' => $inputId,
                    'type' => 'checkbox',
                    'class' => 'size-4 bg-white border border-primary text-primary rounded-inset focus:ring-primary focus:ring-0',
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
        $inputClass = 'w-full py-2 text-body text-neutral-900 placeholder-neutral-400 rounded-control border border-neutral-300 bg-white shadow-e1 focus:outline-none focus:ring-2 focus:ring-secondary-light focus:border-primary transition '
            . ($isTime ? 'ps-3 pe-12 time-input-native' : 'px-3');
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
