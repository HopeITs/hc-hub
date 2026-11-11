<?php

declare(strict_types=1);

namespace Tests\PHPStan;

use Livewire\Component;
use Modules\Tracking\Livewire\Concerns\Calendar\BuildsCalendarGrid;
use Modules\Tracking\Livewire\Concerns\Calendar\FiltersCalendarTypes;
use Modules\Tracking\Livewire\Concerns\Calendar\FindsFocusedTypeDates;
use Modules\Tracking\Livewire\Concerns\Calendar\ManagesCalendarMilestones;
use Modules\Tracking\Livewire\Concerns\Calendar\ManagesCalendarTasks;
use Modules\Tracking\Livewire\Concerns\Calendar\PresentsCalendarItems;
use Modules\Tracking\Livewire\Concerns\Calendar\ScopesCalendarPeople;
use Modules\Tracking\Livewire\Concerns\Calendar\ShowsCalendarDay;
use Modules\Tracking\Livewire\Concerns\ChecksFinanceVisibility;
use Modules\Tracking\Livewire\Concerns\NavigatesCalendarPeriods;

/**
 * Stands in for calendar/index, with every trait that page uses.
 *
 * The real page is a single-file Livewire component inside Blade, which
 * PHPStan cannot read, so without this class the calendar traits are never
 * analysed. It declares the page's own members the traits read. Never
 * instantiated.
 */
abstract class CalendarIndexPageHost extends Component
{
    use BuildsCalendarGrid;
    use ChecksFinanceVisibility;
    use FiltersCalendarTypes;
    use FindsFocusedTypeDates;
    use ManagesCalendarMilestones;
    use ManagesCalendarTasks;
    use NavigatesCalendarPeriods;
    use PresentsCalendarItems;
    use ScopesCalendarPeople;
    use ShowsCalendarDay;

    public string $month = '';

    public string $anchorDate = '';

    public string $viewMode = 'month';

    /** @var array<int, string> */
    public array $filterEmployeeIds = [];
}
