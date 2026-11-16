<?php

declare(strict_types=1);

namespace Modules\Hr\Console;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Modules\Core\Models\User;
use Modules\Hr\Actions\Contracts\ActivateEmployeeForContract;
use Modules\Hr\Enums\ContractType;
use Modules\Hr\Models\Employee;

/**
 * Lists the gaps between employees and logins: employees with no login, and
 * logins with no employee. Read only: it changes nothing.
 *
 * Each employee with no login is classified against the rules of
 * ActivateEmployeeForContract, which is what gives an employee a login when a
 * real contract becomes active:
 * - draft: a draft record, or no real contract at all, so activation never ran;
 * - username empty: activation runs but silently creates nothing;
 * - email taken: username@hcsyria.org already belongs to another login;
 * - not explained: activation would have made a login, so the record most
 *   likely came in through hub:import-hr, which never runs activation.
 * A real contract that has since ended still counts: activation ran when it
 * was active, so such a former employee is classed by their username too.
 * A username that would not make a valid email (a space, say) is flagged too.
 */
class ReportAccountGapsCommand extends Command
{
    public const DRAFT = 'draft';

    public const USERNAME_EMPTY = 'username empty';

    public const EMAIL_TAKEN = 'email taken';

    public const NOT_EXPLAINED = 'not explained';

    /**
     * @var string
     */
    protected $signature = 'hub:account-gaps';

    /**
     * @var string
     */
    protected $description = 'List employees with no login and logins with no employee (read only)';

    public function handle(): int
    {
        $this->reportEmployeesWithoutLogin();
        $this->newLine();
        $this->reportLoginsWithoutEmployee();

        $this->newLine();
        $this->components->info('Nothing was changed.');

        return self::SUCCESS;
    }

    private function reportEmployeesWithoutLogin(): void
    {
        $employees = Employee::query()
            ->whereDoesntHave('account')
            ->withExists([
                'allContracts as has_real_contract' => fn (Builder $contract) => $this->real($contract),
                'allContracts as has_active_real_contract' => fn (Builder $contract) => $this->real($contract)->where('is_active', true),
            ])
            ->orderBy('id')
            ->get(['id', 'first_name_en', 'last_name_en', 'username', 'record_status', 'is_active']);

        if ($employees->isEmpty()) {
            $this->components->info('Every employee has a login.');

            return;
        }

        $rows = $employees->map(fn (Employee $employee): array => $this->classify($employee));

        $this->components->warn("{$employees->count()} employees have no login:");
        $this->table(
            ['Employee', 'Name', 'Username', 'Active', 'Class', 'Why'],
            $rows->map(fn (array $row): array => [
                (string) $row['id'],
                $row['name'],
                $row['username'],
                $row['active'],
                $row['class'],
                $row['why'],
            ])->all(),
        );

        $this->line('By class:');
        foreach ([self::DRAFT, self::USERNAME_EMPTY, self::EMAIL_TAKEN, self::NOT_EXPLAINED] as $class) {
            $ids = $rows->where('class', $class)->pluck('id');
            $this->line(sprintf('  %-15s %3d%s', $class, $ids->count(), $ids->isEmpty() ? '' : '  ('.$ids->implode(', ').')'));
        }

        $invalid = $rows->where('invalid_username', true)->pluck('id');
        $this->line(sprintf('  %-15s %3d%s', 'invalid username', $invalid->count(), $invalid->isEmpty() ? '' : '  ('.$invalid->implode(', ').')'));
    }

    /**
     * Contracts activation treats as real: any type but Draft (a null type
     * casts to 0, so it counts).
     *
     * @template TBuilder of Builder
     *
     * @param  TBuilder  $contract
     * @return TBuilder
     */
    private function real(Builder $contract): Builder
    {
        return $contract->where(fn (Builder $type) => $type->whereNull('type')->orWhere('type', '!=', ContractType::Draft->value));
    }

    /**
     * Mirrors ActivateEmployeeForContract::handle() and ensureAccount().
     *
     * @return array{id: int, name: string, username: string, active: string, class: string, why: string, invalid_username: bool}
     */
    private function classify(Employee $employee): array
    {
        $username = strtolower(trim((string) $employee->username));
        $email = $username.'@hcsyria.org';
        $invalidUsername = $username !== '' && (preg_match('/\s/', $username) === 1 || filter_var($email, FILTER_VALIDATE_EMAIL) === false);

        [$class, $why] = match (true) {
            $employee->isDraft() => [self::DRAFT, 'draft record'],
            ! $employee->getAttribute('has_real_contract') => [self::DRAFT, 'no real contract'],
            $username === '' => [self::USERNAME_EMPTY, 'activation skips an empty username'],
            User::query()->where('email', $email)->exists() => [self::EMAIL_TAKEN, "{$email} belongs to another login"],
            default => [self::NOT_EXPLAINED, 'activation would make a login; likely imported'],
        };

        if ($class !== self::DRAFT && ! $employee->getAttribute('has_active_real_contract')) {
            $why .= '; contract since ended';
        }

        if ($invalidUsername) {
            $why .= '; invalid username';
        }

        return [
            'id' => (int) $employee->id,
            'name' => trim($employee->first_name_en.' '.$employee->last_name_en),
            'username' => $employee->username === null ? '-' : '"'.$employee->username.'"',
            'active' => $employee->is_active ? 'yes' : 'no',
            'class' => $class,
            'why' => $why,
            'invalid_username' => $invalidUsername,
        ];
    }

    private function reportLoginsWithoutEmployee(): void
    {
        /** @var Collection<int, User> $users */
        $users = User::query()
            ->with('roles:id,name')
            ->where(fn (Builder $query) => $query->whereNull('employee_id')->orWhereDoesntHave('employee'))
            ->orderBy('id')
            ->get(['id', 'email', 'employee_id', 'last_seen_at']);

        if ($users->isEmpty()) {
            $this->components->info('Every login has an employee.');

            return;
        }

        $this->components->warn("{$users->count()} logins have no employee:");
        $this->table(
            ['User', 'Email', 'Employee id', 'Last seen', 'Roles'],
            $users->map(fn (User $user): array => [
                (string) $user->id,
                $user->email,
                $user->employee_id === null ? '-' : $user->employee_id.' (missing)',
                $user->getAttribute('last_seen_at') === null ? 'never' : (string) $user->getAttribute('last_seen_at'),
                $user->roles->pluck('name')->implode(', ') ?: '-',
            ])->all(),
        );
    }
}
