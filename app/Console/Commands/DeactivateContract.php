<?php

namespace App\Console\Commands;

use App\Models\Contract;
use Illuminate\Console\Command;

class DeactivateContract extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'deactivate:contract';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'deactivate contract';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {

       Contract::where('is_active',true)->whereDate('end_date', '<', today())->get()->each(function ($contract) {
            $contract->is_active = false;
            $contract->save();
        });

        $this->info('Finished deactivating contract');
    }
}
