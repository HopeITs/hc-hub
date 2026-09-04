<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class BadgeSubtype extends Enum
{
    const WarningOral = 'warning_oral';

    const WarningWritten = 'warning_written';

    const WarningDismissal = 'warning_dismissal';

    const WarningFinancialFine = 'warning_financial_fine';

    const WarningLeaveDeduction = 'warning_leave_deduction';

    const RewardThankYou = 'reward_thank_you';

    const RewardPromotion = 'reward_promotion';

    const RewardFinancial = 'reward_financial';

    const RewardGrantLeave = 'reward_grant_leave';

    public static function optionsForType(int|string|null $type): array
    {
        return match ((int) $type) {
            BadgeType::Warning => [
                self::WarningOral => self::getLocalizedName(self::WarningOral),
                self::WarningWritten => self::getLocalizedName(self::WarningWritten),
                self::WarningDismissal => self::getLocalizedName(self::WarningDismissal),
                self::WarningFinancialFine => self::getLocalizedName(self::WarningFinancialFine),
                self::WarningLeaveDeduction => self::getLocalizedName(self::WarningLeaveDeduction),
            ],
            BadgeType::Reward => [
                self::RewardThankYou => self::getLocalizedName(self::RewardThankYou),
                self::RewardPromotion => self::getLocalizedName(self::RewardPromotion),
                self::RewardFinancial => self::getLocalizedName(self::RewardFinancial),
                self::RewardGrantLeave => self::getLocalizedName(self::RewardGrantLeave),
            ],
            default => [],
        };
    }

    public static function getLocalizedName(?string $value): string
    {
        $res = match ($value) {
            self::WarningOral => ['en' => 'Verbal warning', 'ar' => 'إنذار شفهي'],
            self::WarningWritten => ['en' => 'Written warning', 'ar' => 'إنذار خطي'],
            self::WarningDismissal => ['en' => 'Termination', 'ar' => 'فصل من العمل'],
            self::WarningFinancialFine => ['en' => 'Financial penalty (electronics accounting system)', 'ar' => 'غرامة مالية (نظام محاسبة الإلكترونيات)'],
            self::WarningLeaveDeduction => ['en' => 'Deduct leave balance', 'ar' => 'خصم من رصيد الإجازات'],
            self::RewardThankYou => ['en' => 'Appreciation certificate and thank-you email', 'ar' => 'شهادة تقدير مع رسالة شكر إلكترونية'],
            self::RewardPromotion => ['en' => 'Position promotion', 'ar' => 'ترقية وظيفية'],
            self::RewardFinancial => ['en' => 'Financial bonus', 'ar' => 'مكافأة مالية'],
            self::RewardGrantLeave => ['en' => 'Grant additional leave', 'ar' => 'منح إجازة إضافية'],
            default => ['en' => 'Unknown', 'ar' => 'غير معروف'],
        };

        return $res[app()->getLocale()] ?? $res['en'];
    }

    public static function selectorLabelForType(int|string|null $type): string
    {
        return match ((int) $type) {
            BadgeType::Warning => __('general.penalty-type'),
            BadgeType::Reward => __('general.reward-type'),
            default => __('general.badge-detail'),
        };
    }

    public static function isFinancial(?string $value): bool
    {
        return in_array($value, [
            self::WarningFinancialFine,
            self::RewardFinancial,
        ], true);
    }

    public static function isLeave(?string $value): bool
    {
        return in_array($value, [
            self::WarningLeaveDeduction,
            self::RewardGrantLeave,
        ], true);
    }

    public static function isPromotion(?string $value): bool
    {
        return $value === self::RewardPromotion;
    }
}
