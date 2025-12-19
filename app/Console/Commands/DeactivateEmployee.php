<?php

namespace App\Console\Commands;

use App\Models\Employee;
use Illuminate\Console\Command;

class DeactivateEmployee extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'deactivate:employee';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Deactivate Employee when no active contracts';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        Employee::where("is_active", 1)->whereDoesntHave('contracts')->get()->each(function ($employee) {
            $employee->update(["is_active" => 0]);
        });

        $this->info('Finished deactivating employees');
    }
}
