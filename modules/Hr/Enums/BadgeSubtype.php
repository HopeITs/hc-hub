<?php

declare(strict_types=1);

namespace Modules\Hr\Enums;

enum BadgeSubtype: string
{
    case WarningOral = 'warning_oral';

    case WarningWritten = 'warning_written';

    case WarningDismissal = 'warning_dismissal';

    case WarningFinancialFine = 'warning_financial_fine';

    case WarningLeaveDeduction = 'warning_leave_deduction';

    case RewardThankYou = 'reward_thank_you';

    case RewardPromotion = 'reward_promotion';

    case RewardFinancial = 'reward_financial';

    case RewardGrantLeave = 'reward_grant_leave';

    /**
     * @return array<string, mixed>
     */
    public static function optionsForType(int|string|null $type): array
    {
        return match ((int) $type) {
            BadgeType::Warning->value => [
                self::WarningOral->value => self::getLocalizedName(self::WarningOral->value),
                self::WarningWritten->value => self::getLocalizedName(self::WarningWritten->value),
                self::WarningDismissal->value => self::getLocalizedName(self::WarningDismissal->value),
                self::WarningFinancialFine->value => self::getLocalizedName(self::WarningFinancialFine->value),
                self::WarningLeaveDeduction->value => self::getLocalizedName(self::WarningLeaveDeduction->value),
            ],
            BadgeType::Reward->value => [
                self::RewardThankYou->value => self::getLocalizedName(self::RewardThankYou->value),
                self::RewardPromotion->value => self::getLocalizedName(self::RewardPromotion->value),
                self::RewardFinancial->value => self::getLocalizedName(self::RewardFinancial->value),
                self::RewardGrantLeave->value => self::getLocalizedName(self::RewardGrantLeave->value),
            ],
            default => [],
        };
    }

    public static function getLocalizedName(?string $value): string
    {
        $res = match ($value) {
            self::WarningOral->value => ['en' => 'Verbal warning', 'ar' => 'إنذار شفهي'],
            self::WarningWritten->value => ['en' => 'Written warning', 'ar' => 'إنذار خطي'],
            self::WarningDismissal->value => ['en' => 'Termination', 'ar' => 'فصل من العمل'],
            self::WarningFinancialFine->value => ['en' => 'Financial penalty (electronics accounting system)', 'ar' => 'غرامة مالية (نظام محاسبة الإلكترونيات)'],
            self::WarningLeaveDeduction->value => ['en' => 'Deduct leave balance', 'ar' => 'خصم من رصيد الإجازات'],
            self::RewardThankYou->value => ['en' => 'Appreciation certificate and thank-you email', 'ar' => 'شهادة تقدير مع رسالة شكر إلكترونية'],
            self::RewardPromotion->value => ['en' => 'Position promotion', 'ar' => 'ترقية وظيفية'],
            self::RewardFinancial->value => ['en' => 'Financial bonus', 'ar' => 'مكافأة مالية'],
            self::RewardGrantLeave->value => ['en' => 'Grant additional leave', 'ar' => 'منح إجازة إضافية'],
            default => ['en' => 'Unknown', 'ar' => 'غير معروف'],
        };

        return $res[app()->getLocale()] ?? $res['en'];
    }

    public static function selectorLabelForType(int|string|null $type): string
    {
        return match ((int) $type) {
            BadgeType::Warning->value => __('general.penalty-type'),
            BadgeType::Reward->value => __('general.reward-type'),
            default => __('general.badge-detail'),
        };
    }

    public static function isFinancial(?string $value): bool
    {
        return in_array($value, [
            self::WarningFinancialFine->value,
            self::RewardFinancial->value,
        ], true);
    }

    public static function isLeave(?string $value): bool
    {
        return in_array($value, [
            self::WarningLeaveDeduction->value,
            self::RewardGrantLeave->value,
        ], true);
    }

    public static function isPromotion(?string $value): bool
    {
        return $value === self::RewardPromotion->value;
    }
}
