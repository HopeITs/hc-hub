<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Support\UserReferences;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Modules\Core\Models\User;

/**
 * Cancels the spare login of an employee who has two (D2, roadmap 1.7). The
 * login a person chose to keep gets the union of both logins' roles; the
 * spare one is deleted. Refused when the two logins are not the same
 * employee's, or when the spare one carries anything but role grants, so no
 * work is lost. Without --force it only says what it would do.
 */
class CancelDuplicateAccountCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'hub:cancel-duplicate-account
        {cancel : The login (users.id) to cancel}
        {--keep= : The login (users.id) of the same employee that stays}
        {--force : Really change the database}';

    /**
     * @var string
     */
    protected $description = 'Cancel the spare login of an employee with two, keeping all roles on the other';

    public function handle(UserReferences $references): int
    {
        $cancel = User::query()->with('roles')->find((int) $this->argument('cancel'));
        $keep = User::query()->with('roles')->find((int) $this->option('keep'));

        if ($cancel === null || $keep === null || $cancel->is($keep)) {
            $this->components->error('Give two different existing logins: the one to cancel and --keep=<id>.');

            return self::FAILURE;
        }

        if ($cancel->employee_id === null || $cancel->employee_id !== $keep->employee_id) {
            $this->components->error("Logins #{$cancel->id} and #{$keep->id} do not belong to the same employee.");

            return self::FAILURE;
        }

        $carried = $references->countsFor((int) $cancel->id);
        unset($carried['model_has_roles']);

        if ($carried !== []) {
            $this->components->error("Login #{$cancel->id} still carries rows: ".UserReferences::describe($carried).'. Move them first.');

            return self::FAILURE;
        }

        $added = $cancel->roles->pluck('name')->diff($keep->roles->pluck('name'))->values();

        $this->components->info("Keep #{$keep->id} ({$keep->email}), cancel #{$cancel->id} ({$cancel->email}).");
        $this->components->info('Roles added to the kept login: '.($added->isEmpty() ? 'none' : $added->implode(', ')));

        if (! $this->option('force')) {
            $this->components->warn('Dry run: nothing was changed. Add --force to apply.');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($cancel, $keep, $added): void {
            if ($added->isNotEmpty()) {
                $keep->assignRole($added->all());
            }

            $cancel->syncRoles([]);
            $cancel->delete();
        });

        $this->components->info("Login #{$cancel->id} was cancelled.");

        return self::SUCCESS;
    }
}
