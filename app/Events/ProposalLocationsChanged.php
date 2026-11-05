<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * The location rows of a proposal changed (contracts.md §5, W-34a, D-62).
 * Raised once per save that changed the set, by SaveProposal and the link
 * wrappers, and only after the change commits. Carries counts, never names
 * or amounts; the activity `proposal.locations_changed` holds the same.
 */
final class ProposalLocationsChanged implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(
        public readonly int $proposalId,
        public readonly ?int $actorId,
        public readonly int $added,
        public readonly int $removed,
        public readonly int $changed,
    ) {}
}
