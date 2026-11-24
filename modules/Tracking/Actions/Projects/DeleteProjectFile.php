<?php

declare(strict_types=1);

namespace Modules\Tracking\Actions\Projects;

use App\Models\Media;
use App\Support\StoredFiles;
use Illuminate\Support\Facades\Gate;
use Modules\Tracking\Models\Project;
use Modules\Tracking\Support\Locations\ProjectLocationMirror;

/**
 * Delete a project file's row and its underlying disk file.
 */
class DeleteProjectFile
{
    public function __construct(private ProjectLocationMirror $mirror) {}

    public function handle(Project $project, Media $file): void
    {
        Gate::authorize('update', $project);

        $this->mirror->detachFile($project, $file);

        StoredFiles::forget($file->disk, $file->path, 'project file', ['media_id' => $file->id]);

        $file->delete();
    }
}
