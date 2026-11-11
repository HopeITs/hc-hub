<?php

declare(strict_types=1);

namespace Tests\PHPStan;

use Livewire\Component;
use Modules\Tracking\Livewire\Concerns\AnnualPlans\ReadsAnnualPlanSummary;
use Modules\Tracking\Livewire\Concerns\Results\FiltersResultsChain;

/**
 * Stands in for annual-plans/show, with every trait that page uses.
 *
 * The real page is a single-file Livewire component inside Blade, which
 * PHPStan cannot read, so without this class the annual-plan summary trait is
 * never analysed. It declares the page's own members the trait reads. Never
 * instantiated.
 */
abstract class AnnualPlansShowPageHost extends Component
{
    use FiltersResultsChain;
    use ReadsAnnualPlanSummary;

    public int $annualPlanId;
}
