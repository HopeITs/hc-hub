<?php

declare(strict_types=1);

namespace Modules\Hr\Support\Contracts;

use Illuminate\Support\Facades\Cache;
use Modules\Hr\Models\ContractType;
use Modules\Hr\Models\TerminationReason;

/**
 * Reference lists for the contract screens, cached per locale: they change a
 * few times a year and every contract page reads them.
 */
final class ContractOptions
{
    /** Stored work_day_from / work_day_to values: Carbon's dayOfWeek, Sunday = 0 (as HR's composer and the Desk clock read them). */
    public const WEEK_DAYS = [0 => 'sunday', 1 => 'monday', 2 => 'tuesday', 3 => 'wednesday', 4 => 'thursday', 5 => 'friday', 6 => 'saturday'];

    /**
     * @return array<int, string>
     */
    public static function types(): array
    {
        return Cache::remember('hr.contract-types.'.app()->getLocale(), now()->addHour(), fn (): array => ContractType::query()
            ->select(['id', 'name_en', 'name_ar'])
            ->orderBy('id')
            ->get()
            ->mapWithKeys(fn (ContractType $type): array => [$type->id => (string) $type->name])
            ->all());
    }

    /**
     * @return array<int, string>
     */
    public static function terminationReasons(): array
    {
        return Cache::remember('hr.termination-reasons.'.app()->getLocale(), now()->addHour(), fn (): array => TerminationReason::query()
            ->select(['id', 'title_en', 'title_ar'])
            ->orderBy('id')
            ->get()
            ->mapWithKeys(fn (TerminationReason $reason): array => [$reason->id => (string) $reason->title])
            ->all());
    }

    public static function category(?int $contractTypeId): string
    {
        return config('contracts.map_by_id', ContractType::CATEGORY_BY_ID)[$contractTypeId] ?? 'annual';
    }

    public static function typeName(?int $contractTypeId): string
    {
        return self::types()[$contractTypeId] ?? '—';
    }
}
