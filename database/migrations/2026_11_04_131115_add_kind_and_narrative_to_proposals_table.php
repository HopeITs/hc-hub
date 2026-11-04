<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A proposal gets a kind (D-55b, W-32): `project`, the default every existing
 * row keeps, or `payroll`, a center's volunteer salaries and equipment with a
 * narrative instead of projects, a logframe or targets. The kind is set when
 * the proposal is created and never changes (contracts.md §7).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('proposals', 'kind')) {
            Schema::table('proposals', function (Blueprint $table): void {
                $table->string('kind', 10)->default('project')->after('status');
                $table->index('kind', 'proposals_kind_index');
            });
        }

        if (! Schema::hasColumn('proposals', 'narrative')) {
            Schema::table('proposals', function (Blueprint $table): void {
                $table->text('narrative')->nullable()->after('notes');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('proposals', 'narrative')) {
            Schema::table('proposals', function (Blueprint $table): void {
                $table->dropColumn('narrative');
            });
        }

        if (Schema::hasColumn('proposals', 'kind')) {
            Schema::table('proposals', function (Blueprint $table): void {
                $table->dropIndex('proposals_kind_index');
                $table->dropColumn('kind');
            });
        }
    }
};
