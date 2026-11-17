<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The misnamed columns that hold an employee id, each with its new name
     * (roadmap 1.9, D2).
     *
     * @var array<string, array{0: string, 1: string}>
     */
    private const array Columns = [
        'projects' => ['user_id', 'owner_employee_id'],
        'milestones' => ['responsible_user_id', 'responsible_employee_id'],
    ];

    /**
     * Expand step: add each new column next to the old one, with the same
     * restricting foreign key to employees, and copy the old values in. The
     * old columns stay and the models keep both in sync until a later PR
     * drops them.
     */
    public function up(): void
    {
        foreach (self::Columns as $table => [$old, $new]) {
            Schema::table($table, function (Blueprint $blueprint) use ($old, $new): void {
                $blueprint->foreignId($new)->nullable()->after($old)->constrained('employees')->restrictOnDelete();
            });
        }

        $this->backfill();
    }

    /**
     * Copy every old value into its new column. Safe to run again.
     */
    public function backfill(): void
    {
        foreach (self::Columns as $table => [$old, $new]) {
            DB::table($table)->update([$new => DB::raw($old)]);
        }
    }

    public function down(): void
    {
        foreach (self::Columns as $table => [, $new]) {
            Schema::table($table, function (Blueprint $blueprint) use ($new): void {
                $blueprint->dropConstrainedForeignId($new);
            });
        }
    }
};
