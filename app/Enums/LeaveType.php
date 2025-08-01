<?php

namespace App\Enums;

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
        return match ($status) {
            self::GENDER_MALE => ['value' => __('general.male'), 'color' => 'green'],
            self::GENDER_FEMALE => ['value' => __('general.female'), 'color' => 'green'],
            default => ['value' => __('general.any'), 'color' => 'gray'],
        };
    }

    public static function getMaritalStatus(?int $status): array
    {
        return match ($status) {
            self::MARITAL_STATUS_SINGLE => ['value' => __('general.single'), 'color' => 'green'],
            self::MARITAL_STATUS_MARRIED => ['value' => __('general.married'), 'color' => 'green'],
            default => ['value' => __('general.any'), 'color' => 'gray'],
        };
    }
}
