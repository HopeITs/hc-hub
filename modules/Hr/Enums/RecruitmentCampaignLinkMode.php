<?php

declare(strict_types=1);

namespace Modules\Hr\Enums;

enum RecruitmentCampaignLinkMode: string
{
    /** Staff only: applicants sign in to HC-HR and apply as themselves. */
    case Internal = 'internal';

    case PublicOpen = 'public_open';

    case InvitationOnly = 'invitation_only';

    /**
     * @return array<string, mixed>
     */
    public static function getLocalizedName(string $value): array
    {
        $label = match ($value) {
            self::InvitationOnly->value => ['en' => 'Invitation only', 'ar' => 'للمدعوين فقط'],
            self::Internal->value => ['en' => 'Staff only', 'ar' => 'للموظفين فقط'],
            default => ['en' => 'Public link', 'ar' => 'رابط عام'],
        };

        return ['value' => $label[app()->getLocale()]];
    }
}
