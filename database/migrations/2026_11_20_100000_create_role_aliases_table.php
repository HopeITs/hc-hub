<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Which roles a role name stands for (roadmap 4.2, D5): HR's general-manager
 * and the hub's General Manager mean the same thing to HR's checks. The pairs
 * were a constant in Modules\Hr\Support\Employees\HrAccess; here they are
 * rows, so a renamed role keeps its meaning. Temporary: the table goes once
 * every check reads a permission instead of a role name.
 *
 * Only links are written. No role, permission or grant is touched, so the
 * (role x permission) matrix is the same before and after.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('role_aliases', function (Blueprint $table): void {
            $table->id();
            $table->string('alias', 60);
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['alias', 'role_id']);
        });

        $roles = DB::table('roles')->where('guard_name', 'web')->pluck('id', 'name');
        $now = now();

        /** @var list<string> $names */
        foreach (config('access-control.role_aliases', []) as $alias => $names) {
            foreach ($names as $name) {
                if ($roles->has($name)) {
                    DB::table('role_aliases')->insert([
                        'alias' => $alias,
                        'role_id' => $roles->get($name),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('role_aliases');
    }
};
