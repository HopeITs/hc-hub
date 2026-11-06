<?php

declare(strict_types=1);

namespace Tests\PHPStan;

use App\Livewire\Concerns\Strategies\DeletesStrategy;
use Livewire\Component;

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
