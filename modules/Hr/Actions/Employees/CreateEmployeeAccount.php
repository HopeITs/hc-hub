<?php

declare(strict_types=1);

namespace Modules\Hr\Actions\Employees;

use App\Models\Role;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Core\Models\User;
use Modules\Hr\Models\Employee;

/**
 * "Create login account" on the employee form's Contact tab: an active
 * employee (or one who already has a login) gets username@hcsyria.org with
 * the role "employee". An existing login follows a changed username. Never
 * removes a login. The password-setup email is the same stub event as
 * ActivateEmployeeForContract fires, since the hub has no reset flow yet.
 */
class CreateEmployeeAccount
{
    public const NONE = 'none';

    public const ACCOUNT_CREATED = 'account-created';

    public const EMAIL_UPDATED = 'email-updated';

    public const DOMAIN = '@hcsyria.org';

    public function handle(Employee $employee, User $actor): string
    {
        Gate::forUser($actor)->authorize('update', $employee);

        if (! self::mayHaveAccount($employee)) {
            return self::NONE;
        }

        $email = self::workEmail($employee);

        if ($email === null) {
            return self::NONE;
        }

        [$outcome, $email] = DB::transaction(function () use ($employee, $email): array {
            $account = $employee->account()->lockForUpdate()->first();

            if (! self::emailIsFree($email, $account?->id)) {
                throw ValidationException::withMessages(['createAccount' => __('hr_employees.validation.work_email_taken', ['email' => $email])]);
            }

            if ($account !== null) {
                if ($account->email === $email) {
                    return [self::NONE, null];
                }

                $account->forceFill(['email' => $email])->save();

                return [self::EMAIL_UPDATED, $email];
            }

            $user = new User;
            $user->forceFill([
                'email' => $email,
                'password' => Hash::make(Str::password(64)),
                'employee_id' => $employee->id,
            ])->save();

            if (Role::query()->where('name', 'employee')->exists()) {
                $user->assignRole('employee');
            }

            return [self::ACCOUNT_CREATED, $email];
        });

        if ($outcome === self::ACCOUNT_CREATED) {
            event('hr.employee.account-created', [$email]);
        }

        return $outcome;
    }

    /**
     * A draft never has a login; an inactive employee gets one with their
     * first real contract, unless they already have one.
     */
    public static function mayHaveAccount(Employee $employee): bool
    {
        return $employee->exists
            && ! $employee->isDraft()
            && ($employee->is_active || $employee->account()->exists());
    }

    public static function workEmail(Employee $employee): ?string
    {
        $username = strtolower(trim((string) $employee->username));

        return $username === '' ? null : $username.self::DOMAIN;
    }

    public static function emailIsFree(string $email, ?int $exceptUserId = null): bool
    {
        return ! User::query()
            ->where('email', $email)
            ->when($exceptUserId !== null, fn ($query) => $query->whereKeyNot($exceptUserId))
            ->exists();
    }
}
