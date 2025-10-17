<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class LeaveRequestStatus extends Enum
{
    const Pending = 0;
    const Accepted = 1;
    const Rejected = 2;

    public static function getLocalizedName(int $value): array
    {
        $val =  match ($value) {
            self::Accepted => ['text' => ['en' => 'Accepted', 'ar' => 'مقبول'] , 'color' => 'green'],
            self::Rejected => ['text' => ['en' => 'Rejected', 'ar' => 'مرفوض'] , 'color' => 'red'],
            default =>['text' =>  ['en' => 'Pending', 'ar' => 'قيد المراجعة'],'color' => 'purple'],
        };
        $result = [
            'value' => $val['text'][app()->getLocale()],
            'color' => $val['color'],
        ];


        return $result;
    }
}
