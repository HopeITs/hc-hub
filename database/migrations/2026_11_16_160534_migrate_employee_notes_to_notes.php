<?php

declare(strict_types=1);

use App\Support\Notes\CopyEmployeeNotes;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Copies the old HR-only employee_notes into notes (type note) and
     * user_todos (type task). employee_notes is kept for rollback.
     */
    public function up(): void
    {
        (new CopyEmployeeNotes)();
    }

    /**
     * Nothing to undo safely: the copies cannot be told from rows written
     * since, and employee_notes is still there to copy from again.
     */
    public function down(): void {}
};
