<?php

declare(strict_types=1);

namespace Modules\Hr\Enums;

enum EducationType: int
{
    case Unknown = 0;

    case Primary_School = 1;

    case High_School = 2;

    case Institute = 3;

    case University = 4;

    case Master = 5;

    case PHD = 6;

    public static function getLocalizedName(int $value): string
    {
        $result = match ($value) {
            self::Primary_School->value => ['en' => 'Primary School', 'ar' => 'تعليم أساسي'],
            self::High_School->value => ['en' => 'High School', 'ar' => 'تعليم ثانوي'],
            self::Institute->value => ['en' => 'Institute', 'ar' => 'معهد'],
            self::University->value => ['en' => 'University', 'ar' => 'جامعة'],
            self::Master->value => ['en' => 'Master', 'ar' => 'ماجستير'],
            self::PHD->value => ['en' => 'PhD', 'ar' => 'دكتوراه'],
            default => ['en' => 'Unknown', 'ar' => 'غير معروف'],
        };

        return $result[app()->getLocale()] ?? '';
    }
}
