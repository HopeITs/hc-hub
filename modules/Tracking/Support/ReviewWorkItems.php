<?php

declare(strict_types=1);

namespace Modules\Tracking\Support;

use App\Support\Desk\Destinations;
use App\Support\Modules\Contracts\WorkItemSource;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Modules\Core\Models\User;
use Modules\Tracking\Models\ProposalReviewer;

/**
 * My reviews as a source of the "Waiting on me" page (D7), and the one
 * reader of the rows behind the Desk's My reviews card: the proposals and
 * concept notes still waiting on the viewer's own review, one row per
 * proposal, oldest ask first.
 */
final class ReviewWorkItems implements WorkItemSource
{
    /**
     * A review waiting longer than this many days is drawn as late.
     */
    public const int LongWaitDays = 7;

    public function __construct(private readonly Destinations $destinations) {}

    /**
     * The viewer's reviewer rows that still wait on them, one per proposal.
     *
     * @return Collection<int, ProposalReviewer>
     */
    public function awaiting(User $user): Collection
    {
        if ($user->employee_id === null) {
            return new Collection;
        }

        return ProposalReviewer::query()
            ->forEmployee((int) $user->employee_id)
            ->awaitingReview()
            ->with(['proposal:id,slug,name,status', 'proposal.projects:projects.id,projects.slug'])
            ->oldest('created_at')
            ->get()
            ->unique('proposal_id')
            ->values();
    }

    public function key(): string
    {
        return 'reviews';
    }

    public function label(): string
    {
        return (string) __('desk.panel.reviews.title');
    }

    /**
     * The rule that opens the My reviews page and offers its card.
     */
    public function allows(User $user): bool
    {
        return $this->destinations->allows($user, 'reviews.index');
    }

    public function href(User $user): string
    {
        return route('reviews.index');
    }

    public function count(User $user): int
    {
        return $this->awaiting($user)->count();
    }

    public function items(User $user): array
    {
        $today = CarbonImmutable::today();
        $items = [];

        foreach ($this->awaiting($user) as $review) {
            if ($review->proposal === null) {
                continue;
            }

            $href = ProposalStoryLink::review($user, $review->proposal);

            $items[] = [
                'ref' => 'review:'.$href,
                'title' => $review->proposal->name,
                'context' => $review->stage->label().' · '.$review->slot->label(),
                'href' => $href,
                'since' => $review->created_at,
                'urgent' => $review->created_at !== null && $review->created_at->startOfDay()->diffInDays($today) > self::LongWaitDays,
            ];
        }

        return $items;
    }
}
