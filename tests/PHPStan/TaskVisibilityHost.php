<?php

declare(strict_types=1);

namespace Tests\PHPStan;

use Livewire\Component;
use Modules\Tracking\Livewire\Concerns\ManagesTaskVisibility;

/**
 * Stands in for tasks/index.
 *
 * The real pages are single-file Livewire components inside Blade, which
 * PHPStan cannot read, so without this class the trait is never analysed.
 * Never instantiated.
 */
abstract class TaskVisibilityHost extends Component
{
    use ManagesTaskVisibility;
}
