<?php

declare(strict_types=1);

namespace Tests\PHPStan;

use Livewire\Component;
use Modules\Tracking\Livewire\Concerns\Strategies\DeletesStrategy;

/**
 * Stands in for strategies/index and strategies/show, which share
 * the delete-strategy confirmation.
 *
 * The real pages are single-file Livewire components inside Blade, which
 * PHPStan cannot read, so without this class the trait is never analysed.
 * Never instantiated.
 */
abstract class StrategyDeleteHost extends Component
{
    use DeletesStrategy;
}
