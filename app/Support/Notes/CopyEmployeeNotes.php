<?php

declare(strict_types=1);

namespace App\Support\Notes;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * One-off copy of the old HR-only `employee_notes` into the shared tables:
 * a `note` row becomes a private personal row in `notes`, a `task` row a line
 * in `user_todos`. The author is the user linked to the employee
 * (users.employee_id); a row whose employee has no user is skipped. Created
 * and updated dates are kept. Safe to run again: a row already copied (same
 * owner, text and created date) is not copied twice. employee_notes itself
 * is never touched.
 */
final class CopyEmployeeNotes
{
    /**
     * @return array{notes: int, todos: int, skipped: int} how many rows were copied, and how many left out
     */
    public function __invoke(): array
    {
        $result = ['notes' => 0, 'todos' => 0, 'skipped' => 0];

        if (! Schema::hasTable('employee_notes')) {
            return $result;
        }

        DB::table('employee_notes')->orderBy('id')->chunkById(200, function ($rows) use (&$result): void {
            $userIds = DB::table('users')
                ->whereIn('employee_id', $rows->pluck('employee_id')->unique())
                ->orderBy('id')
                ->pluck('id', 'employee_id');

            foreach ($rows as $row) {
                $userId = $userIds[$row->employee_id] ?? null;
                $content = trim((string) $row->content);

                if ($userId === null || $content === '' || ! in_array($row->type, ['note', 'task'], true)) {
                    $result['skipped']++;

                    continue;
                }

                if ($row->type === 'note') {
                    $this->copyNote($row, (int) $userId, $content) ? $result['notes']++ : $result['skipped']++;
                } else {
                    $this->copyTodo($row, (int) $userId, $content) ? $result['todos']++ : $result['skipped']++;
                }
            }
        });

        return $result;
    }

    private function copyNote(object $row, int $userId, string $content): bool
    {
        $exists = DB::table('notes')
            ->where('author_id', $userId)
            ->whereNull('subject_type')
            ->where('body', $content)
            ->where('created_at', $row->created_at)
            ->exists();

        if ($exists) {
            return false;
        }

        DB::table('notes')->insert([
            'author_id' => $userId,
            'subject_type' => null,
            'subject_id' => null,
            'body' => $content,
            'visibility' => 'private',
            'color' => $row->color,
            'created_at' => $row->created_at,
            'updated_at' => $row->updated_at,
        ]);

        return true;
    }

    private function copyTodo(object $row, int $userId, string $content): bool
    {
        $exists = DB::table('user_todos')
            ->where('user_id', $userId)
            ->where('title', $content)
            ->where('created_at', $row->created_at)
            ->exists();

        if ($exists) {
            return false;
        }

        DB::table('user_todos')->insert([
            'user_id' => $userId,
            'title' => mb_substr($content, 0, 200),
            'is_done' => (bool) $row->is_done,
            'done_at' => $row->is_done ? $row->updated_at : null,
            'sort_order' => (int) $row->order,
            'created_at' => $row->created_at,
            'updated_at' => $row->updated_at,
        ]);

        return true;
    }
}
