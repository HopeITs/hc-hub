<?php

use App\Enums\RecruitmentApplicationStatus as Status;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Converts recruitment_applications.status from the string values it shipped
 * with to the integer values RecruitmentApplicationStatus now uses, matching
 * every other Recruitment status column (RecruitmentRequestStatus,
 * HiringCampaignStatus, ...).
 *
 * Team 1's original table (2026_09_15_000004) is left untouched — this is a
 * separate, additive migration that ALTERs the column in place, which
 * preserves its existing indexes (rapp_status_idx, rapp_campaign_status_idx,
 * rapp_vacancy_status_idx) since the column itself is never dropped.
 *
 * Existing rows are remapped by their old string value before the column
 * type changes, so no live data (there should be none outside dev testing
 * yet) is silently coerced to 0 by MySQL's implicit VARCHAR->INT cast.
 */
return new class extends Migration
{
    /** @var array<string, int> */
    private const MAP = [
        'submitted' => Status::Submitted,
        'under_review' => Status::UnderReview,
        'shortlisted' => Status::Shortlisted,
        'rejected' => Status::Rejected,
        'interview_scheduled' => Status::InterviewScheduled,
        'interviewed' => Status::Interviewed,
        'selected' => Status::Selected,
        'offer_sent' => Status::OfferSent,
        'offer_accepted' => Status::OfferAccepted,
        'offer_rejected' => Status::OfferRejected,
        'hired' => Status::Hired,
        'withdrawn' => Status::Withdrawn,
        'archived' => Status::Archived,
    ];

    public function up(): void
    {
        foreach (self::MAP as $string => $int) {
            DB::table('recruitment_applications')->where('status', $string)->update(['status' => (string) $int]);
        }

        // Raw ALTER TABLE is not prefix-aware the way the query builder is —
        // this connection runs behind the 'hchr_' table prefix, so the actual
        // physical name must be resolved explicitly rather than hardcoded.
        DB::statement('ALTER TABLE '.$this->table().' MODIFY status TINYINT UNSIGNED NOT NULL DEFAULT 0');
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE ".$this->table()." MODIFY status VARCHAR(60) NOT NULL DEFAULT 'submitted'");

        foreach (self::MAP as $string => $int) {
            DB::table('recruitment_applications')->where('status', (string) $int)->update(['status' => $string]);
        }
    }

    private function table(): string
    {
        return DB::connection()->getTablePrefix().'recruitment_applications';
    }
};
