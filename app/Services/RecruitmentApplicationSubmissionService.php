<?php

namespace App\Services;

use App\Enums\RecruitmentApplicationStatus;
use App\Enums\RecruitmentCampaignLinkMode;
use App\Exceptions\UnsafeRecruitmentFileException;
use App\Models\ApplicationFormVersion;
use App\Models\HiringCampaign;
use App\Models\PublicRecruitmentLink;
use App\Models\RecruitmentApplication;
use App\Models\RecruitmentCampaignInvite;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

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
    public function __construct(
        private readonly RecruitmentApplicantIdentityService $identity,
        private readonly HiringCampaignService $campaigns,
        private readonly RecruitmentApplicationFormService $forms,
        private readonly RecruitmentApplicantFileService $files,
    ) {}

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
     *     files?: array<string, UploadedFile|array<int, UploadedFile>|null>,
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
                throw ValidationException::withMessages(['email' => __('recruitment-applicants.already_applied')]);
            }

            // The identity inputs are also form fields in Team 1's catalog
            // (full_name / email / phone, locked and required); they are
            // answered from the same values so the stored answers match what
            // Team 1's real form stores.
            [$answers, $uploads] = $this->whitelistAnswers($link->formVersion, array_merge(
                (array) ($input['answers'] ?? []),
                array_filter([
                    'full_name' => $input['full_name'] ?? null,
                    'email' => $input['email'] ?? null,
                    'phone' => $input['phone'] ?? null,
                    'privacy_consent' => ($input['privacy_accepted_at'] ?? false) ? true : null,
                ], fn ($value) => $value !== null && $value !== ''),
            ), (array) ($input['files'] ?? []));

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
                throw ValidationException::withMessages(['email' => __('recruitment-applicants.already_applied')]);
            }

            foreach ($answers as $fieldKey => $value) {
                $application->answers()->create([
                    'form_version_id' => $link->form_version_id,
                    'field_key' => $fieldKey,
                    // Same envelope as Team 1's PublicRecruitmentApplicationService,
                    // so every reader of answers sees one shape.
                    'answer_json' => ['value' => $value],
                ]);
            }

            $this->storeUploads($application, $uploads);

            if ($invite !== null && $invite->used_at === null) {
                $invite->forceFill(['status' => RecruitmentCampaignInvite::STATUS_USED, 'used_at' => now()])->save();
            }

            return $application;
        });
    }

    /**
     * One rule with Team 1's real form: HiringCampaignService::acceptsSubmissions
     * checks the effective state (D3: Open, not merely Published) and the
     * campaign's start/end window on the clock, independent of the
     * SyncRecruitmentCampaignStates scheduler having run.
     */
    public function isAcceptingSubmissions(?HiringCampaign $campaign): bool
    {
        return $campaign !== null && $this->campaigns->acceptsSubmissions($campaign);
    }

    private function assertLinkUsable(PublicRecruitmentLink $link): void
    {
        if ($link->status !== PublicRecruitmentLink::STATUS_ACTIVE || $link->expires_at?->isPast()) {
            // Generic on purpose: an expired, disabled, and never-existed
            // token must all look the same from the outside (Phase 1 Task 15).
            throw ValidationException::withMessages(['token' => __('recruitment-applicants.invalid_or_expired_link')]);
        }
    }

    private function assertCampaignAccepting(?HiringCampaign $campaign): void
    {
        if (! $this->isAcceptingSubmissions($campaign)) {
            throw ValidationException::withMessages(['token' => __('recruitment-applicants.campaign_not_accepting')]);
        }
    }

    /**
     * Invitation-only campaigns accept a submission only from a pre-approved
     * email; open campaigns have no invite to check at all.
     */
    private function resolveInvite(HiringCampaign $campaign, string $emailNormalized): ?RecruitmentCampaignInvite
    {
        if ($campaign->link_mode !== RecruitmentCampaignLinkMode::InvitationOnly) {
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
        if ($invite === null || $invite->status === RecruitmentCampaignInvite::STATUS_CANCELLED) {
            throw ValidationException::withMessages(['email' => __('recruitment-applicants.invitation_required')]);
        }

        return $invite;
    }

    /**
     * Keep only answers the published form version actually asked for.
     *
     * The schema is read through Team 1's RecruitmentApplicationFormService::
     * normalizeFields() — the same reader their real form uses — so keys,
     * required flags (including the catalog's locked fields) and labels mean
     * exactly what the form builder meant.
     *
     * File fields are matched the same way against $files; only uploads for
     * a file field the form declares are kept, never more than its max_files.
     * Storing them is left to storeUploads(), once the application exists.
     *
     * @param  array<string, mixed>  $files
     * @return array{array<string, mixed>, array<string, array{rules: array, uploads: array<int, UploadedFile>}>}
     */
    private function whitelistAnswers(?ApplicationFormVersion $formVersion, array $submitted, array $files = []): array
    {
        $fields = $this->forms->normalizeFields((array) ($formVersion?->fields_json ?? []));
        $missing = [];
        $answers = [];
        $uploads = [];

        foreach ($fields as $field) {
            $key = $field['key'];
            $label = $field['label'][app()->getLocale()] ?: ($field['label']['en'] ?: $key);

            if ($field['type'] === 'file') {
                $fieldUploads = array_values(array_filter(
                    is_array($files[$key] ?? null) ? $files[$key] : [$files[$key] ?? null],
                    fn ($upload) => $upload instanceof UploadedFile,
                ));

                if ($fieldUploads === []) {
                    if ($field['required']) {
                        $missing['answers.'.$key] = __('recruitment-applicants.missing_required_field', ['field' => $label]);
                    }

                    continue;
                }

                $uploads[$key] = [
                    'rules' => ['extensions' => $field['accept'], 'max_files' => $field['max_files']],
                    'uploads' => array_slice($fieldUploads, 0, $field['max_files']),
                ];

                continue;
            }

            // false counts as unanswered: an unticked required checkbox
            // (privacy_consent) must not pass as consent given.
            if (array_key_exists($key, $submitted) && ! in_array($submitted[$key], [null, '', false], true)) {
                $answers[$key] = $submitted[$key];

                continue;
            }

            if ($field['required']) {
                $missing['answers.'.$key] = __('recruitment-applicants.missing_required_field', ['field' => $label]);
            }
        }

        if ($missing !== []) {
            throw ValidationException::withMessages($missing);
        }

        return [$answers, $uploads];
    }

    /**
     * Files go through Team 1's RecruitmentApplicantFileService — the same
     * content inspection and quarantine as their real form, not a second
     * implementation. A rejected file rolls the whole submission back; the
     * files already written to disk are removed by hand, because the
     * transaction only undoes the rows.
     *
     * @param  array<string, array{rules: array, uploads: array<int, UploadedFile>}>  $uploads
     */
    private function storeUploads(RecruitmentApplication $application, array $uploads): void
    {
        $stored = [];

        try {
            foreach ($uploads as $key => $field) {
                foreach ($field['uploads'] as $upload) {
                    try {
                        $stored[] = $this->files->store($application, $upload, $key, $field['rules']);
                    } catch (UnsafeRecruitmentFileException $exception) {
                        throw ValidationException::withMessages(['answers.'.$key => $exception->getMessage()]);
                    }
                }
            }
        } catch (Throwable $exception) {
            foreach ($stored as $file) {
                $file->delete();
            }

            throw $exception;
        }
    }
}
