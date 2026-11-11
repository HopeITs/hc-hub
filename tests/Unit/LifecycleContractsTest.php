<?php

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Modules\Tracking\Actions\FundingAgreements\SaveAgreementInstallments;
use Modules\Tracking\Actions\Projects\ActivateProject;
use Modules\Tracking\Actions\Projects\ApproveProjectFromProposal;
use Modules\Tracking\Actions\Projects\CloseProject;
use Modules\Tracking\Actions\Projects\ReopenProjectAsDraft;
use Modules\Tracking\Actions\Projects\SaveProject;
use Modules\Tracking\Actions\Proposals\ApproveProposal;
use Modules\Tracking\Actions\Proposals\CorrectProposalStatus;
use Modules\Tracking\Actions\Proposals\InviteDefaultReviewers;
use Modules\Tracking\Actions\Proposals\LinkProposalProject;
use Modules\Tracking\Actions\Proposals\RecordFinalReportAcceptance;
use Modules\Tracking\Actions\Proposals\SaveProposal;
use Modules\Tracking\Actions\Proposals\TransitionProposal;
use Modules\Tracking\Actions\Proposals\UnlinkProposalProject;
use Modules\Tracking\Enums\ReviewStage;
use Modules\Tracking\Events\FundingAgreementRecorded;
use Modules\Tracking\Events\ProjectActivated;
use Modules\Tracking\Events\ProjectPeriodChanged;
use Modules\Tracking\Events\ProposalStatusChanged;
use Modules\Tracking\Support\ApprovalResult;
use Modules\Tracking\Support\TransitionResult;

/*
| The drift alarm for docs/lifecycle/contracts.md §5 (events) and §6 (actions).
| Each expectation is the frozen signature rendered as "type $name = default".
| When this fails, the code and contracts.md disagree: fix the code, or ask
| Dev 1 to change the contract first.
*/

/**
 * Render a function's parameters as "type $name = default, ...".
 */
function renderedParameters(ReflectionFunctionAbstract $function): string
{
    return collect($function->getParameters())
        ->map(function (ReflectionParameter $parameter): string {
            $rendered = $parameter->getType().' $'.$parameter->getName();

            if ($parameter->isDefaultValueAvailable()) {
                $rendered .= ' = '.strtolower(var_export($parameter->getDefaultValue(), true));
            }

            return $rendered;
        })
        ->implode(', ');
}

it('freezes each domain event constructor (contracts.md §5)', function (string $event, string $parameters) {
    $class = new ReflectionClass($event);

    expect($class->isFinal())->toBeTrue()
        ->and($class->implementsInterface(ShouldDispatchAfterCommit::class))->toBeTrue()
        ->and(renderedParameters($class->getConstructor()))->toBe($parameters);

    foreach ($class->getConstructor()->getParameters() as $parameter) {
        expect($parameter->isPromoted())->toBeTrue()
            ->and($class->getProperty($parameter->getName())->isReadOnly())->toBeTrue();
    }
})->with([
    'ProposalStatusChanged' => [ProposalStatusChanged::class, 'int $proposalId, Modules\Tracking\Enums\ProposalStatus $from, Modules\Tracking\Enums\ProposalStatus $to, ?int $actorId, bool $isCorrection = false'],
    'ProjectActivated' => [ProjectActivated::class, 'int $projectId, ?int $proposalId, Carbon\CarbonImmutable $startDate, Carbon\CarbonImmutable $endDate, string $via'],
    'ProjectPeriodChanged' => [ProjectPeriodChanged::class, 'int $projectId, Carbon\CarbonImmutable $oldStart, Carbon\CarbonImmutable $oldEnd, Carbon\CarbonImmutable $newStart, Carbon\CarbonImmutable $newEnd'],
    'FundingAgreementRecorded' => [FundingAgreementRecorded::class, 'int $fundingAgreementId, int $proposalId, ?int $actorId'],
]);

it('freezes each result object (contracts.md §6)', function (string $result, string $parameters) {
    $class = new ReflectionClass($result);

    expect($class->isFinal())->toBeTrue()
        ->and($class->isReadOnly())->toBeTrue()
        ->and(renderedParameters($class->getConstructor()))->toBe($parameters);
})->with([
    'TransitionResult' => [TransitionResult::class, 'Modules\Tracking\Models\ProposalStatusNote $note, array $warnings'],
    'ApprovalResult' => [ApprovalResult::class, 'array $approvedProjectIds, array $waitingProjectIds, array $skippedProjectIds, array $warnings'],
]);

it('freezes each Team A action entry point (contracts.md §6)', function (string $action, string $parameters, string $returns) {
    $class = new ReflectionClass($action);
    $handle = $class->getMethod('handle');

    expect($class->isFinal())->toBeTrue()
        ->and(renderedParameters($handle))->toBe($parameters)
        ->and((string) $handle->getReturnType())->toBe($returns);
})->with([
    'TransitionProposal' => [TransitionProposal::class, 'Modules\Tracking\Models\Proposal $proposal, Modules\Tracking\Enums\ProposalStatus $to, Modules\Tracking\Enums\ProposalStatus $expectedFrom, array $input', TransitionResult::class],
    'CorrectProposalStatus' => [CorrectProposalStatus::class, 'Modules\Tracking\Models\Proposal $proposal, Modules\Tracking\Enums\ProposalStatus $to, Modules\Tracking\Enums\ProposalStatus $expectedFrom, string $reason', TransitionResult::class],
    'RecordFinalReportAcceptance' => [RecordFinalReportAcceptance::class, 'Modules\Tracking\Models\Proposal $proposal, Carbon\CarbonImmutable $acceptedOn', 'Modules\Tracking\Models\Proposal'],
    'SaveProposal' => [SaveProposal::class, '?Modules\Tracking\Models\Proposal $existing, array $data, Modules\Core\Models\User $actor', 'Modules\Tracking\Models\Proposal'],
    'LinkProposalProject' => [LinkProposalProject::class, 'Modules\Tracking\Models\Proposal $proposal, int $projectId, ?int $targetBeneficiaries = null', 'void'],
    'UnlinkProposalProject' => [UnlinkProposalProject::class, 'Modules\Tracking\Models\Proposal $proposal, int $projectId', 'void'],
    'InviteDefaultReviewers' => [InviteDefaultReviewers::class, 'Modules\Tracking\Models\Proposal $proposal, Modules\Tracking\Enums\ReviewStage $stage', 'void'],
    'ApproveProposal' => [ApproveProposal::class, 'Modules\Tracking\Models\Proposal $proposal, Modules\Tracking\Enums\ProposalStatus $expectedFrom, array $input', ApprovalResult::class],
    'ReopenProjectAsDraft' => [ReopenProjectAsDraft::class, 'Modules\Tracking\Models\Project $project', 'void'],
    'SaveProject' => [SaveProject::class, '?Modules\Tracking\Models\Project $existing, array $data, Modules\Core\Models\User $actor', 'Modules\Tracking\Models\Project'],
    'CloseProject' => [CloseProject::class, 'Modules\Tracking\Models\Project $project', 'void'],
    'ApproveProjectFromProposal' => [ApproveProjectFromProposal::class, 'Modules\Tracking\Models\Project $project, string $via', 'bool'],
    'ActivateProject' => [ActivateProject::class, 'Modules\Tracking\Models\Project $project, string $via', 'void'],
    'SaveAgreementInstallments' => [SaveAgreementInstallments::class, 'Modules\Tracking\Models\DonorProposal $agreement, array $installments', 'void'],
]);

it('freezes the review stages (contracts.md §9)', function () {
    expect(ReviewStage::values())->toBe(['concept_note', 'proposal']);
});
