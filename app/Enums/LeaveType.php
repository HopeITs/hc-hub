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

        $val =  match ($status) {
            self::GENDER_MALE => ['text' => ['en' => 'Male', 'ar' => 'ذكر'] , 'color' => 'blue'],
            self::GENDER_FEMALE => ['text' => ['en' => 'Female', 'ar' => 'انثى'] , 'color' => 'pink'],
            default =>['text' =>  ['en' => 'All', 'ar' => 'الكل '],'color' => 'purple'],
        };
        $result = [
            'value' => $val['text'][app()->getLocale()],
            'color' => $val['color'],
        ];


        return $result;

    }

    public static function getMaritalStatus(?int $status): array
    {
        return match ($status) {
            self::MARITAL_STATUS_SINGLE => ['value' => __('general.single'), 'color' => 'yellow'],
            self::MARITAL_STATUS_MARRIED => ['value' => __('general.married'), 'color' => 'purple'],
            default => ['value' => __('general.any'), 'color' => 'green'],
        };
    }
}
