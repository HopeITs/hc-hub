<?php

declare(strict_types=1);

namespace Tests\PHPStan;

use Livewire\Component;
use Livewire\WithPagination;
use Modules\Tracking\Livewire\Concerns\ChecksFinanceVisibility;
use Modules\Tracking\Livewire\Concerns\Donors\ChartsDonorPortfolio;
use Modules\Tracking\Livewire\Concerns\Donors\ExportsDonorRegister;
use Modules\Tracking\Livewire\Concerns\Donors\PresentsDonorRegister;
use Modules\Tracking\Livewire\Concerns\Donors\ReadsDonorRegister;
use Modules\Tracking\Livewire\Concerns\FiltersFinanceRegister;
use Modules\Tracking\Livewire\Concerns\ManagesDonorForm;

/**
 * Stands in for donors/index, with every trait that page uses.
 *
 * The real page is a single-file Livewire component inside Blade, which
 * PHPStan cannot read, so without this class the donors register traits are
 * never analysed. It declares the page's own members the traits read. Never
 * instantiated.
 */
abstract class DonorsIndexPageHost extends Component
{
    use ChartsDonorPortfolio;
    use ChecksFinanceVisibility;
    use ExportsDonorRegister;
    use FiltersFinanceRegister;
    use ManagesDonorForm;
    use PresentsDonorRegister;
    use ReadsDonorRegister;
    use WithPagination;

    public const UNCLASSIFIED = 'unclassified';

    public const NAMED_SEGMENTS = 7;

    /** @var list<string> */
    public const FLAGS = ['overdue', 'follow_up'];

    public string $search = '';

    public string $type = '';

    public string $relationship = '';

    public string $sort = 'name';

    public string $countryFilter = '';

    public string $currency = '';

    public string $flag = '';
}
