<?php

declare(strict_types=1);

namespace Tests\PHPStan;

use App\Livewire\Concerns\Strategies\ManagesStrategyForm;
use Livewire\Component;

/**
 * Stands in for strategies/index and strategies/show, which share
 * the create / edit strategy dialog.
 *
 * The real pages are single-file Livewire components inside Blade, which
 * PHPStan cannot read, so without this class the trait is never analysed.
 * Never instantiated.
 */
abstract class StrategyFormHost extends Component
{
    use ManagesStrategyForm;
}
