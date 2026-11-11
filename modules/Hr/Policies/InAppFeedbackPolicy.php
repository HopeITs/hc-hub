<?php

declare(strict_types=1);

namespace Modules\Hr\Policies;

use Modules\Core\Models\User;
use Modules\Hr\Models\InAppFeedback;
use Modules\Hr\Support\Desk\HrViewer;

/**
 * In-app feedback (DEC-08): anyone signed in may send a note; only the IT
 * team (a running contract in the department coded 'IT') and administrators
 * read them.
 */
final class InAppFeedbackPolicy
{
    public const string ReaderDepartment = 'IT';

    public function viewAny(User $user): bool
    {
        return $user->isAdministrator()
            || strcasecmp((string) HrViewer::for($user)->departmentCode(), self::ReaderDepartment) === 0;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function delete(User $user, InAppFeedback $feedback): bool
    {
        return $this->viewAny($user);
    }
}
