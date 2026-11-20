<?php

declare(strict_types=1);

namespace App\Support\Modules;

use App\Support\Modules\Contracts\CalendarLayerSource;
use App\Support\Modules\Contracts\DeskAgendaSource;
use App\Support\Modules\Contracts\DeskPanelSource;
use App\Support\Modules\Contracts\DestinationSource;
use App\Support\Modules\Contracts\EmployeeTabSource;
use App\Support\Modules\Contracts\MetricSource;
use App\Support\Modules\Contracts\ScheduleSource;
use App\Support\Modules\Contracts\SearchSource;
use App\Support\Modules\Contracts\SubjectLinkSource;
use App\Support\Modules\Contracts\TimelineSource;
use App\Support\Modules\Contracts\WorkItemSource;

/**
 * What each module adds to the platform, registered from the module's own
 * ServiceProvider so app/ never names a module (D10). Today: sidebar pages
 * (Destinations), Desk panels (Panels), Desk agenda rows (ReadsDeskAgenda),
 * record links (SubjectLinks), employee profile tabs, calendar layers and
 * the sources of an employee's timeline.
 *
 * A singleton filled once at boot; it holds class names only, and every read
 * resolves the source from the container, so nothing a request computes
 * outlives it (Destinations and Panels keep their own per-request memo).
 */
final class ModuleRegistry
{
    /**
     * @var list<class-string<DestinationSource>>
     */
    private array $destinations = [];

    /**
     * @var list<class-string<DeskPanelSource>>
     */
    private array $deskPanels = [];

    /**
     * @var list<class-string<DeskAgendaSource>>
     */
    private array $deskAgenda = [];

    /**
     * @var list<class-string<SubjectLinkSource>>
     */
    private array $subjectLinks = [];

    /**
     * @var list<class-string<EmployeeTabSource>>
     */
    private array $employeeTabs = [];

    /**
     * @var list<class-string<CalendarLayerSource>>
     */
    private array $calendarLayers = [];

    /**
     * @var list<class-string<TimelineSource>>
     */
    private array $timeline = [];

    /**
     * @var list<class-string<SearchSource>>
     */
    private array $search = [];

    /**
     * @var list<class-string<WorkItemSource>>
     */
    private array $workItems = [];

    /**
     * @var list<class-string<ScheduleSource>>
     */
    private array $schedule = [];

    /**
     * @param  class-string<ScheduleSource>  $source
     */
    public function addSchedule(string $source): void
    {
        if (! in_array($source, $this->schedule, true)) {
            $this->schedule[] = $source;
        }
    }

    /**
     * The background jobs of the platform and of each module, in registration order.
     *
     * @return list<ScheduleSource>
     */
    public function scheduleSources(): array
    {
        return array_map(fn (string $source): ScheduleSource => app($source), $this->schedule);
    }

    /**
     * @param  class-string<WorkItemSource>  $source
     */
    public function addWorkItems(string $source): void
    {
        if (! in_array($source, $this->workItems, true)) {
            $this->workItems[] = $source;
        }
    }

    /**
     * The places where work waits on a person, in registration order.
     *
     * @return list<WorkItemSource>
     */
    public function workItemSources(): array
    {
        return array_map(fn (string $source): WorkItemSource => app($source), $this->workItems);
    }

    /**
     * @var list<class-string<MetricSource>>
     */
    private array $metrics = [];

    /**
     * @param  class-string<MetricSource>  $source
     */
    public function addMetrics(string $source): void
    {
        if (! in_array($source, $this->metrics, true)) {
            $this->metrics[] = $source;
        }
    }

    /**
     * The organisation's numbers each module defines (App\Support\Metrics\OrgMetrics), in registration order.
     *
     * @return list<MetricSource>
     */
    public function metricSources(): array
    {
        return array_map(fn (string $source): MetricSource => app($source), $this->metrics);
    }

    /**
     * @param  class-string<SearchSource>  $source
     */
    public function addSearch(string $source): void
    {
        if (! in_array($source, $this->search, true)) {
            $this->search[] = $source;
        }
    }

    /**
     * The records modules let the Ask bar find, in registration order.
     *
     * @return list<SearchSource>
     */
    public function searchSources(): array
    {
        return array_map(fn (string $source): SearchSource => app($source), $this->search);
    }

    /**
     * @param  class-string<TimelineSource>  $source
     */
    public function addTimeline(string $source): void
    {
        if (! in_array($source, $this->timeline, true)) {
            $this->timeline[] = $source;
        }
    }

    /**
     * What modules add to an employee's timeline, in registration order.
     *
     * @return list<TimelineSource>
     */
    public function timelineSources(): array
    {
        return array_map(fn (string $source): TimelineSource => app($source), $this->timeline);
    }

    /**
     * @param  class-string<CalendarLayerSource>  $source
     */
    public function addCalendarLayer(string $source): void
    {
        if (! in_array($source, $this->calendarLayers, true)) {
            $this->calendarLayers[] = $source;
        }
    }

    /**
     * The layers modules add to the calendar, in registration order.
     *
     * @return list<CalendarLayerSource>
     */
    public function calendarLayerSources(): array
    {
        return array_map(fn (string $source): CalendarLayerSource => app($source), $this->calendarLayers);
    }

    /**
     * @param  class-string<EmployeeTabSource>  $source
     */
    public function addEmployeeTabs(string $source): void
    {
        if (! in_array($source, $this->employeeTabs, true)) {
            $this->employeeTabs[] = $source;
        }
    }

    /**
     * The tabs other modules add to an employee profile, in registration order.
     *
     * @return list<EmployeeTabSource>
     */
    public function employeeTabSources(): array
    {
        return array_map(fn (string $source): EmployeeTabSource => app($source), $this->employeeTabs);
    }

    /**
     * @param  class-string<DeskAgendaSource>  $source
     */
    public function addDeskAgenda(string $source): void
    {
        if (! in_array($source, $this->deskAgenda, true)) {
            $this->deskAgenda[] = $source;
        }
    }

    /**
     * @param  class-string<SubjectLinkSource>  $source
     */
    public function addSubjectLinks(string $source): void
    {
        if (! in_array($source, $this->subjectLinks, true)) {
            $this->subjectLinks[] = $source;
        }
    }

    /**
     * @return list<DeskAgendaSource>
     */
    public function deskAgendaSources(): array
    {
        return array_map(fn (string $source): DeskAgendaSource => app($source), $this->deskAgenda);
    }

    /**
     * The module whose records carry this morph type, if any.
     */
    public function subjectLinkSourceFor(string $type): ?SubjectLinkSource
    {
        foreach ($this->subjectLinks as $class) {
            $source = app($class);

            if ($source->handles($type)) {
                return $source;
            }
        }

        return null;
    }

    /**
     * @param  class-string<DestinationSource>  $source
     */
    public function addDestinations(string $source): void
    {
        if (! in_array($source, $this->destinations, true)) {
            $this->destinations[] = $source;
        }
    }

    /**
     * @param  class-string<DeskPanelSource>  $source
     */
    public function addDeskPanels(string $source): void
    {
        if (! in_array($source, $this->deskPanels, true)) {
            $this->deskPanels[] = $source;
        }
    }

    /**
     * @return list<DestinationSource>
     */
    public function destinationSources(): array
    {
        return array_map(fn (string $source): DestinationSource => app($source), $this->destinations);
    }

    /**
     * @return list<DeskPanelSource>
     */
    public function deskPanelSources(): array
    {
        return array_map(fn (string $source): DeskPanelSource => app($source), $this->deskPanels);
    }

    /**
     * The module that owns a Desk panel id, if any.
     */
    public function deskPanelSourceFor(string $id): ?DeskPanelSource
    {
        foreach ($this->deskPanelSources() as $source) {
            if ($source->owns($id)) {
                return $source;
            }
        }

        return null;
    }
}
