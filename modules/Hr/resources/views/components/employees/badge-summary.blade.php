@props(['badge', 'showPay' => false])

{{-- One badge in words: its type and kind, the date, then the details its kind carries. Pay figures only when showPay. --}}
@php
    use Modules\Hr\Enums\BadgeSubtype;
    use Modules\Hr\Enums\BadgeType;
    use Modules\Hr\Enums\Currency;
    use Modules\Hr\Support\Employees\EnumLabel;

    $type = (int) $badge->type;
    $tone = match ($type) {
        BadgeType::Warning->value => 'bg-status-critical-soft text-status-critical',
        BadgeType::Reward->value => 'bg-status-good-soft text-status-good',
        default => 'bg-secondary-soft text-secondary-dark dark:bg-surface-2 dark:text-ink',
    };
    $muted = 'text-secondary-medium dark:text-ink-muted';
    $details = [];

    if (BadgeSubtype::isFinancial($badge->subtype) && $showPay && $badge->amount !== null) {
        $details[] = number_format((float) $badge->amount, 2).' '.EnumLabel::of(Currency::class, $badge->currency, '');
    }

    if (BadgeSubtype::isPromotion($badge->subtype) && filled($badge->new_position)) {
        $details[] = __('hr_employees.badges.new_position').': '.$badge->new_position;
    }

    if (BadgeSubtype::isLeave($badge->subtype) && $badge->amount !== null) {
        $details[] = trans_choice('hr_employees.badges.leave_'.($badge->leave_unit === 'hour' ? 'hours' : 'days'), (int) $badge->amount, ['count' => (int) $badge->amount]);
    }

    if ($type === BadgeType::Salary_Increase->value && $showPay && ($badge->current_salary !== null || $badge->new_salary !== null)) {
        $details[] = number_format((float) $badge->current_salary).' → '.number_format((float) $badge->new_salary).' '.EnumLabel::of(Currency::class, $badge->currency, '');
    }
@endphp

<div {{ $attributes }}>
    <p class="flex flex-wrap items-center gap-2">
        <span class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-semibold {{ $tone }}">{{ BadgeType::getLocalizedName($type) }}</span>
        @if (filled($badge->subtype))
            <span class="text-sm font-semibold text-secondary-dark dark:text-ink">{{ BadgeSubtype::getLocalizedName($badge->subtype) }}</span>
        @endif
        <span class="text-xs {{ $muted }}">{{ $badge->date?->translatedFormat(__('formats.date')) }}</span>
    </p>
    @if ($details !== [])
        <p class="mt-1 text-sm text-secondary-dark dark:text-ink" dir="auto">{{ implode(' · ', $details) }}</p>
    @endif
    @if (filled($badge->reason))
        <p class="mt-1 text-sm {{ $muted }}" dir="auto">{{ $badge->reason }}</p>
    @endif
    @if (filled($badge->notes))
        <p class="mt-1 text-sm {{ $muted }}" dir="auto">{{ $badge->notes }}</p>
    @endif
</div>
