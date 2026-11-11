<?php

declare(strict_types=1);

namespace Modules\Tracking\Actions\Projects;

use App\Models\Media;
use App\Support\StoredFiles;
use Illuminate\Support\Facades\Gate;
use Modules\Tracking\Models\Project;

/**
 * Delete a project file's row and its underlying disk file.
 */
class DeleteProjectFile
{
    public function handle(Project $project, Media $file): void
    {
        Gate::authorize('update', $project);

        StoredFiles::forget($file->disk, $file->path, 'project file', ['media_id' => $file->id]);

        $file->delete();
    }
}
