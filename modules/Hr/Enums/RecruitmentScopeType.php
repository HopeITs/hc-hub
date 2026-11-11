<?php

declare(strict_types=1);

namespace Modules\Hr\Enums;

/**
 * How wide a user's reach into recruitment data is.
 *
 * Scope answers "where", separately from the role that answers "what" and the
 * job level that answers "may they raise a request at all". All three are
 * checked; holding a permission without the scope to match it grants nothing.
 */
enum RecruitmentScopeType: int
{
    /** Sees nothing. The default for anyone the resolver cannot place. */
    case None = 0;

    /** Own requests plus those of direct reports, via positions.direct_manager_id. */
    case DirectReports = 1;

    /** One governorate, resolved through Position::scopeInCity. */
    case City = 2;

    /** Every request in the organization. */
    case Organization = 3;

    /**
     * @return array<string, mixed>
     */
    public static function getLocalizedName(int $value): array
    {
        $val = match ($value) {
            self::DirectReports->value => ['text' => ['en' => 'Direct reports', 'ar' => 'المرؤوسون المباشرون'], 'color' => 'secondary-light'],
            self::City->value => ['text' => ['en' => 'Governorate', 'ar' => 'المحافظة'], 'color' => 'primary'],
            self::Organization->value => ['text' => ['en' => 'Organization', 'ar' => 'المنظمة'], 'color' => 'secondary-medium'],
            default => ['text' => ['en' => 'No access', 'ar' => 'لا وصول'], 'color' => 'secondary-light'],
        };

        return [
            'value' => $val['text'][app()->getLocale()],
            'color' => $val['color'],
        ];
    }
}
