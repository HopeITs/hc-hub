<?php

declare(strict_types=1);

namespace Tests\PHPStan;

use Livewire\Component;
use Livewire\WithPagination;
use Modules\Tracking\Livewire\Concerns\ChecksFinanceVisibility;
use Modules\Tracking\Livewire\Concerns\FiltersFinanceRegister;
use Modules\Tracking\Livewire\Concerns\Proposals\ChartsProposalPipeline;
use Modules\Tracking\Livewire\Concerns\Proposals\DeletesProposals;
use Modules\Tracking\Livewire\Concerns\Proposals\ExportsProposalRegister;
use Modules\Tracking\Livewire\Concerns\Proposals\ManagesProposalForm;
use Modules\Tracking\Livewire\Concerns\Proposals\ManagesProposalStatus;
use Modules\Tracking\Livewire\Concerns\Proposals\ReadsProposalBoard;
use Modules\Tracking\Livewire\Concerns\Proposals\ReadsProposalRegister;

/**
 * Stands in for proposals/index, with every trait that page uses.
 *
 * The real page is a single-file Livewire component inside Blade, which
 * PHPStan cannot read, so without this class the proposal register traits
 * are never analysed. It declares the page's own members the traits read.
 * Never instantiated.
 */
abstract class ProposalsIndexPageHost extends Component
{
    use ChartsProposalPipeline;
    use ChecksFinanceVisibility;
    use DeletesProposals;
    use ExportsProposalRegister;
    use FiltersFinanceRegister;
    use ManagesProposalForm;
    use ManagesProposalStatus;
    use ReadsProposalBoard;
    use ReadsProposalRegister;
    use WithPagination;

    /** @var list<string> */
    public const FLAGS = ['no_agreement', 'overdue', 'report_overdue'];

    public string $search = '';

    public string $status = '';

    public string $phase = '';

    public string $donor = '';

    public string $currency = '';

    public string $flag = '';

    public string $proposalKind = '';

    public string $forProject = '';

    private function forgetReadings(): void {}
}
