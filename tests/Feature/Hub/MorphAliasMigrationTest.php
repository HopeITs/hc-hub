<?php

use App\Models\Employee;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\PermissionRegistrar;

function morphAliasMigration(): Migration
{
    return require database_path('migrations/2026_10_29_000001_use_morph_aliases_in_polymorphic_columns.php');
}

/**
 * Rows as HR wrote them before the morph map existed: full class names.
 */
function seedLegacyMorphRows(Employee $employee, User $user, Role $role, Permission $permission): void
{
    // Creating the fixtures logged activity of its own; keep only legacy rows.
    DB::table('activity_log')->delete();

    DB::table('model_has_roles')->insert([
        'role_id' => $role->id,
        'model_type' => 'App\\Models\\User',
        'model_id' => $user->id,
        'is_active' => true,
    ]);

    DB::table('model_has_permissions')->insert([
        'permission_id' => $permission->id,
        'model_type' => 'App\\Models\\User',
        'model_id' => $user->id,
    ]);

    DB::table('activity_log')->insert([
        'log_name' => 'default',
        'description' => 'updated',
        'subject_type' => 'App\\Models\\Employee',
        'subject_id' => $employee->id,
        'causer_type' => 'App\\Models\\User',
        'causer_id' => $user->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('notifications')->insert([
        'id' => (string) Str::uuid(),
        'type' => 'App\\Notifications\\Legacy',
        'notifiable_type' => 'App\\Models\\User',
        'notifiable_id' => $user->id,
        'data' => '{}',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('news')->insert([
        'title_ar' => 'خبر',
        'description_ar' => 'نص',
        'newsable_type' => 'App\\Models\\Employee',
        'newsable_id' => $employee->id,
        'event_date' => now()->toDateString(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('media')->insert([
        'model_type' => 'App\\Models\\Employee',
        'model_id' => $employee->id,
        'uuid' => (string) Str::uuid(),
        'collection_name' => 'attachments',
        'name' => 'cv',
        'file_name' => 'cv.pdf',
        'mime_type' => 'application/pdf',
        'disk' => 'local',
        'size' => 1,
        'manipulations' => '[]',
        'custom_properties' => '[]',
        'generated_conversions' => '[]',
        'responsive_images' => '[]',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

test('the migration rewrites legacy class names to aliases and back', function () {
    $employee = Employee::factory()->create();
    $user = User::factory()->create(['employee_id' => $employee->id]);
    $role = Role::findOrCreate('legacy-role', 'web');
    $permission = Permission::findOrCreate('legacy.permission', 'web');

    seedLegacyMorphRows($employee, $user, $role, $permission);

    morphAliasMigration()->up();

    expect(DB::table('activity_log')->value('subject_type'))->toBe('employee')
        ->and(DB::table('activity_log')->value('causer_type'))->toBe('user')
        ->and(DB::table('news')->value('newsable_type'))->toBe('employee')
        ->and(DB::table('media')->value('model_type'))->toBe('employee')
        ->and(DB::table('notifications')->value('notifiable_type'))->toBe('user')
        ->and(DB::table('model_has_roles')->value('model_type'))->toBe('user')
        ->and(DB::table('model_has_permissions')->value('model_type'))->toBe('user');

    // The rewritten rows resolve through the enforced map.
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $activity = Activity::query()->with(['subject', 'causer'])->firstOrFail();
    $freshUser = User::query()->with(['roles', 'permissions', 'notifications'])->findOrFail($user->id);

    expect($activity->subject)->toBeInstanceOf(Employee::class)
        ->and($activity->subject->is($employee))->toBeTrue()
        ->and($activity->causer->is($user))->toBeTrue()
        ->and($freshUser->roles->pluck('name')->all())->toBe(['legacy-role'])
        ->and($freshUser->permissions->pluck('name')->all())->toBe(['legacy.permission'])
        ->and($freshUser->hasActivePermission('legacy.permission'))->toBeTrue()
        ->and($freshUser->notifications)->toHaveCount(1)
        ->and($employee->fresh()->getMedia('attachments'))->toHaveCount(1);

    morphAliasMigration()->down();

    expect(DB::table('activity_log')->value('subject_type'))->toBe('App\\Models\\Employee')
        ->and(DB::table('activity_log')->value('causer_type'))->toBe('App\\Models\\User')
        ->and(DB::table('news')->value('newsable_type'))->toBe('App\\Models\\Employee')
        ->and(DB::table('media')->value('model_type'))->toBe('App\\Models\\Employee')
        ->and(DB::table('notifications')->value('notifiable_type'))->toBe('App\\Models\\User')
        ->and(DB::table('model_has_roles')->value('model_type'))->toBe('App\\Models\\User')
        ->and(DB::table('model_has_permissions')->value('model_type'))->toBe('App\\Models\\User');
});

test('the migration leaves rows that already use aliases untouched', function () {
    $employee = Employee::factory()->create();
    DB::table('activity_log')->delete();

    DB::table('activity_log')->insert([
        'log_name' => 'default',
        'description' => 'created',
        'subject_type' => 'employee',
        'subject_id' => $employee->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    morphAliasMigration()->up();

    expect(DB::table('activity_log')->pluck('subject_type')->all())->toBe(['employee']);
});
