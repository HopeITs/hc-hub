<?php

declare(strict_types=1);

namespace Modules\Tracking\Actions\Tasks;

use App\Support\StoredFiles;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Modules\Tracking\Models\Task;
use Modules\Tracking\Models\TaskAttachment;

/**
 * Attach a file to a task, or rename, redate and optionally replace one.
 */
class SaveTaskAttachment
{
    /**
     * @param  array<string, mixed>  $validated  keyed by the file form's property names
     */
    public function handle(Task $task, ?TaskAttachment $existing, ?UploadedFile $upload, array $validated): TaskAttachment
    {
        Gate::authorize('manageFiles', $task);

        $file = $existing ?? new TaskAttachment(['task_id' => $task->id]);

        StoredFiles::save(
            $file,
            $upload,
            'task-attachments/'.$task->id,
            (string) ($validated['fileDisplayName'] ?? ''),
            ['date' => $validated['fileDate']],
            [],
            'task attachment',
        );

        return $file;
    }
}
