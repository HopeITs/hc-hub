<?php

declare(strict_types=1);

namespace Modules\Tracking\Actions\Projects;

use Illuminate\Support\Facades\Gate;
use Modules\Tracking\Enums\ProjectStatus;
use Modules\Tracking\Models\Project;

/**
 * Retire a project into the Archived state. Nothing is destroyed: this is how
 * a project leaves the active portfolio without losing its delivery record.
 */
class ArchiveProject
{
    public function handle(Project $project): void
    {
        Gate::authorize('delete', $project);

        $project->update(['status' => ProjectStatus::Archived]);
    }
}
