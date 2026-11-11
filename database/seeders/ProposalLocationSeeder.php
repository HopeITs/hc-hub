<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Tracking\Support\ProposalLocationBackfill;

/**
 * Location rows for the seeded proposals (W-34a): one per existing link
 * through the same backfill the command runs. Runs after the proposal
 * seeders; idempotent.
 */
class ProposalLocationSeeder extends Seeder
{
    public function run(): void
    {
        app(ProposalLocationBackfill::class)->run(apply: true);
    }
}
