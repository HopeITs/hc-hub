<?php

namespace App\Services;

use App\Enums\HiringCampaignStatus;
use App\Enums\RecruitmentCampaignLinkMode;
use App\Enums\RecruitmentEventType;
use App\Enums\RecruitmentRequestStatus;
use App\Models\HiringCampaign;
use App\Models\PublicRecruitmentLink;
use App\Models\RecruitmentRequestEvent;
use App\Models\User;
use App\Models\Vacancy;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;

class HiringCampaignService
{
    private const ACTIVE_STATUSES = [
        HiringCampaignStatus::Draft,
        HiringCampaignStatus::Published,
        HiringCampaignStatus::Open,
    ];

    public function __construct(private readonly ActivityLogger $activityLogger) {}

    /** @param array{starts_at: string|CarbonInterface, ends_at: string|CarbonInterface} $input */
    public function create(User $actor, Vacancy $vacancy, array $input): HiringCampaign
    {
        [$startsAt, $endsAt] = $this->validatedSchedule($input);

        return DB::transaction(function () use ($actor, $vacancy, $startsAt, $endsAt): HiringCampaign {
            $lockedVacancy = Vacancy::query()
                ->with('createdFromRequest.latestVersion')
                ->whereKey($vacancy->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            Gate::forUser($actor)->authorize('create', [HiringCampaign::class, $lockedVacancy]);
            $this->assertAvailableVacancy($lockedVacancy);

            $hasActiveCampaign = HiringCampaign::query()
                ->where('vacancy_id', $lockedVacancy->getKey())
                ->whereIn('status', self::ACTIVE_STATUSES)
                ->lockForUpdate()
                ->exists();

            if ($hasActiveCampaign) {
                throw new DomainException(__('recruitment.campaign.errors.active_exists'));
            }

            $campaign = HiringCampaign::query()->create([
                'vacancy_id' => $lockedVacancy->getKey(),
                'status' => HiringCampaignStatus::Draft,
                'link_mode' => RecruitmentCampaignLinkMode::PublicOpen,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'created_by_user_id' => $actor->getKey(),
            ]);

            $this->recordStateChange($campaign, $actor, null, HiringCampaignStatus::Draft, 'created');

            return $campaign->fresh();
        }, 3);
    }

    /** @param array{starts_at: string|CarbonInterface, ends_at: string|CarbonInterface} $input */
    public function updateSchedule(User $actor, HiringCampaign $campaign, array $input): HiringCampaign
    {
        [$startsAt, $endsAt] = $this->validatedSchedule($input);

        return DB::transaction(function () use ($actor, $campaign, $startsAt, $endsAt): HiringCampaign {
            $locked = HiringCampaign::query()->lockForUpdate()->findOrFail($campaign->getKey());
            Gate::forUser($actor)->authorize('update', $locked);

            if (! in_array((int) $locked->status, self::ACTIVE_STATUSES, true)) {
                throw new DomainException(__('recruitment.campaign.errors.terminal_schedule'));
            }

            if ((int) $locked->status === HiringCampaignStatus::Open
                && ($startsAt->isFuture() || ! $endsAt->isFuture())) {
                throw new DomainException(__('recruitment.campaign.errors.open_window'));
            }

            $before = [
                'starts_at' => $locked->starts_at?->toIso8601String(),
                'ends_at' => $locked->ends_at?->toIso8601String(),
            ];
            $locked->forceFill(['starts_at' => $startsAt, 'ends_at' => $endsAt])->save();

            $this->recordEvent($locked, $actor, 'Hiring campaign schedule changed.', [
                'action' => 'schedule_updated',
                'before' => $before,
                'after' => [
                    'starts_at' => $startsAt->toIso8601String(),
                    'ends_at' => $endsAt->toIso8601String(),
                ],
            ]);
            $this->activityLogger->log('recruitment_campaign_schedule_updated', $locked, $actor, [
                'before' => $before,
                'starts_at' => $startsAt->toIso8601String(),
                'ends_at' => $endsAt->toIso8601String(),
            ], 'recruitment');

            return $locked->fresh();
        }, 3);
    }

    public function open(User $actor, HiringCampaign $campaign): HiringCampaign
    {
        return DB::transaction(function () use ($actor, $campaign): HiringCampaign {
            $locked = $this->lockCampaign($campaign);
            Gate::forUser($actor)->authorize('publish', $locked);

            if ((int) $locked->status === HiringCampaignStatus::Open) {
                return $locked;
            }
            if ((int) $locked->status !== HiringCampaignStatus::Published) {
                throw new DomainException(__('recruitment.campaign.errors.not_published'));
            }

            $this->assertReadyToOpen($locked);
            $from = (int) $locked->status;
            $locked->forceFill(['status' => HiringCampaignStatus::Open])->save();
            $this->recordStateChange($locked, $actor, $from, HiringCampaignStatus::Open, 'opened');

            return $locked->fresh();
        }, 3);
    }

    public function close(User $actor, HiringCampaign $campaign): HiringCampaign
    {
        return DB::transaction(function () use ($actor, $campaign): HiringCampaign {
            $locked = $this->lockCampaign($campaign);
            Gate::forUser($actor)->authorize('close', $locked);

            if ((int) $locked->status === HiringCampaignStatus::Closed) {
                return $locked;
            }
            if (! in_array((int) $locked->status, self::ACTIVE_STATUSES, true)) {
                throw new DomainException(__('recruitment.campaign.errors.terminal_transition'));
            }

            $from = (int) $locked->status;
            $locked->forceFill([
                'status' => HiringCampaignStatus::Closed,
                'closed_by_user_id' => $actor->getKey(),
            ])->save();
            $this->recordStateChange($locked, $actor, $from, HiringCampaignStatus::Closed, 'closed');

            return $locked->fresh();
        }, 3);
    }

    public function disable(User $actor, HiringCampaign $campaign, string $reason): HiringCampaign
    {
        $reason = trim($reason);
        if ($reason === '' || mb_strlen($reason) > 1000) {
            throw new DomainException(__('recruitment.campaign.errors.disable_reason'));
        }

        return DB::transaction(function () use ($actor, $campaign, $reason): HiringCampaign {
            $locked = $this->lockCampaign($campaign);
            Gate::forUser($actor)->authorize('manage', $locked);

            if ((int) $locked->status === HiringCampaignStatus::Disabled) {
                return $locked;
            }
            if (! in_array((int) $locked->status, self::ACTIVE_STATUSES, true)) {
                throw new DomainException(__('recruitment.campaign.errors.terminal_transition'));
            }

            $from = (int) $locked->status;
            $locked->publicLinks()
                ->where('status', PublicRecruitmentLink::STATUS_ACTIVE)
                ->lockForUpdate()
                ->update(['status' => PublicRecruitmentLink::STATUS_DISABLED, 'updated_at' => now()]);
            $locked->forceFill([
                'status' => HiringCampaignStatus::Disabled,
                'closed_by_user_id' => $actor->getKey(),
            ])->save();
            $this->recordStateChange($locked, $actor, $from, HiringCampaignStatus::Disabled, 'disabled', [
                'reason' => $reason,
            ]);

            return $locked->fresh();
        }, 3);
    }

    /** @return array{opened: int, expired: int} */
    public function syncScheduledStates(): array
    {
        $opened = 0;
        $expired = 0;

        HiringCampaign::query()
            ->whereIn('status', [HiringCampaignStatus::Published, HiringCampaignStatus::Open])
            ->whereNotNull('ends_at')
            ->where('ends_at', '<=', now())
            ->pluck('id')
            ->each(function (int $campaignId) use (&$expired): void {
                $expired += $this->expireById($campaignId) ? 1 : 0;
            });

        HiringCampaign::query()
            ->where('status', HiringCampaignStatus::Published)
            ->whereNotNull('starts_at')
            ->where('starts_at', '<=', now())
            ->where('ends_at', '>', now())
            ->pluck('id')
            ->each(function (int $campaignId) use (&$opened): void {
                $opened += $this->openScheduledById($campaignId) ? 1 : 0;
            });

        return ['opened' => $opened, 'expired' => $expired];
    }

    private function openScheduledById(int $campaignId): bool
    {
        return DB::transaction(function () use ($campaignId): bool {
            $campaign = HiringCampaign::query()
                ->with(['vacancy.createdFromRequest.latestVersion', 'jobPost.currentVersion', 'applicationForm.currentVersion'])
                ->lockForUpdate()
                ->find($campaignId);

            if (! $campaign || (int) $campaign->status !== HiringCampaignStatus::Published) {
                return false;
            }

            try {
                $this->assertReadyToOpen($campaign);
            } catch (DomainException) {
                return false;
            }

            $campaign->forceFill(['status' => HiringCampaignStatus::Open])->save();
            $this->recordStateChange($campaign, null, HiringCampaignStatus::Published, HiringCampaignStatus::Open, 'opened_automatically');

            return true;
        }, 3);
    }

    private function expireById(int $campaignId): bool
    {
        return DB::transaction(function () use ($campaignId): bool {
            $campaign = HiringCampaign::query()->lockForUpdate()->find($campaignId);
            if (! $campaign
                || ! in_array((int) $campaign->status, [HiringCampaignStatus::Published, HiringCampaignStatus::Open], true)
                || ! $campaign->ends_at?->isPast()) {
                return false;
            }

            $from = (int) $campaign->status;
            $campaign->forceFill(['status' => HiringCampaignStatus::Expired])->save();
            $this->recordStateChange($campaign, null, $from, HiringCampaignStatus::Expired, 'expired_automatically');

            return true;
        }, 3);
    }

    private function lockCampaign(HiringCampaign $campaign): HiringCampaign
    {
        return HiringCampaign::query()
            ->with(['vacancy.createdFromRequest.latestVersion', 'jobPost.currentVersion', 'applicationForm.currentVersion'])
            ->lockForUpdate()
            ->findOrFail($campaign->getKey());
    }

    private function assertReadyToOpen(HiringCampaign $campaign): void
    {
        $vacancy = Vacancy::query()->whereKey($campaign->vacancy_id)->lockForUpdate()->firstOrFail();
        $this->assertAvailableVacancy($vacancy);

        if (! $campaign->starts_at || $campaign->starts_at->isFuture()
            || ! $campaign->ends_at || ! $campaign->ends_at->isFuture()) {
            throw new DomainException(__('recruitment.campaign.errors.open_window'));
        }

        if ($campaign->jobPost?->currentVersion?->status !== 'published'
            || $campaign->applicationForm?->currentVersion?->status !== 'published') {
            throw new DomainException(__('recruitment.campaign.errors.assets_not_ready'));
        }

        $activeLink = $campaign->publicLinks()
            ->where('status', PublicRecruitmentLink::STATUS_ACTIVE)
            ->where('expires_at', '>', now())
            ->lockForUpdate()
            ->exists();
        if (! $activeLink) {
            throw new DomainException(__('recruitment.campaign.errors.link_not_ready'));
        }
    }

    private function assertAvailableVacancy(Vacancy $vacancy): void
    {
        $request = $vacancy->createdFromRequest;
        if ($vacancy->status !== 'open'
            || $vacancy->closed_at !== null
            || $vacancy->open_at?->isFuture()
            || (int) $vacancy->filled_count >= (int) $vacancy->needed_count
            || ! $request
            || ! in_array((int) $request->status, [RecruitmentRequestStatus::Approved, RecruitmentRequestStatus::Applied], true)) {
            throw new DomainException(__('recruitment.campaign.errors.vacancy_unavailable'));
        }
    }

    /** @return array{CarbonImmutable, CarbonImmutable} */
    private function validatedSchedule(array $input): array
    {
        $data = Validator::make($input, [
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
        ])->validate();

        $startsAt = CarbonImmutable::parse($data['starts_at']);
        $endsAt = CarbonImmutable::parse($data['ends_at']);
        if (! $endsAt->isFuture()) {
            throw new DomainException(__('recruitment.campaign.errors.future_end'));
        }

        return [$startsAt, $endsAt];
    }

    private function recordStateChange(
        HiringCampaign $campaign,
        ?User $actor,
        ?int $from,
        int $to,
        string $action,
        array $extra = [],
    ): void {
        $meta = array_merge([
            'action' => $action,
            'campaign_id' => $campaign->getKey(),
            'from_status' => $from,
            'to_status' => $to,
        ], $extra);
        $this->recordEvent($campaign, $actor, 'Hiring campaign state changed.', $meta);
        $this->activityLogger->log('recruitment_campaign_'.$action, $campaign, $actor, $meta, 'recruitment');
    }

    private function recordEvent(HiringCampaign $campaign, ?User $actor, string $comment, array $meta): void
    {
        $request = $campaign->vacancy?->createdFromRequest
            ?? $campaign->vacancy()->with('createdFromRequest.latestVersion')->first()?->createdFromRequest;
        if (! $request) {
            return;
        }

        RecruitmentRequestEvent::query()->create([
            'recruitment_request_id' => $request->getKey(),
            'request_version_id' => $request->latestVersion?->getKey(),
            'event_type' => RecruitmentEventType::CampaignStateChanged,
            'actor_user_id' => $actor?->getKey(),
            'actor_role' => $actor?->myRole() ?? 'system',
            'comment' => $comment,
            'meta' => $meta,
        ]);
    }
}
