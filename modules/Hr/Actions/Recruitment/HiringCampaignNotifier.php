<?php

namespace Modules\Hr\Actions\Recruitment;

use Illuminate\Support\Facades\Log;
use Modules\Core\Models\User;
use Modules\Hr\Models\HiringCampaign;
use Modules\Hr\Support\Recruitment\Access;
use Modules\Hr\Support\Recruitment\Notifications\RecruitmentRestrictedCampaignLaunched;
use Throwable;

/** Sends restricted-campaign launch notices without risking the launch itself. */
class HiringCampaignNotifier
{
    public function notifyLaunched(HiringCampaign $campaign): void
    {
        $campaign->loadMissing('vacancy.position.directManager.activeContracts.employee.account');

        $managerUsers = $campaign->vacancy?->position?->directManager?->activeContracts
            ?->map(fn ($contract) => $contract->employee)
            ->filter(fn ($employee) => $employee?->is_active && $employee->account)
            ->map(fn ($employee) => $employee->account)
            ?? collect();

        $hrSyriaUsers = Access::broadcastRecipientsQuery()
            ->whereHas('roles', fn ($roles) => $roles->where('name', 'HR-SYR'))
            ->get();

        $managerUsers
            ->merge($hrSyriaUsers)
            ->unique(fn (User $user): int => (int) $user->getKey())
            ->each(function (User $user) use ($campaign): void {
                try {
                    $user->notify(new RecruitmentRestrictedCampaignLaunched($campaign));
                } catch (Throwable $exception) {
                    Log::warning('A restricted campaign launch notification could not be sent.', [
                        'campaign_id' => $campaign->getKey(),
                        'user_id' => $user->getKey(),
                        'exception' => $exception->getMessage(),
                    ]);
                }
            });
    }
}
