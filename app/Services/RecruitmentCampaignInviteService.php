<?php

namespace App\Services;

use App\Models\HiringCampaign;
use App\Models\RecruitmentCampaignInvite;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/**
 * Lets HR pre-approve who may submit to an invitation-only campaign.
 *
 * Closes the one gap left in Phase 2 Task 1: the invite table and its
 * verification at submission time already existed
 * (RecruitmentApplicationSubmissionService::resolveInvite); this is what lets
 * a human actually put an email on that list.
 */
class RecruitmentCampaignInviteService
{
    public function __construct(private readonly RecruitmentApplicantIdentityService $identity) {}

    /**
     * Add an invite, or revive one that was previously cancelled.
     *
     * (campaign_id, email_normalized) is unique regardless of status, so a
     * cancelled invite cannot simply be re-inserted — it is reactivated in
     * place instead.
     *
     * // TODO (Business Rule): reactivating overwrites the cancelled row
     * // rather than keeping a history of "invited, cancelled, invited
     * // again". Fine for an MVP; a full audit trail would need a separate
     * // events table, the same way recruitment_request_events tracks the
     * // request side.
     */
    public function add(User $actor, HiringCampaign $campaign, string $fullName, string $email): RecruitmentCampaignInvite
    {
        Gate::forUser($actor)->authorize('manageForCampaign', [RecruitmentCampaignInvite::class, $campaign]);

        $emailNormalized = $this->identity->normalizeEmail($email);

        return DB::transaction(function () use ($actor, $campaign, $fullName, $emailNormalized) {
            $invite = RecruitmentCampaignInvite::query()
                ->where('campaign_id', $campaign->getKey())
                ->where('email_normalized', $emailNormalized)
                ->lockForUpdate()
                ->first();

            if ($invite === null) {
                return RecruitmentCampaignInvite::query()->create([
                    'campaign_id' => $campaign->getKey(),
                    'full_name' => $fullName,
                    'email_normalized' => $emailNormalized,
                    'status' => 'pending',
                    'created_by_user_id' => $actor->getKey(),
                ]);
            }

            if ($invite->used_at !== null) {
                // The person already applied under this invite; re-adding
                // them would silently detach their existing application from
                // the invite that let them through.
                throw ValidationException::withMessages(['email' => __('recruitment.invite_already_used')]);
            }

            if ($invite->status !== 'cancelled') {
                throw ValidationException::withMessages(['email' => __('recruitment.invite_already_exists')]);
            }

            $invite->forceFill([
                'full_name' => $fullName,
                'status' => 'pending',
                'sent_at' => null,
            ])->save();

            return $invite;
        });
    }

    /** Cancelling is final — see add()'s reactivation path to invite the same email again. */
    public function cancel(User $actor, RecruitmentCampaignInvite $invite): RecruitmentCampaignInvite
    {
        Gate::forUser($actor)->authorize('cancel', $invite);

        return DB::transaction(function () use ($invite) {
            $fresh = RecruitmentCampaignInvite::query()->whereKey($invite->getKey())->lockForUpdate()->firstOrFail();

            if ($fresh->used_at !== null) {
                throw ValidationException::withMessages(['invite' => __('recruitment.invite_already_used')]);
            }

            // Cancelling twice is a harmless retry, not an error.
            if ($fresh->status !== 'cancelled') {
                $fresh->forceFill(['status' => 'cancelled'])->save();
            }

            return $fresh;
        });
    }
}
