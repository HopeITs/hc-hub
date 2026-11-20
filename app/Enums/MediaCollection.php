<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasTranslatedLabel;

/**
 * What a stored file is, as distinct from its format.
 *
 * The value is what lands in `media.collection` (VARCHAR(50)). This is the
 * display category that `FileType` deliberately is NOT: `FileType` is the file
 * extension and the media `path` is built from it, so the two never merge.
 *
 * Values are lower case and casing is part of the value.
 */
enum MediaCollection: string
{
    use HasTranslatedLabel;

    case ConceptNote = 'concept_note';
    case Proposal = 'proposal';
    case Agreement = 'agreement';
    case Mou = 'mou';
    case Photo = 'photo';
    case Evidence = 'evidence';
    case Other = 'other';
    case FinalReport = 'final_report';
    case FinalReportAcceptance = 'final_report_acceptance';
    case NeedAssessment = 'need assessment';
    case DonorApproval = 'donor_approval';
    case ReportSubmission = 'report_submission';
    case Negotiation = 'negotiation';
    case Amendment = 'amendment';
    case Avatar = 'avatar';
    case EmployeeAttachment = 'employee_attachment';
    case Credential = 'credential';
    case ArchiveFile = 'archive_file';
    case NewsImage = 'news_image';

    /**
     * Whether only a staff record holds files of this collection: a person's
     * photo and papers, the certificate behind a qualification, an HR archive
     * folder's files, a news item's picture (roadmap 4.10). Project and
     * proposal file pickers never offer it.
     */
    public function isStaffRecordOnly(): bool
    {
        return in_array($this, [self::Avatar, self::EmployeeAttachment, self::Credential, self::ArchiveFile, self::NewsImage], true);
    }

    /**
     * Whether this collection holds signed funding paperwork.
     *
     * An agreement or MOU can state the budget, so it is agreement data:
     * only someone holding funding-agreements.view may list or open it.
     */
    public function isFundingPaper(): bool
    {
        return in_array($this, [self::Agreement, self::Mou], true);
    }

    /**
     * Whether only a donor report holds files of this collection (the file
     * delivered to the donor, contracts §14.2), so project and proposal file
     * pickers never offer it.
     */
    public function isDonorReportOnly(): bool
    {
        return $this === self::ReportSubmission;
    }

    /**
     * All media collection values, for validation rules and console prompts.
     *
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
