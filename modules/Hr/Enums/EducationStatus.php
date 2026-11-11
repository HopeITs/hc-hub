<?php

declare(strict_types=1);

namespace Modules\Hr\Enums;

enum EducationStatus: int
{
    case Graduated = 0;

    case Student = 1;

    case Stopped = 2;

    public static function getLocalizedName(int $value): string
    {
        $result = match ($value) {
            self::Student->value => ['en' => 'Student', 'ar' => 'طالب'],
            self::Stopped->value => ['en' => 'Stopped', 'ar' => 'منقطع'],
            default => ['en' => 'Graduated', 'ar' => 'متخرج'],
        };

        return $result[app()->getLocale()];
    }
}
