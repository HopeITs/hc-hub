<?php

declare(strict_types=1);

namespace Modules\Hr\Enums;

/**
 * Who a comment on a request is addressed to.
 */
enum RecruitmentCommentType: int
{
    case General = 1;

    case DecisionReason = 2;

    /**
     * @return array<string, mixed>
     */
    public static function getLocalizedName(int $value): array
    {
        $val = match ($value) {
            self::DecisionReason->value => ['text' => ['en' => 'Decision reason', 'ar' => 'سبب القرار'], 'color' => 'secondary-medium'],
            default => ['text' => ['en' => 'General', 'ar' => 'عام'], 'color' => 'secondary-light'],
        };

        return [
            'value' => $val['text'][app()->getLocale()],
            'color' => $val['color'],
        ];
    }
}
