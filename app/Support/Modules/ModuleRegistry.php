<?php

declare(strict_types=1);

namespace App\Support\Modules;

use App\Support\Modules\Contracts\DeskPanelSource;
use App\Support\Modules\Contracts\DestinationSource;

/**
 * What each module adds to the platform, registered from the module's own
 * ServiceProvider so app/ never names a module (D10). Today: sidebar pages
 * (Destinations) and Desk panels (Panels).
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
