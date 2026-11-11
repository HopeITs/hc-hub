@props(['label', 'value' => null, 'ltr' => false])

{{-- One labelled fact of a record: the label above, the value (or a dash) below. --}}
<div {{ $attributes->class('min-w-0') }}>
    <dt class="type-label text-secondary-medium dark:text-ink-muted">{{ $label }}</dt>
    <dd class="mt-0.5 break-words text-sm text-secondary-dark dark:text-ink" @if ($ltr) dir="ltr" @else dir="auto" @endif>
        @if ($slot->hasActualContent())
            {{ $slot }}
        @else
            {{ filled($value) ? $value : '—' }}
        @endif
    </dd>
</div>
