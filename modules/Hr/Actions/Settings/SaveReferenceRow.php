<?php

declare(strict_types=1);

namespace Modules\Hr\Actions\Settings;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Modules\Hr\Support\Settings\ReferenceTables;

/**
 * Create or edit one row of an HR reference table (ReferenceTables): the
 * fields, rules and uniqueness come from the table's definition, the right
 * to write from the model's policy (project-basics.create / .update).
 */
final class SaveReferenceRow
{
    /**
     * @param  array<string, mixed>  $input  field => typed value
     *
     * @throws ValidationException keyed by field name
     */
    public function handle(string $table, ?int $rowId, array $input): Model
    {
        $definition = ReferenceTables::get($table);
        $modelClass = $definition['model'];

        if ($rowId === null) {
            Gate::authorize('create', $modelClass);
        } else {
            Gate::authorize('update', $modelClass::query()->findOrFail($rowId));
        }

        $validated = $this->validate($definition, $rowId, $input);

        return DB::transaction(function () use ($modelClass, $rowId, $validated): Model {
            $row = $rowId === null ? new $modelClass : $modelClass::query()->lockForUpdate()->findOrFail($rowId);

            $row->fill($validated)->save();

            return $row;
        });
    }

    /**
     * @param  array<string, mixed>  $definition
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    private function validate(array $definition, ?int $rowId, array $input): array
    {
        $tableName = (new $definition['model'])->getTable();
        $data = [];
        $rules = [];
        $attributes = [];

        foreach ($definition['fields'] as $field => $spec) {
            $value = $input[$field] ?? null;
            $value = is_string($value) ? trim($value) : $value;
            $data[$field] = $value === '' ? null : $value;

            $fieldRules = $spec['rules'];

            if ($spec['type'] === 'select') {
                $fieldRules[] = Rule::in($spec['options'] ?? []);
            }

            if ($spec['unique'] ?? false) {
                $fieldRules[] = Rule::unique($tableName, $field)->ignore($rowId);
            }

            $rules[$field] = $fieldRules;
            $attributes[$field] = __('hr_settings.fields.'.$field);
        }

        $validated = Validator::make($data, $rules, [], $attributes)->validate();

        foreach ($definition['fields'] as $field => $spec) {
            if (($validated[$field] ?? null) === null) {
                continue;
            }

            $validated[$field] = match ($spec['type']) {
                'integer', 'select' => (int) $validated[$field],
                'decimal' => (float) $validated[$field],
                default => $validated[$field],
            };
        }

        return $validated;
    }
}
