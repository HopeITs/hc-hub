<?php

namespace Modules\Hr\Support\Recruitment\Console;

use Illuminate\Console\Command;
use Modules\Hr\Actions\Recruitment\HiringCampaignService;

class SyncRecruitmentCampaignStates extends Command
{
    protected $signature = 'recruitment:sync-campaign-states';

    protected $description = 'Open scheduled recruitment campaigns and expire campaigns whose window ended';

    public function handle(HiringCampaignService $campaigns): int
    {
        $result = $campaigns->syncScheduledStates();
        $this->info("Opened {$result['opened']} and expired {$result['expired']} recruitment campaign(s).");

        return self::SUCCESS;
    }
}
