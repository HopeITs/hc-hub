@props(['planned' => null, 'actual' => null, 'done' => false])

@php
    use Illuminate\Support\Carbon;

    $today = Carbon::today();

    if ($planned === null) {
        $text = __('common.unscheduled');
        $tone = 'neutral';
    } elseif ($done) {
        if ($actual === null) {
            $text = __('common.completed');
            $tone = 'good';
        } elseif ($actual->lte($planned)) {
            $text = __('ui.schedule_on_time');
            $tone = 'good';
        } else {
            $days = abs($planned->diffInDays($actual));
            $text = trans_choice('ui.schedule_days_late', $days, ['count' => $days]);
            $tone = 'warn';
        }
    } elseif ($planned->lt($today)) {
        $days = abs($planned->diffInDays($today));
        $text = trans_choice('ui.schedule_overdue_by', $days, ['count' => $days]);
        $tone = 'critical';
    } elseif (abs($planned->diffInDays($today)) <= 7) {
        $days = abs($planned->diffInDays($today));
        $text = trans_choice('ui.schedule_due_in', $days, ['count' => $days]);
        $tone = 'warn';
    } else {
        $text = __('ui.schedule_due_date', ['date' => $planned->translatedFormat(__('formats.date'))]);
        $tone = 'neutral';
    }

    $textClasses = match ($tone) {
        'good' => 'text-status-good',
        'warn' => 'text-status-warn',
        'critical' => 'text-primary dark:text-brand-text',
        default => 'text-secondary-medium dark:text-ink-muted',
    };

    // Greys and one red (D1): the icon shape, not the hue, tells on time, close and overdue apart.
    $icon = match ($tone) {
        'good' => 'check-circle',
        'warn' => 'clock',
        'critical' => 'exclamation-triangle',
        default => 'calendar',
    };
@endphp

<span {{ $attributes->class('inline-flex items-center gap-1.5 type-meta font-semibold '.$textClasses) }}>
    <flux:icon :name="$icon" variant="micro" class="size-3.5 shrink-0" aria-hidden="true" />
    {{ $text }}
</span>
