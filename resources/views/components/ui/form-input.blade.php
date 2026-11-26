@props([
    'label',
    'id' => null,
    'type' => 'text',
    'prefix' => null,
    'suffix' => null,
    'required' => false,
    'help' => null,
    'variant' => null,
    'compact' => false,
    'hideLabel' => false,
    'clearable' => false,
])

@php
    /*
     * A text, number, email, phone or date field in the header filter picker's
     * box. Every attribute that is not a prop (wire:model, maxlength, min, step,
     * placeholder, autofocus...) lands on the <input>; only `class` stays on the
     * wrapper, so callers can place the field in their grid. The error message
     * is read from the bag under the wire:model name.
     *
     * variant="search" adds a leading magnifier, type search and (with `clearable`)
     * a clear button; `compact` is the 38px desktop-toolbar height; `hideLabel`
     * keeps the label for screen readers only. x-ui.input forwards here.
     */
    $isSearch = $variant === 'search';
    $type = $isSearch ? 'search' : $type;
    $model = (string) $attributes->wire('model')->value();
    $id ??= $model !== '' ? 'field-'.substr(md5($model.'|'.$label), 0, 10) : 'field-'.\Illuminate\Support\Str::random(10);
    $error = $model !== '' ? $errors->first($model) : '';
    $describedBy = collect([
        $prefix !== null ? $id.'-prefix' : null,
        $suffix !== null ? $id.'-suffix' : null,
        $help !== null ? $id.'-help' : null,
        $error !== '' ? $id.'-error' : null,
    ])->filter()->implode(' ');
@endphp

<x-ui.form-field :label="$label" :for="$id" :hide-label="$hideLabel" :required="$required" :help="$help" :error="$error" :class="$attributes->get('class')">
    <div
        class="form-box"
        @if ($error !== '') data-invalid @endif
        @if ($compact) data-size="sm" @endif
        @if ($isSearch && $clearable) x-data="{ filled: false }" x-init="filled = $el.querySelector('input').value !== ''" x-on:input="filled = $event.target.value !== ''" @endif
    >
        @if ($isSearch)
            <flux:icon.magnifying-glass variant="micro" class="form-affix size-4" aria-hidden="true" />
        @endif

        @if ($prefix !== null)
            <span id="{{ $id }}-prefix" class="form-affix">{{ $prefix }}</span>
        @endif

        <input
            id="{{ $id }}"
            type="{{ $type }}"
            {{ $attributes->except('class')->class(['form-box-input', 'tabular-nums' => $type === 'number']) }}
            @if ($required) required aria-required="true" @endif
            @if ($error !== '') aria-invalid="true" @endif
            @if ($describedBy !== '') aria-describedby="{{ $describedBy }}" @endif
        />

        @if ($suffix !== null)
            <span id="{{ $id }}-suffix" class="form-affix">{{ $suffix }}</span>
        @endif

        @if ($isSearch && $clearable)
            <button
                type="button"
                class="form-clear"
                x-show="filled"
                x-cloak
                x-on:click="const field = $el.closest('.form-box').querySelector('input'); field.value = ''; field.dispatchEvent(new Event('input', { bubbles: true })); field.focus()"
                aria-label="{{ __('Clear input') }}"
            >
                <flux:icon.x-mark variant="micro" class="size-4" />
            </button>
        @endif
    </div>
</x-ui.form-field>
