<?php

declare(strict_types=1);

namespace Modules\Tracking\Support;

/**
 * What materialising a proposal's location rows did, or would do
 * (contracts.md §6, W-34a slice 2).
 *
 * From handle(): the projects created, the open projects reused, and the
 * rows left waiting for their program. From preview(): the same lists
 * before anything exists, so `$createdProjectIds` holds the id of the row
 * that would create each project (one entry per project) and callers read
 * counts only.
 */
final readonly class MaterialisationResult
{
    /**
     * @param  list<int>  $createdProjectIds
     * @param  list<int>  $linkedProjectIds
     * @param  list<int>  $waitingLocationIds
     * @param  list<string>  $warnings  warning key: location_linked_existing
     */
    public function __construct(
        public array $createdProjectIds,
        public array $linkedProjectIds,
        public array $waitingLocationIds,
        public array $warnings,
    ) {}
}
