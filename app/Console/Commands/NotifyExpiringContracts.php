<?php

namespace App\Console\Commands;

use App\Models\Contract;
use App\Models\User;
use App\Notifications\ExpiringContractAlert;
use Carbon\Carbon;
use Illuminate\Console\Command;

class NotifyExpiringContracts extends Command
{
    protected $signature = 'notify:expiry-contracts';
    protected $description = 'Notify General HRs of contracts expiring in 7 days';

    public function handle(): int
    {
        $targetDate = Carbon::today()->addDays(7);

        $contracts = Contract::with('employee.city')->where('is_active', true)->whereDate('end_date', $targetDate)->get();

        foreach ($contracts as $contract) {
            $city = $contract->employee->city?->slug;

            if (!$city) {
                continue;
            }

            $roleName = "HR-{$city}";

            $hrUsers = User::whereHas('roles', fn($q) => $q->where('name', $roleName))->get();

            foreach ($hrUsers as $user) {
                $user->notify(new ExpiringContractAlert($contract));
            }
        }

        return Command::SUCCESS;
    }
}
