<?php

declare(strict_types=1);

namespace Tests\PHPStan;

use Livewire\Component;
use Modules\Tracking\Livewire\Concerns\Programs\LinksProgram;
use Modules\Tracking\Livewire\Concerns\Programs\ManagesProgramTasks;
use Modules\Tracking\Livewire\Concerns\Programs\ReadsProgramDelivery;
use Modules\Tracking\Livewire\Concerns\Programs\TracksProgramHealth;
use Modules\Tracking\Livewire\Concerns\Results\FiltersResultsChain;

/**
 * Stands in for programs/show, with the trait that page gained when it was
 * split.
 *
 * The real page is a single-file Livewire component inside Blade, which
 * PHPStan cannot read, so without this class the trait is never analysed. It
 * declares the page's own members the trait reads. Never instantiated.
 */
abstract class ProgramsShowPageHost extends Component
{
    use FiltersResultsChain;
    use LinksProgram;
    use ManagesProgramTasks;
    use ReadsProgramDelivery;
    use TracksProgramHealth;

    public int $programId;
}
