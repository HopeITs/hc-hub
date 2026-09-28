<?php

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

/**
 * Matching-indicator rules HR can change without a deploy.
 *
 * One row holding only what differs from config('recruitment.indicators');
 * a missing key means "use the config value". The permission goes to the
 * administrator roles only — these rules decide what "met" means on every
 * applicant's screen — and other roles are given it from Access Management.
 */
return new class extends Migration
{
    private const PERMISSION = 'recruitment-indicators.manage';

    public function up(): void
    {
        Schema::create('recruitment_indicator_settings', function (Blueprint $table) {
            $table->id();
            $table->json('overrides_json');
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Permission::findOrCreate(self::PERMISSION, 'web');

        foreach (config('access-control.administrator_roles', []) as $roleName) {
            Role::findOrCreate($roleName, 'web')->givePermissionTo(self::PERMISSION);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Schema::dropIfExists('recruitment_indicator_settings');
        Permission::query()->where('name', self::PERMISSION)->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
