<?php

declare(strict_types=1);

namespace App\Support\Desk;

use Modules\Core\Models\User;
use WeakMap;

/**
 * Who is asking, as their access stands now: the key the Desk's catalogues
 * (Destinations, Panels, Shortcuts) keep their per-request answers under.
 *
 * The key is the viewer's id with a digest of their permission and role
 * names, so a role given or taken a permission in the same request reads
 * afresh. Building that digest walks every permission (about 3 ms for a
 * hundred of them) and the catalogues ask for it hundreds of times per page,
 * so it is read once per user model and read again only when what it is made
 * from changes: the model's loaded `permissions` and `roles` relations and
 * each of those roles' loaded `permissions`. spatie/laravel-permission
 * unsets the relation on the model it changes (assignRole, removeRole,
 * syncRoles, givePermissionTo, revokePermissionTo, syncPermissions), and
 * refresh() / load() put new collections in place, so comparing those
 * collections by identity sees every change the digest itself would.
 */
final class AccessFingerprint
{
    /**
     * The last key read for each user model, with what it was read from.
     *
     * @var WeakMap<User, array{key: string, sources: list<mixed>}>|null
     */
    private static ?WeakMap $known = null;

    /**
     * The memo key for the user's access as it stands now.
     */
    public static function of(User $user): string
    {
        self::$known ??= new WeakMap;

        $known = self::$known[$user] ?? null;

        if ($known !== null && $known['sources'] === self::sourcesOf($user)) {
            return $known['key'];
        }

        $key = $user->id.':'.md5($user->getAllPermissions()->pluck('name')->sort()->implode(',').'|'.$user->getRoleNames()->implode(','));

        /* Read after the digest: building it is what loads the relations. */
        self::$known[$user] = ['key' => $key, 'sources' => self::sourcesOf($user)];

        return $key;
    }

    /**
     * Everything the digest is built from, as it is loaded on the model now:
     * the user's key, their direct permissions, their roles and each role
     * with its permissions (null for a relation not loaded). Two readings are
     * identical only while every one of those is the very same object.
     *
     * @return list<mixed>
     */
    private static function sourcesOf(User $user): array
    {
        $roles = $user->relationLoaded('roles') ? $user->getRelation('roles') : null;
        $sources = [
            $user->getKey(),
            $user->relationLoaded('permissions') ? $user->getRelation('permissions') : null,
            $roles,
        ];

        foreach ($roles ?? [] as $role) {
            $sources[] = $role;
            $sources[] = $role->relationLoaded('permissions') ? $role->getRelation('permissions') : null;
        }

        return $sources;
    }
}
