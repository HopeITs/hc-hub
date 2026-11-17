<?php

declare(strict_types=1);

namespace App\Support\Modules;

use App\Support\Modules\Contracts\DeskAgendaSource;
use App\Support\Modules\Contracts\DeskPanelSource;
use App\Support\Modules\Contracts\DestinationSource;
use App\Support\Modules\Contracts\EmployeeTabSource;
use App\Support\Modules\Contracts\SubjectLinkSource;

/**
 * What each module adds to the platform, registered from the module's own
 * ServiceProvider so app/ never names a module (D10). Today: sidebar pages
 * (Destinations), Desk panels (Panels), Desk agenda rows (ReadsDeskAgenda),
 * record links (SubjectLinks) and employee profile tabs.
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
