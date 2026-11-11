<?php

declare(strict_types=1);

namespace Tests\PHPStan;

use Livewire\Component;
use Livewire\WithFileUploads;
use Modules\Tracking\Livewire\Concerns\ChecksFinanceVisibility;
use Modules\Tracking\Livewire\Concerns\Projects\ArchivesProjects;
use Modules\Tracking\Livewire\Concerns\Projects\ManagesProjectAssignment;
use Modules\Tracking\Livewire\Concerns\Projects\ManagesProjectFiles;
use Modules\Tracking\Livewire\Concerns\Projects\ManagesProjectForm;
use Modules\Tracking\Livewire\Concerns\Projects\ManagesReportTemplate;
use Modules\Tracking\Livewire\Concerns\Projects\ReadsProjectDelivery;
use Modules\Tracking\Livewire\Concerns\Projects\ReadsProjectFunding;
use Modules\Tracking\Livewire\Concerns\Projects\ReadsProjectStory;

/**
 * Stands in for projects/show, with every trait that page gained when it was
 * split.
 *
 * The real page is a single-file Livewire component inside Blade, which
 * PHPStan cannot read, so without this class the project traits are never
 * analysed. It declares the page's own members the traits read. Never
 * instantiated.
 */
abstract class ProjectsShowPageHost extends Component
{
    use ArchivesProjects;
    use ChecksFinanceVisibility;
    use ManagesProjectAssignment, ManagesProjectForm {
        ManagesProjectAssignment::refreshFromInbox insteadof ManagesProjectForm;
    }
    use ManagesProjectFiles;
    use ManagesReportTemplate;
    use ReadsProjectDelivery;
    use ReadsProjectFunding;
    use ReadsProjectStory;
    use WithFileUploads;

    public int $projectId;

    /** The page re-reads the project and its story here (it has no pager). */
    abstract protected function resetPage(): void;
}
