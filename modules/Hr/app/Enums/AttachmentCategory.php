<?php

namespace Modules\Hr\Enums;

enum AttachmentCategory: string
{
    case PERSONAL = 'personal_documents';
    case EDUCATION = 'education_certificate';
    case EXPERIENCE = 'experience_certificate';
    case CV = 'cv';
    case CONTRACT = 'contract';
    case OTHER = 'other';

    public function label(): string
    {
        return match ($this) {
            self::PERSONAL => __('general.personal_documents'),
            self::EDUCATION => __('general.education_certificate'),
            self::EXPERIENCE => __('general.experience_certificate'),
            self::CV => __('general.cv'),
            self::CONTRACT => __('general.contract'),
            self::OTHER => __('general.other'),
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn ($case) => [
                $case->value => $case->label(),
            ])
            ->toArray();
    }
}
