@props(['status' => null, 'onAvatar' => false, 'withLabel' => false])

{{--
    The presence dot (Modules\Hr\Support\Employees\WorkPresence). $status is what
    WorkPresence returns for one person: ['tone' => online|offline, 'label' => words];
    null draws nothing (no account, or a former person). The words are the hover
    title and what a screen reader says; with-label also prints them beside the dot.
    on-avatar pins the dot to the corner of a photo (the parent must be `relative`).
--}}
@if (is_array($status) && isset($status['tone'], $status['label']))
    @php
        $dot = $status['tone'] === \Modules\Hr\Support\Employees\WorkPresence::Online
            ? 'bg-status-good'
            : 'border border-secondary-medium bg-white dark:border-ink-muted dark:bg-surface-1';
    @endphp

    @if ($withLabel)
        <span {{ $attributes->class('inline-flex items-center gap-1.5') }} data-presence="{{ $status['tone'] }}">
            <span aria-hidden="true" class="size-2.5 shrink-0 rounded-full {{ $dot }}"></span>
            <span>{{ $status['label'] }}</span>
        </span>
    @else
        <span role="img" aria-label="{{ $status['label'] }}" title="{{ $status['label'] }}" data-presence="{{ $status['tone'] }}"
              {{ $attributes->class(['size-2.5 shrink-0 rounded-full', $dot, 'absolute bottom-0 end-0 ring-2 ring-white dark:ring-surface-1' => $onAvatar, 'inline-block' => ! $onAvatar]) }}></span>
    @endif
@endif
