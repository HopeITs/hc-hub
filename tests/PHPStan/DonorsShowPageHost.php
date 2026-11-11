<?php

declare(strict_types=1);

namespace Tests\PHPStan;

use Livewire\Component;
use Modules\Tracking\Livewire\Concerns\Donors\ReadsDonorMoney;
use Modules\Tracking\Livewire\Concerns\ManagesAgreementForm;
use Modules\Tracking\Livewire\Concerns\ManagesDonorForm;
use Modules\Tracking\Livewire\Concerns\SwitchesFundingView;

/**
 * Stands in for donors/show, with every trait that page uses.
 *
 * The real page is a single-file Livewire component inside Blade, which
 * PHPStan cannot read, so without this class the donor money trait is never
 * analysed. It declares the page's own members the traits read. Never
 * instantiated.
 */
abstract class DonorsShowPageHost extends Component
{
    use ManagesAgreementForm;
    use ManagesDonorForm;
    use ReadsDonorMoney;
    use SwitchesFundingView;

    public int $donorId;
}
