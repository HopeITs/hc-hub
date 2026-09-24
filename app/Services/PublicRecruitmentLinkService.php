<?php

namespace App\Services;

use App\Enums\HiringCampaignStatus;
use App\Enums\RecruitmentCampaignLinkMode;
use App\Enums\RecruitmentEventType;
use App\Exceptions\PublicRecruitmentSubmissionException;
use App\Exceptions\PublicRecruitmentUnavailableException;
use App\Models\HiringCampaign;
use App\Models\PublicRecruitmentLink;
use App\Models\RecruitmentCampaignInvite;
use App\Models\RecruitmentRequestEvent;
use App\Models\User;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use DomainException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PublicRecruitmentLinkService
{
    public function __construct(
        private readonly RecruitmentJobPostService $jobPosts,
        private readonly RecruitmentApplicationFormService $applicationForms,
        private readonly HiringCampaignStateMachine $campaignStates,
        private readonly HiringCampaignService $campaigns,
        private readonly ActivityLogger $activityLogger,
    ) {}

    /** @return array{link: PublicRecruitmentLink, token: string, url: string, campaign_published: bool} */
    public function generate(
        HiringCampaign $campaign,
        User $actor,
        CarbonInterface|string $expiresAt,
        string $linkMode,
    ): array {
        $expiresAt = $expiresAt instanceof CarbonInterface
            ? CarbonImmutable::instance($expiresAt)
            : CarbonImmutable::parse($expiresAt);

        if ($expiresAt->lessThanOrEqualTo(now())) {
            throw new DomainException(__('recruitment.public_link.errors.future_expiry'));
        }

        if (! in_array($linkMode, [RecruitmentCampaignLinkMode::PublicOpen, RecruitmentCampaignLinkMode::InvitationOnly], true)) {
            throw new DomainException(__('recruitment.public_link.errors.invalid_mode'));
        }

        return DB::transaction(function () use ($campaign, $actor, $expiresAt, $linkMode): array {
            $lockedCampaign = HiringCampaign::query()
                ->with([
                    'jobPost.currentVersion',
                    'applicationForm.currentVersion',
                    'vacancy.createdFromRequest.latestVersion',
                ])
                ->lockForUpdate()
                ->findOrFail($campaign->getKey());

            $jobVersion = $lockedCampaign->jobPost?->currentVersion;
            $formVersion = $lockedCampaign->applicationForm?->currentVersion;

            if (! in_array((int) $lockedCampaign->status, [
                HiringCampaignStatus::Draft,
                HiringCampaignStatus::Published,
                HiringCampaignStatus::Open,
            ], true)) {
                throw new DomainException(__('recruitment.campaign.errors.terminal_link'));
            }

            if (! $jobVersion || $jobVersion->status !== 'published') {
                throw new DomainException(__('recruitment.public_link.errors.publish_job_post'));
            }

            if (! $formVersion || $formVersion->status !== 'published') {
                throw new DomainException(__('recruitment.public_link.errors.publish_form'));
            }

            $effectiveExpiry = $lockedCampaign->ends_at && $lockedCampaign->ends_at->lessThan($expiresAt)
                ? CarbonImmutable::instance($lockedCampaign->ends_at)
                : $expiresAt;

            if ($effectiveExpiry->lessThanOrEqualTo(now())) {
                throw new DomainException(__('recruitment.public_link.errors.campaign_ended'));
            }

            $lockedCampaign->publicLinks()
                ->where('status', PublicRecruitmentLink::STATUS_ACTIVE)
                ->lockForUpdate()
                ->update(['status' => PublicRecruitmentLink::STATUS_DISABLED, 'updated_at' => now()]);

            $token = bin2hex(random_bytes(32));
            $link = $lockedCampaign->publicLinks()->create([
                'job_post_version_id' => $jobVersion->getKey(),
                'form_version_id' => $formVersion->getKey(),
                'token_hash' => hash('sha256', $token),
                'status' => PublicRecruitmentLink::STATUS_ACTIVE,
                'expires_at' => $effectiveExpiry,
                'created_by_user_id' => $actor->getKey(),
            ]);
            $campaignPublished = (int) $lockedCampaign->status === HiringCampaignStatus::Draft;
            $lockedCampaign->forceFill(['link_mode' => $linkMode])->save();

            if ($campaignPublished) {
                // The link may publish a fully prepared draft, but the status
                // transition still goes through the campaign state machine.
                $this->campaignStates->apply($lockedCampaign, 'publish');
            }

            if ($campaignPublished) {
                $this->recordEvent(
                    $lockedCampaign,
                    $actor,
                    RecruitmentEventType::CampaignStateChanged,
                    'Hiring campaign published when its public link was activated.',
                    [
                        'from_status' => HiringCampaignStatus::Draft,
                        'to_status' => HiringCampaignStatus::Published,
                    ],
                );
                $this->activityLogger->log('recruitment_campaign_published', $lockedCampaign, $actor, [
                    'from_status' => HiringCampaignStatus::Draft,
                    'to_status' => HiringCampaignStatus::Published,
                ], 'recruitment');
            }

            $this->recordEvent($lockedCampaign, $actor, RecruitmentEventType::PublicLinkChanged, 'Public recruitment link generated.', [
                'public_link_id' => $link->getKey(),
                'link_mode' => $linkMode,
                'expires_at' => $effectiveExpiry->toIso8601String(),
                'job_post_version' => $jobVersion->version_no,
                'form_version' => $formVersion->version_no,
            ]);
            $this->activityLogger->log('public_recruitment_link_generated', $link, $actor, [
                'campaign_id' => $lockedCampaign->getKey(),
                'link_mode' => $linkMode,
                'expires_at' => $effectiveExpiry->toIso8601String(),
            ], 'recruitment');

            return [
                'link' => $link->fresh(),
                'token' => $token,
                'url' => route('careers.show', ['token' => $token]),
                'campaign_published' => $campaignPublished,
            ];
        });
    }

    public function disable(PublicRecruitmentLink $link, User $actor): PublicRecruitmentLink
    {
        return DB::transaction(function () use ($link, $actor): PublicRecruitmentLink {
            $lockedLink = PublicRecruitmentLink::query()
                ->with('campaign.vacancy.createdFromRequest.latestVersion')
                ->lockForUpdate()
                ->findOrFail($link->getKey());

            if ($lockedLink->status === PublicRecruitmentLink::STATUS_DISABLED) {
                return $lockedLink;
            }

            $lockedLink->forceFill(['status' => PublicRecruitmentLink::STATUS_DISABLED])->save();
            $this->recordEvent($lockedLink->campaign, $actor, RecruitmentEventType::PublicLinkChanged, 'Public recruitment link disabled.', [
                'public_link_id' => $lockedLink->getKey(),
            ]);
            $this->activityLogger->log('public_recruitment_link_disabled', $lockedLink, $actor, [
                'campaign_id' => $lockedLink->campaign_id,
            ], 'recruitment');

            return $lockedLink->fresh();
        });
    }

    public function addInvite(HiringCampaign $campaign, User $actor, string $fullName, string $email): RecruitmentCampaignInvite
    {
        $fullName = trim($fullName);
        $email = $this->normalizeEmail($email);

        if ($fullName === '' || mb_strlen($fullName) > 191 || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new DomainException(__('recruitment.public_link.errors.invalid_invitee'));
        }

        return DB::transaction(function () use ($campaign, $actor, $fullName, $email): RecruitmentCampaignInvite {
            $lockedCampaign = HiringCampaign::query()
                ->with('vacancy.createdFromRequest.latestVersion')
                ->lockForUpdate()
                ->findOrFail($campaign->getKey());

            if ($lockedCampaign->link_mode !== RecruitmentCampaignLinkMode::InvitationOnly) {
                throw new DomainException(__('recruitment.public_link.errors.invitation_mode_required'));
            }

            $invite = RecruitmentCampaignInvite::query()->updateOrCreate(
                ['campaign_id' => $lockedCampaign->getKey(), 'email_normalized' => $email],
                [
                    'full_name' => $fullName,
                    'status' => RecruitmentCampaignInvite::STATUS_PENDING,
                    'sent_at' => null,
                    'used_at' => null,
                    'created_by_user_id' => $actor->getKey(),
                ],
            );

            $this->activityLogger->log('recruitment_campaign_invite_saved', $invite, $actor, [
                'campaign_id' => $lockedCampaign->getKey(),
            ], 'recruitment');

            return $invite->fresh();
        });
    }

    public function cancelInvite(RecruitmentCampaignInvite $invite, User $actor): RecruitmentCampaignInvite
    {
        return DB::transaction(function () use ($invite, $actor): RecruitmentCampaignInvite {
            $lockedInvite = RecruitmentCampaignInvite::query()->lockForUpdate()->findOrFail($invite->getKey());
            $lockedInvite->forceFill(['status' => RecruitmentCampaignInvite::STATUS_CANCELLED])->save();
            $this->activityLogger->log('recruitment_campaign_invite_cancelled', $lockedInvite, $actor, [
                'campaign_id' => $lockedInvite->campaign_id,
            ], 'recruitment');

            return $lockedInvite->fresh();
        });
    }

    /** @return array<string, mixed> */
    public function publicPage(string $token): array
    {
        $link = $this->resolve($token);
        $campaign = $link->campaign;

        if (! $this->campaigns->isPubliclyVisible($campaign)) {
            throw new PublicRecruitmentUnavailableException;
        }

        $this->assertFrozenVersions($link);

        return $this->pagePayload($link);
    }

    /**
     * Validates only the Task 15 public boundary. Applicant/application records
     * are intentionally created by the Phase 2 submission service.
     *
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function validateSubmissionShell(string $token, array $input): array
    {
        $link = $this->resolve($token);
        $campaign = $link->campaign;
        $this->assertFrozenVersions($link);

        if (! $this->acceptingApplications($campaign)) {
            throw new PublicRecruitmentSubmissionException;
        }

        $allowedTopLevel = ['_token', 'answers', 'locale', 'website'];
        if (array_diff(array_keys($input), $allowedTopLevel) || ! empty($input['website'])) {
            throw new PublicRecruitmentSubmissionException;
        }

        $answers = $input['answers'] ?? null;
        if (! is_array($answers)) {
            throw new PublicRecruitmentSubmissionException;
        }

        $fields = $this->applicationForms->normalizeFields($link->formVersion->fields_json);
        $allowedAnswerKeys = array_column($fields, 'key');
        if (array_diff(array_keys($answers), $allowedAnswerKeys) || $this->containsUpload($answers)) {
            throw new PublicRecruitmentSubmissionException;
        }

        if ($campaign->link_mode === RecruitmentCampaignLinkMode::InvitationOnly) {
            $emailField = collect($fields)->firstWhere('type', 'email');
            $email = $this->normalizeEmail((string) ($answers[$emailField['key'] ?? 'email'] ?? ''));
            $inviteExists = filter_var($email, FILTER_VALIDATE_EMAIL)
                && $campaign->invites()
                    ->where('email_normalized', $email)
                    ->whereIn('status', [RecruitmentCampaignInvite::STATUS_PENDING, RecruitmentCampaignInvite::STATUS_SENT])
                    ->exists();

            if (! $inviteExists) {
                throw new PublicRecruitmentSubmissionException;
            }
        }

        return $this->pagePayload($link);
    }

    /**
     * Resolve the immutable public link boundary used by the real applicant
     * intake service. The intake service repeats this check after locking the
     * campaign so a close, expiry, or link rotation cannot race a submission.
     */
    public function submissionLink(string $token): PublicRecruitmentLink
    {
        $link = $this->resolve($token);
        $this->assertSubmissionLink($link);

        return $link;
    }

    public function assertSubmissionLink(PublicRecruitmentLink $link): void
    {
        if ($link->status !== PublicRecruitmentLink::STATUS_ACTIVE
            || ! $link->expires_at
            || $link->expires_at->isPast()) {
            throw PublicRecruitmentSubmissionException::because('link_expired');
        }

        $this->assertFrozenVersions($link);

        if (! $this->acceptingApplications($link->campaign)) {
            throw PublicRecruitmentSubmissionException::because('campaign_closed');
        }
    }

    public function normalizeEmail(string $email): string
    {
        return Str::lower(trim($email));
    }

    private function resolve(string $token): PublicRecruitmentLink
    {
        if (preg_match('/^[a-f0-9]{64}$/', $token) !== 1) {
            throw new PublicRecruitmentUnavailableException;
        }

        $link = PublicRecruitmentLink::query()
            ->with([
                'campaign.vacancy.createdFromRequest.latestVersion.positionDetail',
                'campaign.vacancy.position',
                'campaign.jobPost.currentVersion',
                'campaign.applicationForm.currentVersion',
                'jobPostVersion.jobPost',
                'formVersion.applicationForm',
            ])
            ->where('token_hash', hash('sha256', $token))
            ->where('status', PublicRecruitmentLink::STATUS_ACTIVE)
            ->first();

        if (! $link || ! $link->expires_at || $link->expires_at->isPast()) {
            throw new PublicRecruitmentUnavailableException;
        }

        return $link;
    }

    private function assertFrozenVersions(PublicRecruitmentLink $link): void
    {
        $campaignId = (int) $link->campaign_id;
        if (
            $link->jobPostVersion?->status !== 'published'
            || (int) $link->jobPostVersion?->jobPost?->campaign_id !== $campaignId
            || $link->formVersion?->status !== 'published'
            || (int) $link->formVersion?->applicationForm?->campaign_id !== $campaignId
        ) {
            throw new PublicRecruitmentUnavailableException;
        }
    }

    /** @return array<string, mixed> */
    private function pagePayload(PublicRecruitmentLink $link): array
    {
        $campaign = $link->campaign;

        return [
            'job_post' => $this->jobPosts->publicSnapshot($campaign, (int) $link->job_post_version_id),
            'form' => $this->applicationForms->publicSchema($campaign, (int) $link->form_version_id),
            'context' => $this->applicationForms->formContext($campaign),
            'link_mode' => $campaign->link_mode,
            'accepting_applications' => $this->acceptingApplications($campaign),
            'opens_at' => $campaign->starts_at?->toIso8601String(),
            'closes_at' => $campaign->ends_at?->toIso8601String(),
        ];
    }

    private function acceptingApplications(HiringCampaign $campaign): bool
    {
        return $this->campaigns->acceptsSubmissions($campaign);
    }

    /** @param array<string, mixed> $values */
    private function containsUpload(array $values): bool
    {
        foreach ($values as $value) {
            if ($value instanceof UploadedFile || (is_array($value) && $this->containsUpload($value))) {
                return true;
            }
        }

        return false;
    }

    /** @param array<string, mixed> $meta */
    private function recordEvent(
        HiringCampaign $campaign,
        User $actor,
        int $eventType,
        string $comment,
        array $meta,
    ): void {
        $request = $campaign->vacancy?->createdFromRequest;
        if (! $request) {
            return;
        }

        RecruitmentRequestEvent::query()->create([
            'recruitment_request_id' => $request->getKey(),
            'request_version_id' => $request->latestVersion?->getKey(),
            'event_type' => $eventType,
            'actor_user_id' => $actor->getKey(),
            'actor_role' => $actor->myRole() ?? 'unknown',
            'comment' => $comment,
            'meta' => $meta,
        ]);
    }
}
