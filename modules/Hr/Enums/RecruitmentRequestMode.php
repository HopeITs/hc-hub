<?php

declare(strict_types=1);

namespace Modules\Hr\Enums;

/**
 * Which path a request takes (D15).

 * Normal walks the full three-step approval path. DirectHr is for openings
 * arranged outside the system, where the approvals are documented as external
 * confirmations rather than collected electronically.
 */
enum RecruitmentRequestMode: int
{
    case Normal = 1;

    case DirectHr = 2;

    /**
     * @return array<string, mixed>
     */
    public static function getLocalizedName(int $value): array
    {
        $val = match ($value) {
            self::DirectHr->value => ['text' => ['en' => 'Direct HR opening', 'ar' => 'فتح مباشر'], 'color' => 'primary'],
            default => ['text' => ['en' => 'Normal', 'ar' => 'عادي'], 'color' => 'secondary-light'],
        };

        return [
            'value' => $val['text'][app()->getLocale()],
            'color' => $val['color'],
        ];
    }
}
