<?php

declare(strict_types=1);

namespace Modules\Hr\Enums;

/**
 * How an approval given outside the system was communicated (D43).
 */
enum ExternalConfirmationMethod: int
{
    case Phone = 1;

    case Email = 2;

    case Meeting = 3;

    case Other = 4;

    /**
     * @return array<string, mixed>
     */
    public static function getLocalizedName(int $value): array
    {
        $val = match ($value) {
            self::Phone->value => ['text' => ['en' => 'Phone', 'ar' => 'هاتف'], 'color' => 'secondary-light'],
            self::Email->value => ['text' => ['en' => 'Email', 'ar' => 'بريد إلكتروني'], 'color' => 'secondary-light'],
            self::Meeting->value => ['text' => ['en' => 'Meeting', 'ar' => 'اجتماع'], 'color' => 'secondary-light'],
            self::Other->value => ['text' => ['en' => 'Other', 'ar' => 'أخرى'], 'color' => 'secondary-light'],
            default => ['text' => ['en' => 'Unknown', 'ar' => 'غير معروف'], 'color' => 'secondary-light'],
        };

        return [
            'value' => $val['text'][app()->getLocale()],
            'color' => $val['color'],
        ];
    }
}
