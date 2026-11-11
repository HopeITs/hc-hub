<?php

declare(strict_types=1);

namespace Modules\Tracking\Console\Commands;

use Illuminate\Console\Command;
use Modules\Tracking\Support\ProposalLocationBackfill;

/**
 * Gives every existing proposal–project link its location row (W-34a) and,
 * with --apply, records them. A dry run (the default, and whenever
 * --dry-run is given) prints the same counts and writes nothing. Safe to
 * re-run: links that already have a row are skipped.
 */
class BackfillProposalLocations extends Command
{
    /**
     * @var string
     */
    protected $signature = 'proposals:backfill-locations
        {--dry-run : Only report; the default when --apply is not given}
        {--apply : Write the location rows}';

    /**
     * @var string
     */
    protected $description = 'Give every existing proposal–project link its location row (W-34a)';

    public function handle(ProposalLocationBackfill $backfill): int
    {
        $apply = (bool) $this->option('apply') && ! $this->option('dry-run');

        $this->components->info($apply ? 'Applying: location rows will be written.' : 'Dry run: nothing will be written.');

        $counts = $backfill->run($apply);

        $this->newLine();
        $this->components->twoColumnDetail('Links (project_proposal)', (string) $counts['links']);
        $this->components->twoColumnDetail('Already had a row (skipped)', (string) $counts['already']);
        $this->components->twoColumnDetail('<fg=green>Rows '.($apply ? 'inserted' : 'to insert').'</>', (string) $counts['inserted']);
        $this->components->twoColumnDetail('<fg=yellow>Merged into an existing row (same city and program)</>', (string) $counts['merged']);
        $this->components->twoColumnDetail('Proposals without any link (no row)', (string) $counts['proposals_without_links']);
        $this->components->twoColumnDetail('Proposals spanning several cities (one row per link)', (string) $counts['multi_city_proposals']);

        return self::SUCCESS;
    }
}
