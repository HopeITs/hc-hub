<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class RecruitmentCampaignLinkMode extends Enum
{
    const PublicOpen = 'public_open';

    const InvitationOnly = 'invitation_only';

    public static function getLocalizedName(string $value): array
    {
        $label = match ($value) {
            self::InvitationOnly => ['en' => 'Invitation only', 'ar' => 'للمدعوين فقط'],
            default => ['en' => 'Public link', 'ar' => 'رابط عام'],
        };

        return ['value' => $label[app()->getLocale()]];
    }
}
