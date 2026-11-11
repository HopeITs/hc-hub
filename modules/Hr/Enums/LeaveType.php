<?php

declare(strict_types=1);

namespace Modules\Hr\Enums;

class LeaveType
{
    const GENDER_ANY = 0;

    const GENDER_MALE = 1;

    const GENDER_FEMALE = 2;

    const MARITAL_STATUS_ANY = 0;

    const MARITAL_STATUS_SINGLE = 1;

    const MARITAL_STATUS_MARRIED = 2;

    /**
     * @return array{value: mixed, color: string}
     */
    public static function getGender(?int $status): array
    {

        $val = match ($status) {
            self::GENDER_MALE => ['key' => 'hr_leave.gender.male', 'color' => 'secondary-medium'],
            self::GENDER_FEMALE => ['key' => 'hr_leave.gender.female', 'color' => 'secondary-medium'],
            default => ['key' => 'hr_leave.gender.any', 'color' => 'secondary-dark'],
        };
        $result = [
            'value' => __($val['key']),
            'color' => $val['color'],
        ];

        return $result;

    }

    /**
     * @return array{value: mixed, color: string}
     */
    public static function getMaritalStatus(?int $status): array
    {

        $val = match ($status) {
            self::MARITAL_STATUS_SINGLE => ['key' => 'hr_leave.marital.single', 'color' => 'secondary-medium'],
            self::MARITAL_STATUS_MARRIED => ['key' => 'hr_leave.marital.married', 'color' => 'secondary-light'],
            default => ['key' => 'hr_leave.marital.any', 'color' => 'secondary-dark'],
        };
        $result = [
            'value' => __($val['key']),
            'color' => $val['color'],
        ];

        return $result;

    }
}
