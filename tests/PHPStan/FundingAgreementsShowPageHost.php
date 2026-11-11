<?php

declare(strict_types=1);

namespace Tests\PHPStan;

use Livewire\Component;
use Modules\Tracking\Livewire\Concerns\FundingAgreements\ManagesDonorReports;
use Modules\Tracking\Livewire\Concerns\FundingAgreements\ReadsAgreementAttention;
use Modules\Tracking\Livewire\Concerns\FundingAgreements\ReadsAgreementConnections;
use Modules\Tracking\Livewire\Concerns\FundingAgreements\ReadsAgreementMoney;

/**
 * Stands in for funding-agreements/show, with every trait that page gained
 * when it was split (its shared agreement, tranche, Gantt and view traits are
 * analysed through their own hosts).
 *
 * The real page is a single-file Livewire component inside Blade, which
 * PHPStan cannot read, so without this class the agreement traits are never
 * analysed. It declares the page's own members the traits read. Never
 * instantiated.
 */
abstract class FundingAgreementsShowPageHost extends Component
{
    use ManagesDonorReports;
    use ReadsAgreementAttention;
    use ReadsAgreementConnections;
    use ReadsAgreementMoney;

    public int $agreementId;
}
