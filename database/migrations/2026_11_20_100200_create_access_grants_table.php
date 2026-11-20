<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Authority in one place and for a time (roadmap 4.4, D5): a role or a single
 * permission, given to a user on one scope (a project first; a program or a
 * city covers the projects under it) from starts_at until ends_at. A null
 * ends_at is open-ended. Taking a grant back fills revoked_at; rows are never
 * deleted, so the history of who could do what stays.
 *
 * Exactly one of role_id / permission_id is set (the check constraint).
 * General roles stay in Spatie's model_has_roles; this table is for scope
 * only. The table is additive: dropping it changes nothing else.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('access_grants', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('role_id')->nullable()->constrained('roles')->cascadeOnDelete();
            $table->foreignId('permission_id')->nullable()->constrained('permissions')->cascadeOnDelete();
            $table->string('scope_type', 30);
            $table->unsignedBigInteger('scope_id');
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->foreignId('granted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('delegated_from_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('revoked_at')->nullable();
            $table->foreignId('revoked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reason')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'scope_type', 'scope_id', 'ends_at'], 'access_grants_lookup_index');
        });

        DB::statement('ALTER TABLE access_grants ADD CONSTRAINT access_grants_one_subject_check CHECK ((role_id IS NULL) <> (permission_id IS NULL))');
    }

    public function down(): void
    {
        Schema::dropIfExists('access_grants');
    }
};
