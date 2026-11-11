<?php

declare(strict_types=1);

namespace Modules\Hr\Support\Contracts;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Modules\Hr\Enums\ContractType as ContractTypeEnum;
use Modules\Hr\Enums\Currency;
use Modules\Hr\Enums\PayMode;
use Modules\Hr\Enums\SalaryPer;
use Modules\Hr\Enums\ScheduleMode;
use Modules\Hr\Enums\SessionDuration;
use Modules\Hr\Models\ContractAmendment;
use Modules\Hr\Models\ContractAmendmentPayTier;
use Modules\Hr\Support\Employees\EnumLabel;

/**
 * An amendment's changes in words, for the screens: the amendments page and
 * the amendment form's notice. The printed sheet words them through the
 * contract's own clause components instead.
 *
 * Only what a person chose is named: the print toggles are presentation, not
 * terms; a fixed-mode time is not named once the schedule is flexible, nor a
 * flexible box once it is fixed; a term with no value before has no "before".
 * Reads the amendment's payTiers relation, so eager load it for a list.
 */
final class ContractAmendmentDescriber
{
    /**
     * One line per term that changed: [label, before, after].
     *
     * @return list<array{0: string, 1: string, 2: string}>
     */
    public function lines(ContractAmendment $amendment, bool $withPay): array
    {
        return array_values(collect($this->terms($amendment, $withPay))
            ->filter(fn (array $term): bool => $term['changed'])
            ->map(fn (array $term): array => [$term['label'], $term['before'], $term['after']])
            ->all());
    }

    /**
     * Every term the contract runs on after this amendment, changed or not,
     * so an amendment reads on its own. `before` is filled only when changed.
     *
     * @return list<array{label: string, after: string, before: string, changed: bool}>
     */
    public function terms(ContractAmendment $amendment, bool $withPay): array
    {
        $changed = $amendment->changedFields();
        $has = fn (string ...$fields): bool => array_intersect($fields, $changed) !== [];
        $before = $amendment->terms_before ?? [];
        $after = $amendment->terms_after ?? [];
        $terms = [];

        $add = function (string $label, string $from, string $to, bool $moved) use (&$terms): void {
            if ($to === '') {
                return;
            }

            $moved = $moved && $from !== $to;
            $terms[] = ['label' => $label, 'after' => $to, 'before' => $moved ? $from : '', 'changed' => $moved];
        };

        $add(__('hr_contracts.fields.type'), $this->type($before), $this->type($after), $has('type'));
        $add(__('hr_contracts.fields.schedule_mode'), $this->scheduleMode($before), $this->scheduleMode($after), $has('schedule_mode'));
        $add(__('hr_contracts.fields.weekly_hours'), $this->weeklyHours($before), $this->weeklyHours($after), $has('weekly_hours'));

        $modeMoved = $has('schedule_mode');

        if ($this->isFlexible($after)) {
            $add(__('hr_contract_amendments.terms.flexible'), $this->isFlexible($before) ? $this->flexible($before) : '', $this->flexible($after),
                $modeMoved || $has('flex_days_per_week', 'flex_hours_per_week', 'flex_hours_per_month', 'flex_varies_by_requirements',
                    'flex_coordinated_with_manager', 'flex_custom_en', 'flex_custom_ar'));
        } else {
            $wasFixed = ! $this->isFlexible($before);

            $add(__('hr_contract_amendments.terms.hours'), $wasFixed ? $this->shifts($before) : '', $this->shifts($after),
                $modeMoved || $has('work_hours_from', 'work_hours_to', 'work_hours_2_from', 'work_hours_2_to'));
            $add(__('hr_contract_amendments.terms.days'), $wasFixed ? $this->days($before) : '', $this->days($after),
                $modeMoved || $has('work_day_from', 'work_day_to'));
        }

        if ($withPay) {
            if ($has('pay_mode')) {
                $add(__('hr_contracts.fields.pay_mode'), $this->payMode($before), $this->payMode($after), true);
            }

            if ($this->isFlexiblePay($after)) {
                $add(__('hr_contracts.fields.pay_tiers'),
                    $this->isFlexiblePay($before) ? $this->tiers($this->side($amendment, ContractAmendmentPayTier::SIDE_BEFORE), $before) : '',
                    $this->tiers($this->side($amendment, ContractAmendmentPayTier::SIDE_AFTER), $after),
                    $has('pay_tiers', 'currency', 'pay_mode'));
            } else {
                $add(__('hr_contracts.fields.salary'),
                    $this->isFlexiblePay($before) ? '' : $this->money($amendment->salary_before, $before),
                    $this->money($amendment->salary_after, $after),
                    $has('salary', 'currency', 'salary_per', 'session_duration', 'pay_mode'));
            }
        }

        return $terms;
    }

    /**
     * One line: "Weekly hours: 24 hours a week (was 40 hours a week) · …".
     */
    public function summary(ContractAmendment $amendment, bool $withPay): string
    {
        return collect($this->lines($amendment, $withPay))
            ->map(fn (array $line): string => $line[0].': '.$line[2].($line[1] !== '' ? ' ('.__('hr_contract_amendments.terms.was', ['value' => $line[1]]).')' : ''))
            ->implode(' · ');
    }

    /**
     * @param  array<string, mixed>  $t
     */
    private function type(array $t): string
    {
        $type = (int) ($t['type'] ?? 0);

        return $type === 0 || $type === ContractTypeEnum::Draft->value ? '' : EnumLabel::of(ContractTypeEnum::class, $type, '');
    }

    /**
     * @param  array<string, mixed>  $t
     */
    private function scheduleMode(array $t): string
    {
        return __($this->isFlexible($t) ? 'hr_contracts.schedule.flexible' : 'hr_contracts.schedule.fixed');
    }

    /**
     * @param  array<string, mixed>  $t
     */
    private function isFlexible(array $t): bool
    {
        return (int) ($t['schedule_mode'] ?? ScheduleMode::Fixed->value) === ScheduleMode::Flexible->value;
    }

    /**
     * @param  array<string, mixed>  $t
     */
    private function weeklyHours(array $t): string
    {
        $hours = (int) ($t['weekly_hours'] ?? 0);

        return $hours > 0 ? trans_choice('hr_contract_amendments.terms.weekly_hours', $hours, ['count' => $hours]) : '';
    }

    /**
     * @param  array<string, mixed>  $t
     */
    private function shifts(array $t): string
    {
        $time = fn (mixed $v): ?string => filled($v) ? substr((string) $v, 0, 5) : null;
        // Isolated left-to-right: in an Arabic line a bare "09:00–15:00" is laid out backwards.
        $shift = fn (string $from, string $to): ?string => $time($t[$from] ?? null) && $time($t[$to] ?? null)
            ? "\u{2066}".$time($t[$from]).'–'.$time($t[$to])."\u{2069}"
            : null;

        return collect([$shift('work_hours_from', 'work_hours_to'), $shift('work_hours_2_from', 'work_hours_2_to')])
            ->filter()->implode(' · ');
    }

    /**
     * Days are stored Sunday = 0 … Saturday = 6 (Carbon's numbering).
     *
     * @param  array<string, mixed>  $t
     */
    private function days(array $t): string
    {
        if (! is_numeric($t['work_day_from'] ?? null) || ! is_numeric($t['work_day_to'] ?? null)) {
            return '';
        }

        $name = fn (int $d): string => Carbon::now()->startOfWeek(Carbon::SUNDAY)->addDays($d)->translatedFormat('l');

        return (int) $t['work_day_from'] === (int) $t['work_day_to']
            ? $name((int) $t['work_day_from'])
            : $name((int) $t['work_day_from']).' – '.$name((int) $t['work_day_to']);
    }

    /**
     * @param  array<string, mixed>  $t
     */
    private function flexible(array $t): string
    {
        $locale = app()->getLocale() === 'ar' ? 'ar' : 'en';

        return collect([
            filled($t['flex_hours_per_week'] ?? null) ? trans_choice('hr_contract_amendments.terms.weekly_hours', (int) $t['flex_hours_per_week'], ['count' => (int) $t['flex_hours_per_week']]) : null,
            filled($t['flex_days_per_week'] ?? null) ? trans_choice('hr_contract_amendments.terms.days_per_week', (int) $t['flex_days_per_week'], ['count' => (int) $t['flex_days_per_week']]) : null,
            filled($t['flex_hours_per_month'] ?? null) ? trans_choice('hr_contract_amendments.terms.monthly_hours', (int) $t['flex_hours_per_month'], ['count' => (int) $t['flex_hours_per_month']]) : null,
            ! empty($t['flex_varies_by_requirements']) ? __('hr_contract_amendments.terms.flex_varies') : null,
            ! empty($t['flex_coordinated_with_manager']) ? __('hr_contract_amendments.terms.flex_coordinated') : null,
            filled($t["flex_custom_{$locale}"] ?? null) ? trim((string) $t["flex_custom_{$locale}"]) : null,
        ])->filter()->implode(' · ');
    }

    /**
     * @param  array<string, mixed>  $t
     */
    private function payMode(array $t): string
    {
        return EnumLabel::of(PayMode::class, $this->isFlexiblePay($t) ? PayMode::Flexible->value : PayMode::Fixed->value);
    }

    /**
     * @param  array<string, mixed>  $t
     */
    private function isFlexiblePay(array $t): bool
    {
        return (int) ($t['pay_mode'] ?? PayMode::Fixed->value) === PayMode::Flexible->value;
    }

    /**
     * @param  array<string, mixed>  $t
     */
    private function money(mixed $amount, array $t): string
    {
        if (blank($amount)) {
            return '';
        }

        $per = EnumLabel::of(SalaryPer::class, (int) ($t['salary_per'] ?? SalaryPer::Month->value), '');

        $session = (int) ($t['salary_per'] ?? 0) === SalaryPer::Session->value && filled($t['session_duration'] ?? null)
            ? ' ('.EnumLabel::of(SessionDuration::class, (int) $t['session_duration']).')'
            : '';

        return trim($this->amount($amount).' '.$this->currency($t).($per !== '' ? ' / '.$per : '').$session);
    }

    /**
     * @param  Collection<int, ContractAmendmentPayTier>  $tiers
     * @param  array<string, mixed>  $t
     */
    private function tiers(Collection $tiers, array $t): string
    {
        $locale = app()->getLocale() === 'ar' ? 'ar' : 'en';

        return $tiers->map(fn (ContractAmendmentPayTier $tier): string => trim((string) $tier->{"condition_{$locale}"}).': '.$this->amount($tier->amount).' '.$this->currency($t))
            ->implode(' · ');
    }

    /**
     * @return Collection<int, ContractAmendmentPayTier>
     */
    private function side(ContractAmendment $amendment, int $side): Collection
    {
        $tiers = $amendment->relationLoaded('payTiers') ? $amendment->payTiers : $amendment->payTiers()->get();

        return $tiers->where('side', $side)->sortBy('sort_order')->values();
    }

    private function amount(mixed $amount): string
    {
        $value = (float) $amount;

        return number_format($value, floor($value) === $value ? 0 : 2);
    }

    /**
     * @param  array<string, mixed>  $t
     */
    private function currency(array $t): string
    {
        return EnumLabel::of(Currency::class, (int) ($t['currency'] ?? 0), '');
    }
}
