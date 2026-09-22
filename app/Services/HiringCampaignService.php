<?php

namespace App\Services;

use App\Enums\HiringCampaignStatus as Status;
use App\Enums\RecruitmentEventType;
use App\Models\HiringCampaign;
use App\Models\RecruitmentRequestEvent;
use App\Models\User;
use App\Models\Vacancy;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Creating a hiring campaign and running it through its life.
 *
 * A campaign is what turns an approved seat into something strangers can see
 * and apply to, so almost everything here is about the moment it becomes
 * public and the moment it stops being so. Two rules run through all of it:
 *
 *   The clock is not the browser's. Every gate re-reads the campaign under a
 *   lock and re-measures the window against the server's own time, because a
 *   page that was opened while the campaign was open proves nothing about
 *   whether it is open now.
 *
 *   Nothing is deleted. A campaign that is finished with is closed, expired or
 *   disabled; its history is what explains why somebody applied, or could not.
 */
class HiringCampaignService
{
    public function __construct(
        private readonly HiringCampaignStateMachine $states,
    ) {}

    /**
     * Open a campaign against a seat that is actually being recruited for.
     *
     * It starts as a draft: nothing is public until the advert and the form
     * exist and somebody publishes it. Several campaigns may run on one
     * vacancy — a public one and an invited one, say — so no uniqueness is
     * imposed here.
     */
    public function create(User $actor, Vacancy $vacancy, array $attributes = []): HiringCampaign
    {
        Gate::forUser($actor)->authorize('create', [HiringCampaign::class, $vacancy]);

        return DB::transaction(function () use ($actor, $vacancy, $attributes): HiringCampaign {
            $locked = Vacancy::query()->whereKey($vacancy->getKey())->lockForUpdate()->firstOrFail();

            // A closed or filled seat is not recruited for, so a campaign on it
            // would advertise work that is not there.
            if (! Vacancy::query()->available()->whereKey($locked->getKey())->exists()) {
                throw $this->refuse('vacancy', 'recruitment-campaign.vacancy_not_available');
            }

            [$startsAt, $endsAt] = $this->window($attributes);

            $campaign = HiringCampaign::query()->create([
                'vacancy_id' => $locked->getKey(),
                'status' => Status::Draft,
                'link_mode' => $attributes['link_mode'] ?? 'public_open',
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'created_by_user_id' => $actor->getKey(),
            ]);

            $this->record($campaign, $actor, null, Status::Draft, 'created');

            return $campaign;
        });
    }

    /**
     * Change the window a campaign runs in.
     *
     * Allowed while the campaign is still the organisation's own business.
     * Once it is expired or disabled the window is history, and rewriting
     * history is what would let a closed campaign quietly accept a late
     * application.
     */
    public function setWindow(User $actor, HiringCampaign $campaign, array $attributes): HiringCampaign
    {
        return $this->act($actor, $campaign, 'update', function (HiringCampaign $locked) use ($actor, $attributes) {
            if (in_array($this->states->effective($locked), [Status::Expired, Status::Disabled], true)) {
                throw $this->refuse('campaign', 'recruitment-campaign.window_is_history');
            }

            [$startsAt, $endsAt] = $this->window($attributes, $locked);

            $locked->forceFill(['starts_at' => $startsAt, 'ends_at' => $endsAt])->save();

            $this->record($locked, $actor, null, (int) $locked->status, 'window_changed', [
                'starts_at' => $startsAt?->toDateTimeString(),
                'ends_at' => $endsAt?->toDateTimeString(),
            ]);

            return $locked;
        });
    }

    /**
     * Make the advert readable (C2). Applications stay shut until `open`.
     *
     * The advert and the form both have to exist and be published first: a
     * campaign that is public with nothing to read and no way to apply is a
     * dead link with the organisation's name on it.
     */
    public function publish(User $actor, HiringCampaign $campaign): HiringCampaign
    {
        return $this->act($actor, $campaign, 'publish', function (HiringCampaign $locked) use ($actor) {
            $this->assertPublishable($locked);

            return $this->transition($locked, $actor, 'publish', 'published');
        });
    }

    /**
     * Open applications (C3), by hand or because `starts_at` arrived.
     *
     * A window that has already run out cannot be opened into: the campaign is
     * expired the moment `ends_at` passes, whoever asks and however the column
     * still reads.
     */
    public function open(User $actor, HiringCampaign $campaign): HiringCampaign
    {
        return $this->act($actor, $campaign, 'publish', function (HiringCampaign $locked) use ($actor) {
            if ($this->states->hasEnded($locked)) {
                throw $this->refuse('campaign', 'recruitment-campaign.window_has_ended');
            }

            return $this->transition($locked, $actor, 'open', 'opened');
        });
    }

    /** Shut applications by hand (C4). The advert stays readable. */
    public function close(User $actor, HiringCampaign $campaign, ?string $reason = null): HiringCampaign
    {
        return $this->act($actor, $campaign, 'close', function (HiringCampaign $locked) use ($actor, $reason) {
            // Written before the transition, so the row that records who
            // closed it is saved in the same breath as the closing itself.
            $locked->forceFill(['closed_by_user_id' => $actor->getKey()])->save();

            return $this->transition($locked, $actor, 'close', 'closed', ['reason' => $reason]);
        });
    }

    /** Take a closed campaign back into applications (C7). */
    public function reopen(User $actor, HiringCampaign $campaign): HiringCampaign
    {
        return $this->act($actor, $campaign, 'publish', function (HiringCampaign $locked) use ($actor) {
            // Reopening onto a window that has run out would take applications
            // nobody could act on.
            if ($locked->ends_at === null || $this->states->hasEnded($locked)) {
                throw $this->refuse('campaign', 'recruitment-campaign.reopen_needs_a_future_end');
            }

            $locked->forceFill(['closed_by_user_id' => null])->save();

            return $this->transition($locked, $actor, 'reopen', 'reopened');
        });
    }

    /**
     * Hide a campaign administratively (C8), from any state.
     *
     * For abuse or a security problem, so it takes effect at once and on the
     * advert as well as the form: a disabled campaign is a public 404, not a
     * closed notice.
     */
    public function disable(User $actor, HiringCampaign $campaign, ?string $reason = null): HiringCampaign
    {
        return $this->act($actor, $campaign, 'manage', function (HiringCampaign $locked) use ($actor, $reason) {
            $previous = (int) $locked->status;

            return $this->transition($locked, $actor, 'disable', 'disabled', [
                'reason' => $reason,
                // Kept so reactivation knows where it was before it vanished.
                'previous_status' => $previous,
            ]);
        });
    }

    /**
     * Bring a disabled campaign back (C9), to where the clock puts it.
     *
     * Where it was before is read from the disabling event rather than a
     * column, so the record of what happened and the state it returns to
     * cannot disagree.
     */
    public function reactivate(User $actor, HiringCampaign $campaign): HiringCampaign
    {
        return $this->act($actor, $campaign, 'manage', function (HiringCampaign $locked) use ($actor) {
            $previous = $this->statusBeforeDisabling($locked);

            $this->states->reactivate($locked, $previous);

            $this->record($locked, $actor, Status::Disabled, (int) $locked->status, 'reactivated', [
                'previous_status' => $previous,
            ]);

            return $locked;
        });
    }

    /**
     * Does this campaign accept an application at this instant?
     *
     * The single authority the public endpoint will ask. It re-reads under a
     * lock and re-measures the window, because the gap between a page loading
     * and a form arriving is long enough for a campaign to close in.
     */
    public function acceptsSubmissions(HiringCampaign $campaign): bool
    {
        return $this->states->effective($campaign) === Status::Open
            && $this->states->hasStarted($campaign)
            && ! $this->states->hasEnded($campaign);
    }

    /** Is the advert readable to a stranger? Draft and disabled are not (D48). */
    public function isPubliclyVisible(HiringCampaign $campaign): bool
    {
        return ! in_array(
            $this->states->effective($campaign),
            [Status::Draft, Status::Disabled],
            true,
        );
    }

    /**
     * Write expiry onto campaigns whose window has run out (C5 · C6).
     *
     * A convenience, not the authority: every gate already treats a campaign
     * with a past `ends_at` as expired. This only keeps the column honest so
     * lists and counts read correctly between sweeps.
     *
     * @return int how many were written
     */
    public function expireDue(): int
    {
        $due = HiringCampaign::query()
            ->whereIn('status', [Status::Published, Status::Open])
            ->whereNotNull('ends_at')
            ->where('ends_at', '<', now())
            ->get();

        foreach ($due as $campaign) {
            DB::transaction(function () use ($campaign): void {
                $locked = HiringCampaign::query()->whereKey($campaign->getKey())->lockForUpdate()->first();

                if ($locked === null || ! $this->states->can($locked, 'expire') || ! $this->states->hasEnded($locked)) {
                    return;
                }

                $from = (int) $locked->status;
                $this->states->apply($locked, 'expire');
                $this->record($locked, null, $from, Status::Expired, 'expired');
            });
        }

        return $due->count();
    }

    /**
     * The shape every action shares: authorize, lock, re-read, act.
     *
     * The lock is taken before anything is read, so two people acting at the
     * same instant serialize on it and the second sees what the first did
     * rather than the state the page was showing.
     */
    private function act(User $actor, HiringCampaign $campaign, string $ability, callable $work): HiringCampaign
    {
        Gate::forUser($actor)->authorize($ability, $campaign);

        return DB::transaction(function () use ($campaign, $work): HiringCampaign {
            $locked = HiringCampaign::query()
                ->whereKey($campaign->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            return $work($locked);
        });
    }

    /** @param array<string, mixed> $meta */
    private function transition(
        HiringCampaign $campaign,
        ?User $actor,
        string $trigger,
        string $action,
        array $meta = [],
    ): HiringCampaign {
        $from = (int) $campaign->status;
        $this->states->apply($campaign, $trigger);

        $this->record($campaign, $actor, $from, (int) $campaign->status, $action, $meta);

        return $campaign;
    }

    /** C2's precondition: something to read, and something to fill in. */
    private function assertPublishable(HiringCampaign $campaign): void
    {
        $hasPost = $campaign->jobPost()->whereHas(
            'versions', fn ($query) => $query->whereNotNull('published_at'),
        )->exists();

        $hasForm = $campaign->applicationForm()->whereHas(
            'versions', fn ($query) => $query->whereNotNull('published_at'),
        )->exists();

        if (! $hasPost || ! $hasForm) {
            throw $this->refuse('campaign', 'recruitment-campaign.needs_post_and_form');
        }
    }

    /**
     * The status this campaign held before it was disabled.
     *
     * @return int the recorded previous status, or draft if nothing says
     */
    private function statusBeforeDisabling(HiringCampaign $campaign): int
    {
        $event = RecruitmentRequestEvent::query()
            ->where('event_type', RecruitmentEventType::CampaignStateChanged)
            ->where('meta->campaign_id', $campaign->getKey())
            ->where('meta->action', 'disabled')
            ->latest('id')
            ->first();

        return (int) ($event?->meta['previous_status'] ?? Status::Draft);
    }

    /**
     * Read and normalise the window.
     *
     * An end before a start is not a window at all, and an end already in the
     * past would create a campaign that is expired the moment it is saved.
     *
     * @param  array<string, mixed>  $attributes
     * @return array{0: ?Carbon, 1: ?Carbon}
     */
    private function window(array $attributes, ?HiringCampaign $current = null): array
    {
        $startsAt = array_key_exists('starts_at', $attributes)
            ? $this->toDate($attributes['starts_at'])
            : ($current?->starts_at === null ? null : Carbon::parse($current->starts_at));

        $endsAt = array_key_exists('ends_at', $attributes)
            ? $this->toDate($attributes['ends_at'])
            : ($current?->ends_at === null ? null : Carbon::parse($current->ends_at));

        if ($startsAt !== null && $endsAt !== null && $endsAt->lessThanOrEqualTo($startsAt)) {
            throw $this->refuse('ends_at', 'recruitment-campaign.end_before_start');
        }

        if ($endsAt !== null && $endsAt->isPast()) {
            throw $this->refuse('ends_at', 'recruitment-campaign.end_in_the_past');
        }

        return [$startsAt, $endsAt];
    }

    private function toDate(mixed $value): ?Carbon
    {
        return blank($value) ? null : Carbon::parse($value);
    }

    /**
     * Write one audit row.
     *
     * Campaign events hang off the request that produced the seat: the events
     * table is keyed to a request, and the chain campaign to vacancy to
     * request is what lets a reader see an advert in the same timeline as the
     * decision that authorised it.
     *
     * A failure to record never undoes the change it describes, which is the
     * rule the rest of the module records events under.
     *
     * @param  array<string, mixed>  $meta
     */
    private function record(
        HiringCampaign $campaign,
        ?User $actor,
        ?int $from,
        int $to,
        string $action,
        array $meta = [],
    ): void {
        try {
            $requestId = $campaign->vacancy?->created_from_request_id
                ?? Vacancy::query()->whereKey($campaign->vacancy_id)->value('created_from_request_id');

            if ($requestId === null) {
                return;
            }

            DB::table('recruitment_request_events')->insert([
                'recruitment_request_id' => $requestId,
                'event_type' => RecruitmentEventType::CampaignStateChanged,
                'actor_user_id' => $actor?->getKey(),
                // The role as it was, never looked up when the row is read.
                'actor_role' => $actor?->myRole() ?? 'system',
                'meta' => json_encode(array_filter([
                    'campaign_id' => $campaign->getKey(),
                    'action' => $action,
                    'from_status' => $from,
                    'to_status' => $to,
                ] + $meta, fn ($value): bool => $value !== null), JSON_UNESCAPED_UNICODE),
                'created_at' => now(),
            ]);
        } catch (Throwable $exception) {
            Log::warning('Recording a hiring campaign event failed.', [
                'campaign_id' => $campaign->getKey(),
                'action' => $action,
                'exception' => $exception->getMessage(),
            ]);
        }
    }

    private function refuse(string $field, string $message): ValidationException
    {
        return ValidationException::withMessages([$field => __($message)]);
    }
}
