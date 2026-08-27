<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class EducationStatus extends Enum
{
    const Graduated = 0;

    const Student = 1;

    const Stopped = 2;

    public static function getLocalizedName(int $value): string
    {
        $result = match ($value) {
            self::Student => ['en' => 'Student', 'ar' => 'طالب'],
            self::Stopped => ['en' => 'Stopped', 'ar' => 'منقطع'],
            default => ['en' => 'Graduated', 'ar' => 'متخرج'],
        };

        return $result[app()->getLocale()];
    }
}
