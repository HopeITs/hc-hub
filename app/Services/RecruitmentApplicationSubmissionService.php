<?php

namespace App\Services;

use App\Enums\HiringCampaignStatus;
use App\Enums\RecruitmentApplicationStatus;
use App\Models\ApplicationFormVersion;
use App\Models\HiringCampaign;
use App\Models\PublicRecruitmentLink;
use App\Models\RecruitmentApplication;
use App\Models\RecruitmentCampaignInvite;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Turns a public form submission into a Candidate/Application pair.
 *
 * Everything here runs unauthenticated, off a token the caller already
 * resolved to a PublicRecruitmentLink (recruitment-execution-plan-ar.md,
 * Phase 1 Task 15 owns the token itself; this owns what happens once it
 * checks out). Every check re-reads live state under a lock rather than
 * trusting whatever the browser had open, because the whole point of a
 * public link is that the page can sit open for a long time before submit.
 */
class RecruitmentApplicationSubmissionService
{
    // TODO (Business Rule): the exact link_mode string for an invitation-only
    // campaign has never been decided or written anywhere else in the
    // codebase — 'public_open' is the only value Team 1's migration sets as a
    // default. Treating anything else as invitation-only is a safe default
    // (fails toward requiring an invite) but must be confirmed once Team 1 or
    // the business team names the real values.
    private const OPEN_LINK_MODE = 'public_open';

    public function __construct(private readonly RecruitmentApplicantIdentityService $identity) {}

    /**
     * @param  array{
     *     full_name: string,
     *     email: string,
     *     phone?: ?string,
     *     city_id?: ?int,
     *     privacy_notice_version?: ?string,
     *     privacy_accepted_at?: bool,
     *     consent_payload_json?: ?array,
     *     answers: array<string, mixed>,
     * }  $input
     * @param  array{ip_address?: ?string, user_agent?: ?string}  $meta
     */
    public function submit(PublicRecruitmentLink $link, array $input, array $meta = []): RecruitmentApplication
    {
        $emailNormalized = $this->identity->normalizeEmail($input['email']);

        return DB::transaction(function () use ($link, $input, $meta, $emailNormalized) {
            $link = PublicRecruitmentLink::query()
                ->whereKey($link->getKey())
                ->with('campaign')
                ->lockForUpdate()
                ->firstOrFail();

            $this->assertLinkUsable($link);

            $campaign = $link->campaign;
            $this->assertCampaignAccepting($campaign);

            $invite = $this->resolveInvite($campaign, $emailNormalized);

            // A used invite with an application already attached is the
            // invited person reloading or retrying the page, not a second
            // person — return what they already submitted instead of
            // rejecting a legitimate retry.
            if ($invite !== null && $invite->used_at !== null) {
                $existing = RecruitmentApplication::query()
                    ->where('campaign_invite_id', $invite->getKey())
                    ->first();

                if ($existing !== null) {
                    return $existing;
                }
            }

            $applicant = $this->identity->findOrCreate([
                'full_name' => $input['full_name'],
                'email' => $input['email'],
                'phone' => $input['phone'] ?? null,
                'city_id' => $input['city_id'] ?? null,
                'source' => 'public_link',
            ]);

            // TODO (Business Rule): one application per applicant per
            // campaign is a hard reject for now (plan: "can be prevented or
            // flagged depending on policy"). Nothing here lets HR reopen or
            // amend a submitted application — that is a separate ATS
            // decision, not a resubmission.
            $duplicate = RecruitmentApplication::query()
                ->where('applicant_id', $applicant->getKey())
                ->where('campaign_id', $campaign->getKey())
                ->exists();

            if ($duplicate) {
                throw ValidationException::withMessages(['email' => __('recruitment.already_applied')]);
            }

            $answers = $this->whitelistAnswers($link->formVersion, (array) ($input['answers'] ?? []));

            try {
                $application = RecruitmentApplication::query()->create([
                    'applicant_id' => $applicant->getKey(),
                    'campaign_id' => $campaign->getKey(),
                    'vacancy_id' => $campaign->vacancy_id,
                    'form_version_id' => $link->form_version_id,
                    'public_link_id' => $link->getKey(),
                    'campaign_invite_id' => $invite?->getKey(),
                    'submission_key_hash' => hash('sha256', $link->getKey().'|'.$emailNormalized),
                    'email_normalized' => $emailNormalized,
                    'status' => RecruitmentApplicationStatus::Submitted,
                    'privacy_notice_version' => $input['privacy_notice_version'] ?? null,
                    'privacy_accepted_at' => ($input['privacy_accepted_at'] ?? false) ? now() : null,
                    'consent_payload_json' => $input['consent_payload_json'] ?? null,
                    'submitted_at' => now(),
                    'ip_address' => $meta['ip_address'] ?? null,
                    'user_agent' => $meta['user_agent'] ?? null,
                ]);
            } catch (UniqueConstraintViolationException) {
                // Two submits racing past the exists() check above land here;
                // the unique indexes on (applicant_id, campaign_id) and
                // (campaign_id, email_normalized) are the actual guarantee.
                throw ValidationException::withMessages(['email' => __('recruitment.already_applied')]);
            }

            foreach ($answers as $fieldKey => $value) {
                $application->answers()->create([
                    'form_version_id' => $link->form_version_id,
                    'field_key' => $fieldKey,
                    'answer_json' => $value,
                ]);
            }

            if ($invite !== null && $invite->used_at === null) {
                $invite->forceFill(['status' => 'used', 'used_at' => now()])->save();
            }

            return $application;
        });
    }

    private function assertLinkUsable(PublicRecruitmentLink $link): void
    {
        if ($link->status !== 'active' || $link->expires_at?->isPast()) {
            // Generic on purpose: an expired, disabled, and never-existed
            // token must all look the same from the outside (Phase 1 Task 15).
            throw ValidationException::withMessages(['token' => __('recruitment.invalid_or_expired_link')]);
        }
    }

    /** D3: Published only advertises; Open is what accepts submissions. */
    private function assertCampaignAccepting(?HiringCampaign $campaign): void
    {
        if ((int) $campaign?->status !== HiringCampaignStatus::Open) {
            throw ValidationException::withMessages(['token' => __('recruitment.campaign_not_accepting')]);
        }
    }

    /**
     * Invitation-only campaigns accept a submission only from a pre-approved
     * email; open campaigns have no invite to check at all.
     */
    private function resolveInvite(HiringCampaign $campaign, string $emailNormalized): ?RecruitmentCampaignInvite
    {
        if ($campaign->link_mode === self::OPEN_LINK_MODE) {
            return null;
        }

        $invite = RecruitmentCampaignInvite::query()
            ->where('campaign_id', $campaign->getKey())
            ->where('email_normalized', $emailNormalized)
            ->lockForUpdate()
            ->first();

        // Same message whether the email was never invited or the invite was
        // cancelled — the plan is explicit that the invited list must not be
        // discoverable by trying emails against this endpoint.
        if ($invite === null || $invite->status === 'cancelled') {
            throw ValidationException::withMessages(['email' => __('recruitment.invitation_required')]);
        }

        return $invite;
    }

    /**
     * Keep only answers the published form version actually asked for.
     *
     * TODO (Business Rule): the shape of a field definition in fields_json
     * has not been fixed anywhere else in the codebase yet — this assumes
     * each entry carries at least a 'key' and an optional 'required' flag.
     * Update this once Team 1 (or whoever builds the form editor) settles the
     * real schema.
     *
     * @return array<string, mixed>
     */
    private function whitelistAnswers(?ApplicationFormVersion $formVersion, array $submitted): array
    {
        $fields = (array) ($formVersion?->fields_json ?? []);
        $missing = [];
        $answers = [];

        foreach ($fields as $field) {
            $key = $field['key'] ?? null;

            if ($key === null) {
                continue;
            }

            if (array_key_exists($key, $submitted) && $submitted[$key] !== null && $submitted[$key] !== '') {
                $answers[$key] = $submitted[$key];

                continue;
            }

            if (! empty($field['required'])) {
                $missing['answers.'.$key] = __('recruitment.missing_required_field', ['field' => $field['label'] ?? $key]);
            }
        }

        if ($missing !== []) {
            throw ValidationException::withMessages($missing);
        }

        return $answers;
    }
}
