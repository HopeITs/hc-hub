<?php

declare(strict_types=1);

namespace Tests\PHPStan;

use Livewire\Component;
use Modules\Tracking\Livewire\Concerns\Results\BuildsResultsFramework;
use Modules\Tracking\Livewire\Concerns\Results\BuildsResultsTree;
use Modules\Tracking\Livewire\Concerns\Results\ManagesResultForm;
use Modules\Tracking\Livewire\Concerns\Results\SummarisesResults;

/**
 * Stands in for results/index, with every trait that page uses.
 *
 * The real page is a single-file Livewire component inside Blade, which
 * PHPStan cannot read, so without this class the results traits are never
 * analysed. It declares the page's own members the traits read. Never
 * instantiated.
 */
abstract class ResultsIndexPageHost extends Component
{
    use BuildsResultsFramework;
    use BuildsResultsTree;
    use ManagesResultForm;
    use SummarisesResults;

    public string $search = '';

    public string $program = '';

    public string $type = '';

    public string $focus = '';
}
