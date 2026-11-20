<?php

declare(strict_types=1);

namespace Modules\Hr\Support\Contracts;

use Modules\Hr\Models\Contract;
use Modules\Hr\Models\Position;
use Modules\Hr\Support\Leave\LeaveRequestPricing;

/**
 * Study Zone's optional "Friday is considered a day off…" sentence. The day
 * is the weekend leave pricing uses (the position's center, else the centers
 * its program shares in the city), so a contract never names a day off that
 * leave requests then charge for. With no center weekend known at all (the
 * code default) there is nothing to state, and nothing is offered or printed.
 */
final class WeekendClause
{
    public const STUDY_ZONE_SLUG = 'SZ';

    public function __construct(private readonly LeaveRequestPricing $pricing) {}

    /**
     * The day(s) off $position's contract may state; empty for other programs.
     *
     * @return list<int>
     */
    public function daysForPosition(?Position $position): array
    {
        if ($position === null || $position->program?->slug !== self::STUDY_ZONE_SLUG) {
            return [];
        }

        $contract = (new Contract)->setRelation('position', $position)->setRelation('employee', null);
        [$weekend, $source] = $this->pricing->weekendFor($contract);

        return $source === 'default' ? [] : array_values($weekend);
    }

    /**
     * The days a printed $contract states: only when HR ticked the clause.
     *
     * @return list<int>
     */
    public function daysForContract(Contract $contract): array
    {
        return $contract->show_weekend_clause ? $this->daysForPosition($contract->position) : [];
    }

    /**
     * The printed sentence in both languages.
     *
     * @param  list<int>  $days
     * @return array{en: string, ar: string}
     */
    public static function sentences(array $days): array
    {
        $namesEn = array_map(fn (int $day): string => trans('hr_contracts.days.'.ContractOptions::WEEK_DAYS[$day], [], 'en'), $days);
        $namesAr = array_map(fn (int $day): string => trans('hr_contracts.days.'.ContractOptions::WEEK_DAYS[$day], [], 'ar'), $days);

        $en = count($days) === 1
            ? "{$namesEn[0]} is considered a day off, unless management deems it necessary, and is not considered overtime."
            : implode(', ', array_slice($namesEn, 0, -1)).' and '.end($namesEn).' are considered days off, unless management deems it necessary, and are not considered overtime.';

        $ar = match (count($days)) {
            1 => "يعتبر يوم {$namesAr[0]} يوم عطلة، إلا في حال رأت الإدارة الحاجة لذلك، ولا يعتبر دوام إضافي.",
            2 => "يعتبر يوما {$namesAr[0]} و{$namesAr[1]} يومي عطلة، إلا في حال رأت الإدارة الحاجة لذلك، ولا يعتبر دوام إضافي.",
            default => 'تعتبر أيام '.implode(' و', $namesAr).' أيام عطلة، إلا في حال رأت الإدارة الحاجة لذلك، ولا يعتبر دوام إضافي.',
        };

        return ['en' => $en, 'ar' => $ar];
    }

    /**
     * The form checkbox label naming the day(s), in the viewer's language.
     *
     * @param  list<int>  $days
     */
    public static function label(array $days): string
    {
        $names = array_map(fn (int $day): string => __('hr_contracts.days.'.ContractOptions::WEEK_DAYS[$day]), $days);

        return __('hr_contracts.fields.show_weekend_clause', ['days' => implode(' / ', $names)]);
    }
}
