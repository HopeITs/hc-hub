<?php

declare(strict_types=1);

namespace Tests\PHPStan;

use Livewire\Component;
use Modules\Tracking\Livewire\Concerns\BuildsPortfolioGantt;

/**
 * Stands in for funding-agreements/show, the read-only portfolio Gantt.
 *
 * The real page is a single-file Livewire component inside Blade, which
 * PHPStan cannot read, so without this class the trait is never analysed.
 * Never instantiated.
 */
abstract class PortfolioGanttHost extends Component
{
    use BuildsPortfolioGantt;
}
