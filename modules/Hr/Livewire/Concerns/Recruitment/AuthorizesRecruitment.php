<?php

declare(strict_types=1);

namespace Modules\Hr\Livewire\Concerns\Recruitment;

use Flux\Flux;
use Illuminate\Validation\ValidationException;
use Modules\Core\Models\User;
use Modules\Hr\Support\Recruitment\Access;
use Modules\Hr\Support\Recruitment\RecruitmentConfig;

/**
 * What every recruitment page needs: the signed-in user, the old app's
 * `active_permission:` route guard (a permission of the active role), and a
 * toast in the hub's style.
 */
trait AuthorizesRecruitment
{
    protected function viewer(): User
    {
        RecruitmentConfig::load();

        /** @var User $user */
        $user = auth()->user();

        return $user;
    }

    /** The old `active_permission:` middleware: 403 without the permission on the active role. */
    protected function requirePermission(string ...$permissions): void
    {
        foreach ($permissions as $permission) {
            abort_unless(Access::hasActivePermission($this->viewer(), $permission), 403);
        }
    }

    protected function may(string $permission): bool
    {
        return Access::hasActivePermission($this->viewer(), $permission);
    }

    protected function toast(string $message, bool $success = true): void
    {
        Flux::toast(variant: $success ? 'success' : 'danger', text: $message);
    }

    /**
     * Run a service call, turning its refusal into a toast instead of an
     * error page; validation errors also land in the bag for the fields.
     */
    protected function attempt(callable $action, ?string $success = null): bool
    {
        try {
            $action();
        } catch (ValidationException $exception) {
            $this->setErrorBag($exception->validator->errors());
            $this->toast(collect($exception->errors())->flatten()->unique()->implode(' '), false);

            return false;
        }

        if ($success !== null) {
            $this->toast($success);
        }

        return true;
    }
}
