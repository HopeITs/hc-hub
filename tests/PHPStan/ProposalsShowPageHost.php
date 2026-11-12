<?php

declare(strict_types=1);

namespace Tests\PHPStan;

use App\Livewire\Concerns\PreviewsFiles;
use Livewire\Component;
use Livewire\WithFileUploads;
use Modules\Tracking\Livewire\Concerns\AddsQuickTask;
use Modules\Tracking\Livewire\Concerns\ChecksFinanceVisibility;
use Modules\Tracking\Livewire\Concerns\ManagesAgreementForm;
use Modules\Tracking\Livewire\Concerns\ManagesTransferForm;
use Modules\Tracking\Livewire\Concerns\Proposals\ChartsProposalChronology;
use Modules\Tracking\Livewire\Concerns\Proposals\ManagesGrantDates;
use Modules\Tracking\Livewire\Concerns\Proposals\ManagesProposalApproval;
use Modules\Tracking\Livewire\Concerns\Proposals\ManagesProposalFiles;
use Modules\Tracking\Livewire\Concerns\Proposals\ManagesProposalForm;
use Modules\Tracking\Livewire\Concerns\Proposals\ManagesProposalRecord;
use Modules\Tracking\Livewire\Concerns\Proposals\ManagesProposalReviewers;
use Modules\Tracking\Livewire\Concerns\Proposals\ManagesProposalStatus;
use Modules\Tracking\Livewire\Concerns\Proposals\ReadsProposalDelivery;
use Modules\Tracking\Livewire\Concerns\Proposals\ReadsProposalFunding;
use Modules\Tracking\Livewire\Concerns\Proposals\ReadsProposalHealth;
use Modules\Tracking\Livewire\Concerns\Proposals\ReadsProposalVitals;
use Modules\Tracking\Livewire\Concerns\SwitchesFundingView;

/**
 * Stands in for proposals/show, with every trait that page uses.
 *
 * The real page is a single-file Livewire component inside Blade, which
 * PHPStan cannot read, so without this class the proposal traits are never
 * analysed. It declares the page's own members the traits read. Never
 * instantiated.
 */
abstract class ProposalsShowPageHost extends Component
{
    use AddsQuickTask;
    use ChartsProposalChronology;
    use ChecksFinanceVisibility;
    use ManagesAgreementForm;
    use ManagesGrantDates;
    use ManagesProposalApproval;
    use ManagesProposalFiles;
    use ManagesProposalForm;
    use ManagesProposalRecord;
    use ManagesProposalReviewers;
    use ManagesProposalStatus;
    use ManagesTransferForm;
    use PreviewsFiles;
    use ReadsProposalDelivery;
    use ReadsProposalFunding;
    use ReadsProposalHealth;
    use ReadsProposalVitals;
    use SwitchesFundingView;
    use WithFileUploads;

    public int $proposalId;

    private function forgetReadings(): void {}
}
