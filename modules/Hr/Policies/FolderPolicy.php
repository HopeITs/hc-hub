<?php

declare(strict_types=1);

namespace Modules\Hr\Policies;

use Modules\Core\Models\User;
use Modules\Hr\Models\Folder;

/**
 * Archive folders: a folder is open to the people it is shared with
 * (folder_user, which includes its creator) and to administrators; only the
 * creator or an administrator manages it.
 */
class FolderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('archives.view');
    }

    public function view(User $user, Folder $folder): bool
    {
        return $user->can('archives.view')
            && ($user->isAdministrator() || $folder->users()->whereKey($user->id)->exists());
    }

    public function create(User $user): bool
    {
        return $user->can('archives.create');
    }

    public function manage(User $user, Folder $folder): bool
    {
        return $user->isAdministrator() || (int) $folder->user_id === (int) $user->id;
    }

    public function share(User $user, Folder $folder): bool
    {
        return $user->can('archives.share') && $this->manage($user, $folder);
    }
}
