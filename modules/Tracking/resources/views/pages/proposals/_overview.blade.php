{{--
    The Overview view of the proposals page: where the money stands, the
    pipeline rail, the funding flow and one dossier per proposal. Included by
    ⚡index.blade.php, which defines $amount, $shortAmount, $canAgreements,
    $mutedText, $inkText, $receivedText and $hairline.
--}}
@php
    $lifecycle = \Modules\Tracking\Enums\ProposalStatus::lifecycle();
    // The flow names every matching proposal but the list below shows one page
    // of them: a proposal on this page is scrolled to, any other one opened.
    $pageProposalIds = $this->proposals->getCollection()->modelKeys();
    $proposalHref = fn (int $id): string => in_array($id, $pageProposalIds, true) ? '#proposal-'.$id : route('proposals.show', tap(new \Modules\Tracking\Models\Proposal, fn ($proposal) => $proposal->id = $id));
@endphp

@include('tracking::pages.proposals._overview-position')

@include('tracking::pages.proposals._overview-flow')

@include('tracking::pages.proposals._overview-dossiers')
