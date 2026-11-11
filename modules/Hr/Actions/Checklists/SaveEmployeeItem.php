<?php

declare(strict_types=1);

namespace Modules\Hr\Actions\Checklists;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Modules\Hr\Models\Employee;
use Modules\Hr\Models\EmployeeItem;

/**
 * Add or change one personal item on an employee's record (the old
 * ItemForm::save, one row at a time). An existing row is found through the
 * employee, so an id from another person's record matches nothing.
 */
final class SaveEmployeeItem
{
    /**
     * @param  array<string, mixed>  $input  item, brand, model, serial_number, status, notes
     *
     * @throws ValidationException
     */
    public function handle(Employee $employee, ?int $itemId, array $input): EmployeeItem
    {
        Gate::authorize('manage', [EmployeeItem::class, $employee]);

        $trimmed = array_map(fn (mixed $value): mixed => is_string($value) && trim($value) === '' ? null : (is_string($value) ? trim($value) : $value), $input);

        $validated = Validator::make($trimmed, [
            'item' => ['required', 'string', 'max:255'],
            'brand' => ['nullable', 'string', 'max:255'],
            'model' => ['nullable', 'string', 'max:255'],
            'serial_number' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ])->validate();

        $item = $itemId === null
            ? new EmployeeItem(['employee_id' => $employee->id])
            : EmployeeItem::query()->where('employee_id', $employee->id)->findOrFail($itemId);

        $item->fill($validated)->save();

        return $item;
    }
}
