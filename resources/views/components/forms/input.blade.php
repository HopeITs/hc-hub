@props([
    'id' => null,
    'type' => 'text',
    'model' => null,
    'placeholder' => '',
    'autocomplete' => '',
    'showIcon' => true,
    'update' => 'defer',
    'required' => false,
    'errorKey' => null,
])

@php
    use Illuminate\Support\Str;

    $inputId = $id ?? Str::slug($model ?? 'input');
    $wireAttr = $model ? "wire:model.$update" : null;

    /**
     * Wire the field into the accessibility tree.
     *
     * A validation message rendered next to an input is invisible to a screen
     * reader unless the input points at it: aria-describedby makes the message
     * read out when focus lands on the field, and aria-invalid marks the field
     * as failing rather than leaving it announced as ordinary text.
     *
     * The error key defaults to the wire:model path, which is already the key
     * Livewire validates under, so existing call sites gain this for free.
     */
    $errorField = $errorKey ?? $model;
    $hasError = $errorField !== null && $errors->has($errorField);

    $aria = [];

    if ($hasError) {
        $aria['aria-invalid'] = 'true';
        $aria['aria-describedby'] = $inputId.'-error';
    }

    if ($required) {
        // `required` drives native browser validation; aria-required keeps the
        // state exposed to assistive tech even when that validation is bypassed.
        $aria['required'] = true;
        $aria['aria-required'] = 'true';
    }
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
                $base = array_merge($base, $aria);
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
                    'class' => 'w-full py-2 px-3 text-sm text-gray-900 placeholder-gray-400 rounded-md border border-gray-300 bg-white shadow-sm focus:outline-none focus:ring-2 focus:ring-secondary-light focus:border-primary transition',
                ];
                if ($wireAttr) { $base[$wireAttr] = $model; }
                $base = array_merge($base, $aria);
            @endphp
            {{ $attributes->merge($base) }}
        />


    </div>
@endif
