<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tracking's columns that hold an employee, city, center, program or
     * department id were plain numbers while those rows lived in HR's own
     * database. Everything is one database now, so they get real foreign
     * keys: restrict where the row is history that must keep its person or
     * place, null on delete where the link is optional. A column with orphan
     * rows is skipped and reported (logged and printed), never cleaned up here.
     *
     * @var list<array{0: string, 1: string, 2: string, 3: 'restrict'|'null'}>
     */
    private const array Keys = [
        // Employees.
        ['projects', 'user_id', 'employees', 'restrict'],
        ['milestones', 'responsible_user_id', 'employees', 'restrict'],
        ['programs', 'manager_id', 'employees', 'null'],
        ['annual_plans', 'manager_id', 'employees', 'restrict'],
        ['assignments', 'employee_id', 'employees', 'restrict'],
        ['task_assignees', 'employee_id', 'employees', 'restrict'],
        ['meal_coverages', 'employee_id', 'employees', 'restrict'],
        ['proposal_reviewers', 'employee_id', 'employees', 'restrict'],
        ['proposal_locations', 'responsible_employee_id', 'employees', 'null'],
        ['donor_reports', 'responsible_employee_id', 'employees', 'null'],
        ['supervisions', 'supervisee_employee_id', 'employees', 'restrict'],
        ['supervisions', 'supervisor_employee_id', 'employees', 'restrict'],
        // Places, programs and departments.
        ['projects', 'city_id', 'cities', 'restrict'],
        ['projects', 'center_id', 'centers', 'null'],
        ['projects', 'host_center_id', 'centers', 'null'],
        ['proposal_locations', 'city_id', 'cities', 'restrict'],
        ['proposal_location_centers', 'center_id', 'centers', 'restrict'],
        ['meal_coverages', 'city_id', 'cities', 'null'],
        ['tasks', 'department_id', 'departments', 'null'],
        ['library_indicators', 'program_id', 'programs', 'null'],
        ['activities', 'program_id', 'programs', 'null'],
    ];

    public function up(): void
    {
        foreach (self::Keys as [$table, $column, $parent, $onDelete]) {
            if (! Schema::hasColumn($table, $column) || $this->hasForeignKey($table, $column)) {
                continue;
            }

            $orphans = DB::table($table)
                ->whereNotNull($column)
                ->whereNotExists(fn ($query) => $query->selectRaw('1')->from($parent)->whereColumn("{$parent}.id", "{$table}.{$column}"))
                ->count();

            if ($orphans > 0) {
                $message = "Foreign key {$table}.{$column} -> {$parent}.id skipped: {$orphans} orphan rows.";
                Log::warning($message);
                echo "  {$message}".PHP_EOL;

                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) use ($column, $parent, $onDelete): void {
                $key = $blueprint->foreign($column)->references('id')->on($parent);
                $onDelete === 'null' ? $key->nullOnDelete() : $key->restrictOnDelete();
            });
        }
    }

    public function down(): void
    {
        foreach (array_reverse(self::Keys) as [$table, $column]) {
            if (Schema::hasTable($table) && $this->hasForeignKey($table, $column)) {
                Schema::table($table, fn (Blueprint $blueprint) => $blueprint->dropForeign([$column]));
            }
        }
    }

    private function hasForeignKey(string $table, string $column): bool
    {
        return collect(Schema::getForeignKeys($table))->contains(fn (array $key): bool => $key['columns'] === [$column]);
    }
};
