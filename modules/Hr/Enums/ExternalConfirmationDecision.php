<?php

declare(strict_types=1);

namespace Modules\Hr\Enums;

/**
 * The outcome recorded for one external confirmation (D43).

 * NotApplicable means this kind of approval does not apply to this case at all
 * — an unpaid volunteer role has no financial approval to document — not that
 * it is still outstanding. It carries a reason and no confirmer.
 */
enum ExternalConfirmationDecision: int
{
    case Approved = 1;

    case NotApplicable = 2;

    /**
     * @return array<string, mixed>
     */
    public static function getLocalizedName(int $value): array
    {
        $val = match ($value) {
            self::Approved->value => ['text' => ['en' => 'Approved', 'ar' => 'موافَق عليه'], 'color' => 'primary'],
            self::NotApplicable->value => ['text' => ['en' => 'Not applicable', 'ar' => 'لا ينطبق'], 'color' => 'secondary-light'],
            default => ['text' => ['en' => 'Unknown', 'ar' => 'غير معروف'], 'color' => 'secondary-light'],
        };

        return [
            'value' => $val['text'][app()->getLocale()],
            'color' => $val['color'],
        ];
    }
}
