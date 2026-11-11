<?php

declare(strict_types=1);

namespace Tests\PHPStan;

use Livewire\Component;
use Livewire\WithPagination;
use Modules\Tracking\Livewire\Concerns\FiltersByStrategy;
use Modules\Tracking\Livewire\Concerns\Indicators\BuildsIndicatorRows;
use Modules\Tracking\Livewire\Concerns\Indicators\InspectsIndicatorChain;
use Modules\Tracking\Livewire\Concerns\Indicators\ManagesIndicatorForm;
use Modules\Tracking\Livewire\Concerns\Indicators\ManagesIndicatorReadings;
use Modules\Tracking\Livewire\Concerns\Indicators\PresentsIndicators;
use Modules\Tracking\Livewire\Concerns\Indicators\SummarisesIndicators;

/**
 * Stands in for indicators/index, with every trait that page uses.
 *
 * The real page is a single-file Livewire component inside Blade, which
 * PHPStan cannot read, so without this class the indicator traits are never
 * analysed. It declares the page's own members the traits read. Never
 * instantiated.
 */
abstract class IndicatorsIndexPageHost extends Component
{
    use BuildsIndicatorRows;
    use FiltersByStrategy;
    use InspectsIndicatorChain;
    use ManagesIndicatorForm;
    use ManagesIndicatorReadings;
    use PresentsIndicators;
    use SummarisesIndicators;
    use WithPagination;

    public string $search = '';

    public string $result = '';

    public string $measure = '';

    public string $program = '';

    public string $rag = '';

    public string $focus = '';

    public string $view = 'cards';
}
