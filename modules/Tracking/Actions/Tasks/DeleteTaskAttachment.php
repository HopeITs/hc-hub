<?php

declare(strict_types=1);

namespace Modules\Tracking\Actions\Tasks;

use App\Support\StoredFiles;
use Illuminate\Support\Facades\Gate;
use Modules\Tracking\Models\Task;
use Modules\Tracking\Models\TaskAttachment;

/**
 * Delete a task attachment's row and, for a real upload, its file on disk.
 */
class DeleteTaskAttachment
{
    public function handle(Task $task, TaskAttachment $file): void
    {
        Gate::authorize('manageFiles', $task);

        if ($file->original_name !== null) {
            StoredFiles::forget($file->disk, $file->path, 'task attachment', ['task_attachment_id' => $file->id]);
        }

        $file->delete();
    }
}
