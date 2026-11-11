<?php

declare(strict_types=1);

namespace Modules\Hr\Actions\Desk;

use Illuminate\Validation\ValidationException;
use Modules\Hr\Models\EmployeeNote;

/**
 * Writes to an employee's own notes (the Desk's "My HR notes" card): add a
 * note at the end, tick or untick one, or remove one. Every write is held to
 * the owner's rows, so another person's note id finds nothing.
 */
final class SaveEmployeeNote
{
    public const int MaxLength = 500;

    public const int MaxNotes = 100;

    public function add(int $employeeId, string $content, string $type = 'note'): EmployeeNote
    {
        $content = trim($content);

        if ($content === '' || mb_strlen($content) > self::MaxLength) {
            throw ValidationException::withMessages(['content' => __('hr_desk.cards.notes.invalid', ['max' => self::MaxLength])]);
        }

        if (EmployeeNote::query()->forEmployee($employeeId)->count() >= self::MaxNotes) {
            throw ValidationException::withMessages(['content' => __('hr_desk.cards.notes.full')]);
        }

        return EmployeeNote::query()->create([
            'employee_id' => $employeeId,
            'type' => in_array($type, ['note', 'task'], true) ? $type : 'note',
            'content' => $content,
            'is_done' => false,
            'order' => (int) EmployeeNote::query()->forEmployee($employeeId)->max('order') + 1,
            'color' => '#58595b',
        ]);
    }

    public function toggle(int $employeeId, int $noteId): void
    {
        $note = EmployeeNote::query()->forEmployee($employeeId)->findOrFail($noteId);
        $note->update(['is_done' => ! $note->is_done]);
    }

    public function remove(int $employeeId, int $noteId): void
    {
        EmployeeNote::query()->forEmployee($employeeId)->findOrFail($noteId)->delete();
    }
}
