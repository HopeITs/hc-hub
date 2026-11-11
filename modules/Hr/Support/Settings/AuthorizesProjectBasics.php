<?php

declare(strict_types=1);

namespace Modules\Hr\Support\Settings;

use Illuminate\Database\Eloquent\Model;
use Modules\Core\Models\User;

/**
 * The policy of every HR reference table (departments, job levels, leave
 * types...): the old app's "Project Basics" permissions, unchanged.
 */
trait AuthorizesProjectBasics
{
    public function viewAny(User $user): bool
    {
        return $user->can('project-basics.view');
    }

    public function create(User $user): bool
    {
        return $user->can('project-basics.create');
    }

    public function update(User $user, Model $row): bool
    {
        return $user->can('project-basics.update');
    }

    public function delete(User $user, Model $row): bool
    {
        return $user->can('project-basics.delete');
    }
}
