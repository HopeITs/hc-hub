<?php

declare(strict_types=1);

namespace Modules\Hr\Enums;

/**
 * What happened to a recruitment request, for its timeline (D8).
 *
 * These are the structured audit trail that sits beside activity_log rather
 * than inside it, so the timeline can be filtered by step, scope and decision
 * without unpacking JSON.
 *
 * There is no WorkflowBlocked event: it went with the Blocked state (D17).
 * StepSkipped documents the top-level-requester exception (D27), and the last
 * two belong to the direct_hr path (D15).
 */
enum RecruitmentEventType: int
{
    case RequestCreated = 1;

    case RequestEdited = 2;

    case VersionCreated = 3;

    case RequestSubmitted = 4;

    case WorkflowStarted = 5;

    case WorkflowAdvanced = 6;

    case StepApproved = 7;

    case StepRejected = 8;

    case ChangesRequested = 9;

    case RequestCancelled = 10;

    case RequestApplied = 11;

    case RequestApplicationFailed = 12;

    case PositionChanged = 13;

    case HeadcountChanged = 14;

    case VacancyCreated = 15;

    case VacancyClosed = 16;

    case CampaignStateChanged = 17;

    case JobPostPublished = 18;

    case FormVersionPublished = 19;

    case PublicLinkChanged = 20;

    case StepSkipped = 21;

    case DirectHrOpened = 22;

    case ExternalConfirmationRecorded = 23;

    /**
     * @return array<string, mixed>
     */
    public static function getLocalizedName(int $value): array
    {
        $val = match ($value) {
            self::RequestCreated->value => ['text' => ['en' => 'Request created', 'ar' => 'أُنشئ الطلب'], 'color' => 'secondary-light'],
            self::RequestEdited->value => ['text' => ['en' => 'Request edited', 'ar' => 'عُدّل الطلب'], 'color' => 'secondary-light'],
            self::VersionCreated->value => ['text' => ['en' => 'Version created', 'ar' => 'أُنشئت نسخة'], 'color' => 'secondary-light'],
            self::RequestSubmitted->value => ['text' => ['en' => 'Submitted', 'ar' => 'أُرسل الطلب'], 'color' => 'secondary-medium'],
            self::WorkflowStarted->value => ['text' => ['en' => 'Workflow started', 'ar' => 'بدأ المسار'], 'color' => 'secondary-medium'],
            self::WorkflowAdvanced->value => ['text' => ['en' => 'Workflow advanced', 'ar' => 'تقدّم المسار'], 'color' => 'secondary-medium'],
            self::StepApproved->value => ['text' => ['en' => 'Step approved', 'ar' => 'ووفق على الخطوة'], 'color' => 'primary'],
            self::StepRejected->value => ['text' => ['en' => 'Step rejected', 'ar' => 'رُفضت الخطوة'], 'color' => 'secondary-medium'],
            self::ChangesRequested->value => ['text' => ['en' => 'Changes requested', 'ar' => 'طُلب تعديل'], 'color' => 'secondary-medium'],
            self::RequestCancelled->value => ['text' => ['en' => 'Request cancelled', 'ar' => 'أُلغي الطلب'], 'color' => 'secondary-light'],
            self::RequestApplied->value => ['text' => ['en' => 'Request applied', 'ar' => 'طُبِّق الطلب'], 'color' => 'primary'],
            self::RequestApplicationFailed->value => ['text' => ['en' => 'Application failed', 'ar' => 'فشل التطبيق'], 'color' => 'secondary-medium'],
            self::PositionChanged->value => ['text' => ['en' => 'Position changed', 'ar' => 'تغيّر المنصب'], 'color' => 'secondary-light'],
            self::HeadcountChanged->value => ['text' => ['en' => 'Headcount changed', 'ar' => 'تغيّر الملاك'], 'color' => 'secondary-light'],
            self::VacancyCreated->value => ['text' => ['en' => 'Vacancy created', 'ar' => 'أُنشئ شاغر'], 'color' => 'primary'],
            self::VacancyClosed->value => ['text' => ['en' => 'Vacancy closed', 'ar' => 'أُغلق الشاغر'], 'color' => 'secondary-medium'],
            self::CampaignStateChanged->value => ['text' => ['en' => 'Campaign state changed', 'ar' => 'تغيّرت حالة الحملة'], 'color' => 'secondary-light'],
            self::JobPostPublished->value => ['text' => ['en' => 'Job post published', 'ar' => 'نُشر الإعلان'], 'color' => 'primary'],
            self::FormVersionPublished->value => ['text' => ['en' => 'Form version published', 'ar' => 'نُشرت نسخة النموذج'], 'color' => 'primary'],
            self::PublicLinkChanged->value => ['text' => ['en' => 'Public link changed', 'ar' => 'تغيّر الرابط العام'], 'color' => 'secondary-light'],
            self::StepSkipped->value => ['text' => ['en' => 'Step skipped', 'ar' => 'تُجووزت الخطوة'], 'color' => 'secondary-light'],
            self::DirectHrOpened->value => ['text' => ['en' => 'Opened directly by HR', 'ar' => 'فُتح مباشرة من HR'], 'color' => 'primary'],
            self::ExternalConfirmationRecorded->value => ['text' => ['en' => 'External confirmation recorded', 'ar' => 'سُجّل تأكيد خارجي'], 'color' => 'secondary-light'],
            default => ['text' => ['en' => 'Unknown', 'ar' => 'غير معروف'], 'color' => 'secondary-light'],
        };

        return [
            'value' => $val['text'][app()->getLocale()],
            'color' => $val['color'],
        ];
    }
}
