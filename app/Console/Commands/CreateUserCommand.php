<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Role;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Modules\Core\Models\User;

use function Laravel\Prompts\password;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

/**
 * Gives an account access to the hub with a role.
 *
 * Self-registration is disabled. An account HR already created (hub:import-hr)
 * only gets its role; an email no account uses yet becomes a new account with
 * the given password and no linked employee (a system or local account).
 */
class CreateUserCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'user:create
        {--email= : The account email}
        {--password= : The password, only for a new account}
        {--role= : An existing role name; omit for the default role}
        {--unverified : Leave a new account\'s email address unverified}';

    /**
     * @var string
     */
    protected $description = 'Give an account access with a role, creating the account when needed';

    /**
     * Collect the details, validate them, and store the user.
     */
    public function handle(): int
    {
        $interactive = $this->input->isInteractive();

        $email = $this->option('email') ?? ($interactive ? text('Email', required: true) : null);

        $roleNames = Role::query()->orderBy('name')->pluck('name')->all();

        $defaultRole = (string) config('access-control.default_role');
        $role = $this->option('role')
            ?? ($interactive ? select('Role', $roleNames, in_array($defaultRole, $roleNames, true) ? $defaultRole : null) : $defaultRole);

        $validator = Validator::make([
            'email' => $email,
            'role' => $role,
        ], [
            'email' => ['required', 'string', 'email', 'max:255'],
            'role' => ['required', 'string', Rule::in($roleNames)],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->components->error($message);
            }

            return self::FAILURE;
        }

        /** @var array{email: string, role: string} $validated */
        $validated = $validator->validated();

        $user = User::query()->with('roles:id,name')->where('email', $validated['email'])->first();

        if ($user !== null) {
            if ($user->hasLostAccess()) {
                $this->components->error("The employee behind {$validated['email']} is not active.");

                return self::FAILURE;
            }

            if ($user->roles->isNotEmpty()) {
                $this->components->error("{$validated['email']} already has access.");

                return self::FAILURE;
            }

            $user->grantAccess($validated['role']);
            $this->components->info("Gave {$user->email} the {$validated['role']} role.");

            return self::SUCCESS;
        }

        $secret = $this->option('password') ?? ($interactive ? password('Password', required: true) : null);
        $passwordCheck = Validator::make(['password' => $secret], ['password' => ['required', 'string', Password::defaults()]]);

        if ($passwordCheck->fails()) {
            foreach ($passwordCheck->errors()->all() as $message) {
                $this->components->error($message);
            }

            return self::FAILURE;
        }

        $user = new User;
        $user->forceFill([
            'email' => $validated['email'],
            'password' => (string) $secret,
            'email_verified_at' => $this->option('unverified') ? null : now(),
        ])->save();

        $user->grantAccess($validated['role']);

        $this->components->info("Created {$user->email} as {$validated['role']}.");

        return self::SUCCESS;
    }
}
