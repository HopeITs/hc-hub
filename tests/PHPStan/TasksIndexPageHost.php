<?php

declare(strict_types=1);

namespace Tests\PHPStan;

use Livewire\Component;
use Livewire\WithPagination;
use Modules\Tracking\Livewire\Concerns\ManagesTaskVisibility;
use Modules\Tracking\Livewire\Concerns\NavigatesCalendarPeriods;
use Modules\Tracking\Livewire\Concerns\Tasks\ComposesInlineTasks;
use Modules\Tracking\Livewire\Concerns\Tasks\FiltersTaskDirectory;
use Modules\Tracking\Livewire\Concerns\Tasks\ManagesTaskDrawer;
use Modules\Tracking\Livewire\Concerns\Tasks\ProvidesTaskOptions;
use Modules\Tracking\Livewire\Concerns\Tasks\ReadsTaskViews;

/**
 * Stands in for tasks/index, with every trait that page uses.
 *
 * The real page is a single-file Livewire component inside Blade, which
 * PHPStan cannot read, so without this class the task traits are never
 * analysed. It declares the page's own members the traits read. Never
 * instantiated.
 */
abstract class TasksIndexPageHost extends Component
{
    use ComposesInlineTasks;
    use FiltersTaskDirectory;
    use ManagesTaskDrawer;
    use ManagesTaskVisibility;
    use NavigatesCalendarPeriods;
    use ProvidesTaskOptions;
    use ReadsTaskViews;
    use WithPagination;

    public string $month = '';

    public string $anchorDate = '';
}
