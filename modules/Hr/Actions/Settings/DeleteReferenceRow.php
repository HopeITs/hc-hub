<?php

declare(strict_types=1);

namespace Modules\Hr\Actions\Settings;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Modules\Hr\Support\Settings\ReferenceTables;

/**
 * Delete one row of an HR reference table, unless something still uses it
 * (a department with positions, a leave type with balances...). The old app
 * only guarded course categories and let the database refuse the rest.
 */
final class DeleteReferenceRow
{
    /**
     * @throws ValidationException when the row is in use
     */
    public function handle(string $table, int $rowId): void
    {
        $definition = ReferenceTables::get($table);
        $row = $definition['model']::query()->findOrFail($rowId);

        Gate::authorize('delete', $row);

        if (self::usage($table, $rowId) > 0) {
            throw ValidationException::withMessages(['row' => __('hr_settings.in_use')]);
        }

        try {
            DB::transaction(fn () => $row->delete());
        } catch (QueryException) {
            // A foreign key this list does not know about (recruitment, tracking).
            throw ValidationException::withMessages(['row' => __('hr_settings.in_use')]);
        }
    }

    /**
     * How many records point at this row.
     */
    public static function usage(string $table, int $rowId): int
    {
        $count = 0;

        foreach (ReferenceTables::get($table)['in_use'] as [$usingTable, $column]) {
            $count += DB::table($usingTable)->where($column, $rowId)->count();
        }

        return $count;
    }
}
