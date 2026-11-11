<?php

declare(strict_types=1);

namespace Tests\PHPStan;

use Livewire\Component;
use Livewire\WithPagination;
use Modules\Tracking\Livewire\Concerns\AnnualPlans\BuildsPlanMatrix;
use Modules\Tracking\Livewire\Concerns\AnnualPlans\FiltersPlanMatrix;
use Modules\Tracking\Livewire\Concerns\AnnualPlans\ManagesAnnualPlanForm;

/**
 * Stands in for annual-plans/index, with every trait that page uses.
 *
 * The real page is a single-file Livewire component inside Blade, which
 * PHPStan cannot read, so without this class the annual-plan traits are
 * never analysed. It declares the page's own members the traits read. Never
 * instantiated.
 */
abstract class AnnualPlansIndexPageHost extends Component
{
    use BuildsPlanMatrix;
    use FiltersPlanMatrix;
    use ManagesAnnualPlanForm;
    use WithPagination;

    /** @var array<int, int> */
    public array $years = [];

    public string $manager = '';

    public string $programSearch = '';

    public string $focusYear = '';
}
