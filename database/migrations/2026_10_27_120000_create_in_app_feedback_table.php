<?php

use App\Models\Permission;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

/*
 * D3 / DEC-08: in-app feedback. Holds the typed text and exactly the context
 * the form discloses (page route name, language, active role). Nothing else:
 * no URL or query string, no IP, no user agent, no record ids, no files.
 *
 * Rollback drops the table and the permission; the rows are disposable by
 * policy (one-year retention), so there is nothing to recover.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('in_app_feedback', function (Blueprint $table) {
            $table->id();
            // Nullable and set null on delete: the text stays readable until its
            // one-year expiry even if the account is removed.
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('category', 16)->nullable();
            $table->text('message');
            $table->string('route_name', 150)->nullable();
            $table->string('locale', 5);
            $table->string('role_name', 64)->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
        });

        // Not in access-control.groups on purpose: no role is auto-granted it.
        // Only a direct grant to a named account counts (config/feedback.php).
        Permission::findOrCreate('feedback.view', 'web');
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Schema::dropIfExists('in_app_feedback');

        Permission::query()->where('name', 'feedback.view')->where('guard_name', 'web')->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
