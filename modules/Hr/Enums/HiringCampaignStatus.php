<?php

declare(strict_types=1);

namespace Modules\Hr\Enums;

/**
 * Campaign state.

 * Published and Open are deliberately separate (D3): Published means the advert
 * is visible through the public link, Open means the application window is
 * actually accepting submissions. Draft and Disabled are the only two the
 * public never sees (D48).
 */
enum HiringCampaignStatus: int
{
    case Draft = 0;

    case Published = 1;

    case Open = 2;

    case Closed = 3;

    case Expired = 4;

    case Disabled = 5;

    /**
     * @return array<string, mixed>
     */
    public static function getLocalizedName(int $value): array
    {
        $val = match ($value) {
            self::Published->value => ['text' => ['en' => 'Published', 'ar' => 'منشورة'], 'color' => 'secondary-medium'],
            self::Open->value => ['text' => ['en' => 'Open', 'ar' => 'مفتوحة'], 'color' => 'primary'],
            self::Closed->value => ['text' => ['en' => 'Closed', 'ar' => 'مغلقة'], 'color' => 'secondary-medium'],
            self::Expired->value => ['text' => ['en' => 'Expired', 'ar' => 'منتهية'], 'color' => 'secondary-medium'],
            self::Disabled->value => ['text' => ['en' => 'Disabled', 'ar' => 'معطّلة'], 'color' => 'secondary-light'],
            default => ['text' => ['en' => 'Draft', 'ar' => 'مسودة'], 'color' => 'secondary-light'],
        };

        return [
            'value' => $val['text'][app()->getLocale()],
            'color' => $val['color'],
        ];
    }
}
