<?php

declare(strict_types=1);

namespace Tests\PHPStan;

use Livewire\Component;
use Modules\Tracking\Livewire\Concerns\SwitchesFundingView;

/**
 * Stands in for donors/show, funding-agreements/show and proposals/show.
 *
 * The real pages are single-file Livewire components inside Blade, which
 * PHPStan cannot read, so without this class the trait is never analysed.
 * Never instantiated.
 */
abstract class FundingViewHost extends Component
{
    use SwitchesFundingView;
}
