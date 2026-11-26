<?php

declare(strict_types=1);

namespace Modules\Hr\Actions\Contracts;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\Hr\Actions\Employees\CreateEmployeeAccount;
use Modules\Hr\Enums\ContractType as ContractTypeEnum;
use Modules\Hr\Models\Contract;
use Modules\Hr\Models\Employee;

/**
 * What a real, active contract does to its employee (the old EmployeeActivation):
 * a complete employee awaiting a contract becomes active and gets a card
 * token, and an employee without a login gets one (username@hcsyria.org,
 * role "employee"); the old password-setup email is a stub event. Idempotent and serialised
 * on the employee row. Onboarding journeys are not started here.
 */
class ActivateEmployeeForContract
{
    public const NONE = 'none';

    public const ACCOUNT_CREATED = 'account-created';

    public const EMAIL_TAKEN = 'email-taken';

    public function handle(Contract $contract): string
    {
        if ((int) $contract->type === ContractTypeEnum::Draft->value || ! $contract->is_active) {
            return self::NONE;
        }

        [$outcome, $email] = DB::transaction(function () use ($contract): array {
            $employee = Employee::query()->lockForUpdate()->find($contract->employee_id);

            if ($employee === null || $employee->isDraft()) {
                return [self::NONE, null];
            }

            if (! $employee->is_active) {
                $employee->is_active = true;
                $employee->digital_id_token ??= Str::random(64);
                $employee->save();
                $employee->forgetAvatars();
            }

            return $this->ensureAccount($employee);
        });

        if ($outcome === self::ACCOUNT_CREATED) {
            // HR emailed a password-setup link here; the hub has no password
            // reset flow, so how a new account first signs in is still open.
            // Listeners can pick this up once that is decided.
            event('hr.employee.account-created', [$email]);
        }

        return $outcome;
    }

    /**
     * @return array{0: string, 1: ?string}
     */
    private function ensureAccount(Employee $employee): array
    {
        if ($employee->account()->exists()) {
            return [self::NONE, null];
        }

        $email = CreateEmployeeAccount::workEmail($employee);

        if ($email === null) {
            return [self::NONE, null];
        }

        if (! CreateEmployeeAccount::emailIsFree($email)) {
            Log::warning('Employee activation: work email already in use, no account created.', ['employee_id' => $employee->id]);

            return [self::EMAIL_TAKEN, null];
        }

        app(CreateEmployeeAccount::class)->createLogin($employee, $email);

        return [self::ACCOUNT_CREATED, $email];
    }
}
