<?php

namespace Modules\Hr\Enums;

class LeaveType
{
    const GENDER_ANY = 0;

    const GENDER_MALE = 1;

    const GENDER_FEMALE = 2;

    const MARITAL_STATUS_ANY = 0;

    const MARITAL_STATUS_SINGLE = 1;

    const MARITAL_STATUS_MARRIED = 2;

    public static function getGender(?int $status): array
    {

        $val = match ($status) {
            self::GENDER_MALE => ['key' => 'leaves.gender.male', 'color' => 'blue'],
            self::GENDER_FEMALE => ['key' => 'leaves.gender.female', 'color' => 'yellow'],
            default => ['key' => 'leaves.gender.any', 'color' => 'green'],
        };
        $result = [
            'value' => __($val['key']),
            'color' => $val['color'],
        ];

        return $result;

    }

    public static function getMaritalStatus(?int $status): array
    {

        $val = match ($status) {
            self::MARITAL_STATUS_SINGLE => ['key' => 'leaves.marital.single', 'color' => 'yellow'],
            self::MARITAL_STATUS_MARRIED => ['key' => 'leaves.marital.married', 'color' => 'gray'],
            default => ['key' => 'leaves.marital.any', 'color' => 'green'],
        };
        $result = [
            'value' => __($val['key']),
            'color' => $val['color'],
        ];

        return $result;

    }
}
