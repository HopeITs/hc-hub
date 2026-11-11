<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Core\Models\User;
use Modules\Tracking\Models\ProgramRequest;
use Modules\Tracking\Models\Project;
use Modules\Tracking\Models\Proposal;
use Modules\Tracking\Models\ProposalLocation;
use Modules\Tracking\Support\ProposalLocationBackfill;

/**
 * Location rows for the seeded proposals (W-34a): one per existing link
 * through the same backfill the command runs, plus one "new program" row
 * with its pending program request on the first proposal, so the admin
 * page and the Locations chapter have something to show. Runs after the
 * proposal seeders; idempotent.
 */
class ProposalLocationSeeder extends Seeder
{
    public function run(): void
    {
        app(ProposalLocationBackfill::class)->run(apply: true);

        if (ProgramRequest::query()->exists()) {
            return;
        }

        $proposal = Proposal::query()->orderBy('id')->first();
        $cityId = Project::query()->orderBy('id')->value('city_id');
        $requester = User::query()->orderBy('id')->first();

        if ($proposal === null || $cityId === null || $requester === null) {
            return;
        }

        $row = ProposalLocation::query()
            ->where('proposal_id', $proposal->id)
            ->where('city_id', (int) $cityId)
            ->whereNull('program_id')
            ->first();

        $row ??= ProposalLocation::query()->create([
            'proposal_id' => $proposal->id,
            'city_id' => (int) $cityId,
            'is_new' => true,
            'sort' => (int) $proposal->locations()->max('sort') + 1,
        ]);

        ProgramRequest::query()->create([
            'proposal_location_id' => $row->id,
            'name_en' => 'Wells',
            'name_ar' => 'الآبار',
            'requested_by' => $requester->id,
        ]);
    }
}
