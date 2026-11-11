{{--
    What a request costs (LeaveQuote): the amount, what stays after it, the
    days skipped and the blocking errors / warnings, in words. Included by
    _request-form-modal. $quote is a Modules\Hr\Support\Leave\LeaveQuote.
--}}
@php
    $perDay = $quote->hoursPerDay;
    $format = fn (float $hours) => \Modules\Hr\Support\Leave\LeaveFormat::hours($hours, $perDay);
@endphp

<div @class([
    'rounded-lg border px-3 py-2.5 text-sm',
    'border-status-critical/40 bg-status-critical/5' => $quote->hasErrors(),
    'border-secondary-soft bg-secondary-soft/40 dark:border-line dark:bg-surface-2' => ! $quote->hasErrors(),
]) data-leave-impact aria-live="polite">
    <dl class="grid grid-cols-2 gap-x-4 gap-y-1 sm:grid-cols-4">
        <div>
            <dt class="type-label text-secondary-medium dark:text-ink-muted">{{ __('hr_leave.ui.impact.requested') }}</dt>
            <dd class="font-semibold">{{ $format($quote->requestedHours) }}</dd>
        </div>
        <div>
            <dt class="type-label text-secondary-medium dark:text-ink-muted">{{ __('hr_leave.ui.impact.available') }}</dt>
            <dd>{{ $format($quote->availableHours) }}</dd>
        </div>
        <div>
            <dt class="type-label text-secondary-medium dark:text-ink-muted">{{ __('hr_leave.ui.impact.pending') }}</dt>
            <dd>{{ $format($quote->pendingHours) }}</dd>
        </div>
        <div>
            <dt class="type-label text-secondary-medium dark:text-ink-muted">{{ __('hr_leave.ui.impact.after') }}</dt>
            <dd @class(['font-semibold', 'text-status-critical' => $quote->projectedHours < 0])>{{ $format($quote->projectedHours) }}</dd>
        </div>
    </dl>

    @if ($quote->excludedDates !== [])
        <p class="mt-2 text-xs text-secondary-medium dark:text-ink-muted">
            {{ trans_choice('hr_leave.ui.impact.skipped', count($quote->excludedDates), ['count' => count($quote->excludedDates)]) }}
        </p>
    @endif

    @foreach ($quote->errors as $code)
        <p class="form-error mt-1" wire:key="quote-error-{{ $code }}">{{ __(\Modules\Hr\Support\Leave\LeaveQuote::messageKey($code)) }}</p>
    @endforeach

    @foreach ($quote->warnings as $code)
        <p class="mt-1 text-xs text-status-warn" wire:key="quote-warning-{{ $code }}">{{ __(\Modules\Hr\Support\Leave\LeaveQuote::messageKey($code)) }}</p>
    @endforeach
</div>
