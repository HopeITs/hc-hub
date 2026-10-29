<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

/*
 * Hub task 0.3: every permission name carries its module prefix. HR's
 * permissions become `hr.{group}.{action}`; the two platform groups
 * (access management, activity log) become `hub.{group}.{action}`.
 *
 * Grants survive because role_has_permissions and model_has_permissions
 * reference the permission id, so renaming the row is enough. The mapping is
 * frozen here on purpose: it must not follow later edits to the catalogue.
 *
 * Idempotent: a name that is not there is skipped. If both the old and the new
 * name exist (the new one created by a seed run before this migration), the
 * old row's grants move onto the new row and the old row is removed.
 *
 * activity_log properties that recorded permission names are audit history
 * and are left as they were written.
 */
return new class extends Migration
{
    private const GROUPS = [
        'hub' => [
            'access-management' => ['view', 'manage-employees', 'manage-roles', 'manage-permissions'],
            'activity-log' => ['view'],
        ],
        'hr' => [
            'statistics' => ['view'],
            'employees' => ['view', 'create', 'update', 'delete', 'export'],
            'contracts' => ['view', 'create', 'update', 'delete', 'export'],
            'programs' => ['view', 'create', 'update', 'delete'],
            'centers' => ['view', 'create', 'update', 'delete', 'export'],
            'positions' => ['view', 'create', 'update', 'delete', 'export'],
            'leaves' => ['view-own', 'view-all', 'create', 'update', 'delete', 'decide', 'review', 'manage-balances', 'export'],
            'project-basics' => ['view', 'create', 'update', 'delete', 'export'],
            'news' => ['view', 'create', 'update', 'delete'],
            'badges' => ['view', 'create', 'update', 'delete'],
            'archives' => ['view', 'create', 'update', 'delete', 'share', 'download'],
            'checklists' => ['view-own', 'view-all', 'manage', 'deliver', 'receive'],
        ],
    ];

    public function up(): void
    {
        $this->rename(self::renames());
    }

    public function down(): void
    {
        $this->rename(array_flip(self::renames()));
    }

    /**
     * Old (unprefixed) name => new (prefixed) name.
     *
     * @return array<string, string>
     */
    public static function renames(): array
    {
        $renames = [];

        foreach (self::GROUPS as $prefix => $groups) {
            foreach ($groups as $group => $actions) {
                foreach ($actions as $action) {
                    $renames["{$group}.{$action}"] = "{$prefix}.{$group}.{$action}";
                }
            }
        }

        return $renames;
    }

    /**
     * @param  array<string, string>  $renames  from => to
     */
    private function rename(array $renames): void
    {
        DB::transaction(function () use ($renames): void {
            foreach ($renames as $from => $to) {
                $rows = DB::table('permissions')->where('name', $from)->get(['id', 'guard_name']);

                foreach ($rows as $row) {
                    $existingId = DB::table('permissions')
                        ->where('name', $to)
                        ->where('guard_name', $row->guard_name)
                        ->value('id');

                    if ($existingId === null) {
                        DB::table('permissions')->where('id', $row->id)->update([
                            'name' => $to,
                            'updated_at' => now(),
                        ]);

                        continue;
                    }

                    $this->mergeGrants((int) $row->id, (int) $existingId);
                    DB::table('permissions')->where('id', $row->id)->delete();
                }
            }
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function mergeGrants(int $fromId, int $toId): void
    {
        $roleGrants = DB::table('role_has_permissions')
            ->where('permission_id', $fromId)
            ->pluck('role_id')
            ->map(fn ($roleId): array => ['permission_id' => $toId, 'role_id' => $roleId])
            ->all();

        DB::table('role_has_permissions')->insertOrIgnore($roleGrants);

        $modelGrants = DB::table('model_has_permissions')
            ->where('permission_id', $fromId)
            ->get(['model_type', 'model_id'])
            ->map(fn ($grant): array => [
                'permission_id' => $toId,
                'model_type' => $grant->model_type,
                'model_id' => $grant->model_id,
            ])
            ->all();

        DB::table('model_has_permissions')->insertOrIgnore($modelGrants);

        DB::table('role_has_permissions')->where('permission_id', $fromId)->delete();
        DB::table('model_has_permissions')->where('permission_id', $fromId)->delete();
    }
};
