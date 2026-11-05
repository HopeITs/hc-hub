<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Location rows on a proposal (contracts.md §7, W-34a, D-62): one row per
 * city and program, its centers, and the program request opened when the
 * row names a program HR does not have yet.
 *
 * `city_id`, `responsible_employee_id` and `center_id` are HR ids and carry
 * no foreign key (the `hr` connection is read-only). `program_id` and
 * `project_id` are local and nullOnDelete. `program_id` is nullable, so the
 * unique index leaves "no program yet" rows alone; the application keeps at
 * most one such row per city. The pivot `project_proposal` stays: a row with
 * `project_id` is that same link.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('proposal_locations')) {
            Schema::create('proposal_locations', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('proposal_id')->constrained('proposals', indexName: 'proposal_locations_proposal_id_foreign')->cascadeOnDelete();
                $table->unsignedBigInteger('city_id');
                $table->foreignId('program_id')->nullable()->constrained('programs', indexName: 'proposal_locations_program_id_foreign')->nullOnDelete();
                $table->unsignedBigInteger('responsible_employee_id')->nullable();
                $table->boolean('is_new')->default(false);
                $table->foreignId('project_id')->nullable()->constrained('projects', indexName: 'proposal_locations_project_id_foreign')->nullOnDelete();
                $table->unsignedSmallInteger('sort')->default(0);
                $table->timestamps();

                $table->unique(['proposal_id', 'city_id', 'program_id'], 'proposal_locations_proposal_city_program_unique');
                $table->index('responsible_employee_id', 'proposal_locations_responsible_employee_id_index');
            });
        }

        if (! Schema::hasTable('proposal_location_centers')) {
            Schema::create('proposal_location_centers', function (Blueprint $table): void {
                $table->foreignId('proposal_location_id')->constrained('proposal_locations', indexName: 'proposal_location_centers_location_id_foreign')->cascadeOnDelete();
                $table->unsignedBigInteger('center_id');

                $table->primary(['proposal_location_id', 'center_id'], 'proposal_location_centers_primary');
            });
        }

        if (! Schema::hasTable('program_requests')) {
            Schema::create('program_requests', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('proposal_location_id')->constrained('proposal_locations', indexName: 'program_requests_proposal_location_id_foreign')->cascadeOnDelete();
                $table->string('name_en');
                $table->string('name_ar');
                $table->string('status', 20)->default('pending');
                $table->unsignedBigInteger('hr_program_id')->nullable();
                $table->foreignId('requested_by')->nullable()->constrained('users', indexName: 'program_requests_requested_by_foreign')->nullOnDelete();
                $table->timestamps();

                $table->unique('proposal_location_id', 'program_requests_proposal_location_id_unique');
                $table->index('status', 'program_requests_status_index');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('program_requests');
        Schema::dropIfExists('proposal_location_centers');
        Schema::dropIfExists('proposal_locations');
    }
};
