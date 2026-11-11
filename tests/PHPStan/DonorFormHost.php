<?php

declare(strict_types=1);

namespace Tests\PHPStan;

use Livewire\Component;
use Modules\Tracking\Livewire\Concerns\ManagesDonorForm;

/**
 * Stands in for donors/index and donors/show.
 *
 * The real pages are single-file Livewire components inside Blade, which
 * PHPStan cannot read, so without this class the trait is never analysed.
 * Never instantiated.
 */
abstract class DonorFormHost extends Component
{
    use ManagesDonorForm;
}
