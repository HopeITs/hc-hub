<?php

namespace App\Services;

use App\Enums\RecruitmentCampaignLinkMode;
use App\Models\HiringCampaign;
use App\Models\RecruitmentCampaignInvite;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/**
 * Team 2's invite rules, used by our InviteTable / Invites screens.
 *
 * Deliberately consistent with Team 1's PublicRecruitmentLinkService::addInvite
 * (which their link manager on the publishing page uses, and which we do not
 * modify):
 *  - invites exist only on invitation-only campaigns;
 *  - adding an email that is already pending/sent edits the invite — Team 1's
 *    design, and how HR corrects a name (the plan's "edit invite");
 *  - adding a cancelled email re-activates it.
 *
 * One rule stricter than Team 1's service: an invite that has been USED can
 * be neither revived nor cancelled. That rule is also enforced for Team 1's
 * code path, without touching it, by RecruitmentCampaignInviteObserver.
 */
class RecruitmentCampaignInviteService
{
    public function __construct(
        private readonly RecruitmentApplicantIdentityService $identity,
        private readonly ActivityLogger $activityLogger,
    ) {}

    public function add(User $actor, HiringCampaign $campaign, string $fullName, string $email): RecruitmentCampaignInvite
    {
        Gate::forUser($actor)->authorize('manageForCampaign', [RecruitmentCampaignInvite::class, $campaign]);

        $fullName = trim($fullName);
        $emailNormalized = $this->identity->normalizeEmail($email);

        return DB::transaction(function () use ($actor, $campaign, $fullName, $emailNormalized) {
            $lockedCampaign = HiringCampaign::query()->lockForUpdate()->findOrFail($campaign->getKey());

            // An invite list on an open campaign restricts nobody and would
            // only mislead whoever reads it.
            if ($lockedCampaign->link_mode !== RecruitmentCampaignLinkMode::InvitationOnly) {
                throw ValidationException::withMessages(['email' => __('recruitment-applicants.invite_not_invitation_only')]);
            }

            $invite = RecruitmentCampaignInvite::query()
                ->where('campaign_id', $lockedCampaign->getKey())
                ->where('email_normalized', $emailNormalized)
                ->lockForUpdate()
                ->first();

            if ($invite === null) {
                $invite = RecruitmentCampaignInvite::query()->create([
                    'campaign_id' => $lockedCampaign->getKey(),
                    'full_name' => $fullName,
                    'email_normalized' => $emailNormalized,
                    'status' => RecruitmentCampaignInvite::STATUS_PENDING,
                    'created_by_user_id' => $actor->getKey(),
                ]);
            } elseif ($invite->used_at !== null || $invite->status === RecruitmentCampaignInvite::STATUS_USED) {
                throw ValidationException::withMessages(['email' => __('recruitment-applicants.invite_already_used')]);
            } elseif ($invite->status === RecruitmentCampaignInvite::STATUS_CANCELLED) {
                // TODO (Business Rule): re-activating overwrites the row; the
                // activity log is the only record of "invited, cancelled,
                // invited again".
                $invite->forceFill([
                    'full_name' => $fullName,
                    'status' => RecruitmentCampaignInvite::STATUS_PENDING,
                    'sent_at' => null,
                ])->save();
            } else {
                // Pending or sent: an edit. A corrected name does not un-send
                // the link, so status and sent_at are kept.
                $invite->forceFill(['full_name' => $fullName])->save();
            }

            // Same event name Team 1's screen writes, so the activity log
            // reads the same whichever screen was used.
            $this->activityLogger->log('recruitment_campaign_invite_saved', $invite, $actor, [
                'campaign_id' => $lockedCampaign->getKey(),
            ], 'recruitment');

            return $invite->fresh();
        });
    }

    public function cancel(User $actor, RecruitmentCampaignInvite $invite): RecruitmentCampaignInvite
    {
        Gate::forUser($actor)->authorize('cancel', $invite);

        return DB::transaction(function () use ($actor, $invite) {
            $fresh = RecruitmentCampaignInvite::query()->whereKey($invite->getKey())->lockForUpdate()->firstOrFail();

            if ($fresh->used_at !== null || $fresh->status === RecruitmentCampaignInvite::STATUS_USED) {
                throw ValidationException::withMessages(['invite' => __('recruitment-applicants.invite_already_used')]);
            }

            // Cancelling twice is a harmless retry, not an error.
            if ($fresh->status !== RecruitmentCampaignInvite::STATUS_CANCELLED) {
                $fresh->forceFill(['status' => RecruitmentCampaignInvite::STATUS_CANCELLED])->save();

                $this->activityLogger->log('recruitment_campaign_invite_cancelled', $fresh, $actor, [
                    'campaign_id' => $fresh->campaign_id,
                ], 'recruitment');
            }

            return $fresh;
        });
    }
}
