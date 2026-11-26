<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Onboarding journey, extended:
 *
 *  - the direct manager's own orientation session (programs, how work runs),
 *    with the same schedule-then-record rule as HR's session;
 *  - checklist_items.onboarding_group, which says which delivery step an item
 *    belongs to (electronics | card | after_probation | other | exit), set once
 *    by name and idempotently; a "Mouse" item is added when it is missing;
 *  - policy_documents.required_for_onboarding and policy_acknowledgements, the
 *    volunteer's "read and agree" per required document.
 *
 * Additive. Existing journeys keep their rows; a completed one stays completed
 * (OnboardingJourneys never reopens it on its own). Rollback drops what is
 * added here and nothing else reads it.
 *
 * Every foreign key and index is named here (the generated names pass MySQL's
 * 64-character limit once a table prefix is added) and every step checks what
 * already exists, so a run that stopped half way can be repeated.
 */
return new class extends Migration
{
    /** Item name => group. Anything else stays "other"; the order-3 rows of these names are "exit". */
    private const GROUPS = [
        'Email' => 'electronics',
        'Email Signature' => 'electronics',
        'Laptop' => 'electronics',
        'Mouse' => 'electronics',
        'Mobile' => 'electronics',
        'SIM' => 'electronics',
        'ID Cartoon' => 'card',
        'ID Plastic' => 'card',
        'Mug' => 'after_probation',
    ];

    public function up(): void
    {
        $columns = [
            'manager_orientation_scheduled_for' => fn (Blueprint $table) => $table->date('manager_orientation_scheduled_for')->nullable()->after('orientation_completed_by'),
            'manager_orientation_scheduled_by' => fn (Blueprint $table) => $table->unsignedBigInteger('manager_orientation_scheduled_by')->nullable()->after('manager_orientation_scheduled_for'),
            'manager_orientation_completed_at' => fn (Blueprint $table) => $table->timestamp('manager_orientation_completed_at')->nullable()->after('manager_orientation_scheduled_by'),
            'manager_orientation_completed_by' => fn (Blueprint $table) => $table->unsignedBigInteger('manager_orientation_completed_by')->nullable()->after('manager_orientation_completed_at'),
        ];

        foreach ($columns as $name => $add) {
            if (! Schema::hasColumn('onboarding_journeys', $name)) {
                Schema::table('onboarding_journeys', fn (Blueprint $table) => $add($table));
            }
        }

        foreach (['manager_orientation_scheduled_by' => 'onb_journeys_mgr_scheduled_by_fk', 'manager_orientation_completed_by' => 'onb_journeys_mgr_completed_by_fk'] as $column => $name) {
            $this->foreignKey('onboarding_journeys', $column, 'users', $name);
        }

        if (! Schema::hasColumn('checklist_items', 'onboarding_group')) {
            Schema::table('checklist_items', function (Blueprint $table): void {
                $table->string('onboarding_group', 20)->default('other')->after('order');
            });
        }

        foreach (self::GROUPS as $name => $group) {
            DB::table('checklist_items')->where('name', $name)->update(['onboarding_group' => $group]);
        }

        // The return rows (order 3) of the items named above belong to the exit.
        DB::table('checklist_items')->where('order', 3)->whereIn('name', array_keys(self::GROUPS))->update(['onboarding_group' => 'exit']);

        if (! DB::table('checklist_items')->where('name', 'Mouse')->exists()) {
            DB::table('checklist_items')->insert([
                'name' => 'Mouse',
                'order' => 1,
                'onboarding_group' => 'electronics',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if (! Schema::hasColumn('policy_documents', 'required_for_onboarding')) {
            Schema::table('policy_documents', function (Blueprint $table): void {
                $table->boolean('required_for_onboarding')->default(false)->after('synced_at');
            });
        }

        if (! Schema::hasTable('policy_acknowledgements')) {
            Schema::create('policy_acknowledgements', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('employee_id');
                $table->unsignedBigInteger('policy_document_id');
                $table->timestamp('acknowledged_at');
                // The version read: a later change to the document is visible against it.
                $table->timestamp('document_modified_at')->nullable();
                $table->timestamps();

                $table->unique(['employee_id', 'policy_document_id'], 'policy_acks_employee_document_unique');
                $table->index('policy_document_id', 'policy_acks_document_idx');
            });
        }

        $this->foreignKey('policy_acknowledgements', 'employee_id', 'employees', 'policy_acks_employee_fk', 'cascade');
        $this->foreignKey('policy_acknowledgements', 'policy_document_id', 'policy_documents', 'policy_acks_document_fk', 'cascade');
    }

    public function down(): void
    {
        DB::table('checklist_items')->where('name', 'Mouse')->where('order', 1)->delete();

        Schema::dropIfExists('policy_acknowledgements');

        if (Schema::hasColumn('policy_documents', 'required_for_onboarding')) {
            Schema::table('policy_documents', function (Blueprint $table): void {
                $table->dropColumn('required_for_onboarding');
            });
        }

        if (Schema::hasColumn('checklist_items', 'onboarding_group')) {
            Schema::table('checklist_items', function (Blueprint $table): void {
                $table->dropColumn('onboarding_group');
            });
        }

        foreach (['manager_orientation_scheduled_by' => 'onb_journeys_mgr_scheduled_by_fk', 'manager_orientation_completed_by' => 'onb_journeys_mgr_completed_by_fk'] as $column => $name) {
            if ($this->hasForeignKey('onboarding_journeys', $name)) {
                Schema::table('onboarding_journeys', fn (Blueprint $table) => $table->dropForeign($name));
            }
        }

        foreach (['manager_orientation_scheduled_for', 'manager_orientation_scheduled_by', 'manager_orientation_completed_at', 'manager_orientation_completed_by'] as $column) {
            if (Schema::hasColumn('onboarding_journeys', $column)) {
                Schema::table('onboarding_journeys', fn (Blueprint $table) => $table->dropColumn($column));
            }
        }
    }

    private function foreignKey(string $table, string $column, string $references, string $name, string $onDelete = 'set null'): void
    {
        if ($this->hasForeignKey($table, $name)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($column, $references, $name, $onDelete): void {
            $blueprint->foreign($column, $name)->references('id')->on($references)->onDelete($onDelete);
        });
    }

    private function hasForeignKey(string $table, string $name): bool
    {
        return collect(Schema::getForeignKeys($table))->contains(fn (array $key): bool => $key['name'] === $name);
    }
};
