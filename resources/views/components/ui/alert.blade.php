{{--
    Page-level callout for an error, warning, success or plain notice (not a toast, not a field error).

        <x-ui.alert tone="danger" :title="__('...')" dismissible>Body text</x-ui.alert>

    tone: info | success | warning | danger. Danger and warning interrupt a screen reader (role="alert");
    info and success are polite (role="status"). Colour is never the only signal: every tone has an icon.
    An optional `action` slot (a button) sits at the end of the row. Extra attributes (data-test, class) pass through.
--}}
@props([
    'tone' => 'info',
    'title' => null,
    'dismissible' => false,
])

@php
    $tones = [
        'info' => ['icon' => 'information-circle', 'box' => 'border-status-active/40 bg-status-active-soft'],
        'success' => ['icon' => 'check-circle', 'box' => 'border-status-good/40 bg-status-good-soft'],
        'warning' => ['icon' => 'exclamation-triangle', 'box' => 'border-status-warn/60 bg-status-warn-soft'],
        'danger' => ['icon' => 'exclamation-circle', 'box' => 'border-status-critical bg-status-critical-soft'],
    ];
    $style = $tones[$tone] ?? $tones['info'];
    $role = in_array($tone, ['warning', 'danger'], true) ? 'alert' : 'status';
@endphp

<div
    x-data="{ open: true }"
    x-show="open"
    role="{{ $role }}"
    data-ui-alert
    data-tone="{{ $tone }}"
    {{ $attributes->class(['flex items-start gap-3 rounded-lg border p-3 text-sm text-secondary-dark dark:text-ink', $style['box']]) }}
>
    <flux:icon :name="$style['icon']" variant="mini" class="mt-0.5 size-5 shrink-0" aria-hidden="true" />

    <div class="min-w-0 flex-1 space-y-1">
        @if ($title)
            <p class="font-semibold">{{ $title }}</p>
        @endif
        <div>{{ $slot }}</div>
    </div>

    @isset($action)
        <div class="shrink-0">{{ $action }}</div>
    @endisset

    @if ($dismissible)
        <flux:button type="button" variant="ghost" size="xs" icon="x-mark" inset x-on:click="open = false" :aria-label="__('common.dismiss')" data-ui-alert-dismiss />
    @endif
</div>
