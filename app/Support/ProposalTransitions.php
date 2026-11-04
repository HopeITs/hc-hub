<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\ProposalKind;
use App\Enums\ProposalStatus;

/**
 * The proposal transition table of contracts.md §2, and nothing else.
 *
 * Only the moves TransitionProposal may make are listed with their
 * requirement keys. `→ approved` belongs to ApproveProposal, `approved →
 * active` to ActivateProject (or, for payroll, ApproveProposal itself) and
 * every move out of a terminal status to CorrectProposalStatus, so none of
 * them appear in the maps.
 *
 * A hold is not a move (D-58, W-06): the proposal keeps its status and
 * carries `on_hold_since`, so no row leads in or out of `on hold`.
 *
 * A payroll proposal (D-55b, W-32) has its own, shorter table: it never sits
 * at a concept note or in a review. Its one `concept note` row is the hop
 * SaveProposal makes at creation, in the same transaction.
 */
final class ProposalTransitions
{
    /**
     * from => [to => requirement keys] for project proposals.
     *
     * @var array<string, array<string, list<string>>>
     */
    private const array Map = [
        'concept note' => [
            'concept note review' => ['linked_project', 'donor', 'planned_dates'],
            'proposal draft' => ['reason'],
        ],
        'concept note review' => [
            'concept note' => ['reason'],
            'concept note submitted' => ['effective_date'],
        ],
        'concept note submitted' => [
            'proposal draft' => ['effective_date'],
            'rejected' => ['reason_min_10', 'effective_date'],
        ],
        'proposal draft' => [
            'proposal review' => ['budget', 'planned_dates', 'target_beneficiaries'],
        ],
        'proposal review' => [
            'proposal draft' => ['reason'],
            'submitted' => ['effective_date', 'target_beneficiaries'],
        ],
        'submitted' => [
            'proposal draft' => ['reason'],
            'rejected' => ['reason_min_10', 'effective_date'],
        ],
        'active' => [
            'completed' => ['final_report', 'effective_date'],
        ],
    ];

    /**
     * from => [to => requirement keys] for payroll proposals (W-32).
     *
     * @var array<string, array<string, list<string>>>
     */
    private const array PayrollMap = [
        'concept note' => [
            'proposal draft' => ['reason'],
        ],
        'proposal draft' => [
            'submitted' => ['budget', 'planned_dates', 'narrative', 'effective_date'],
        ],
        'submitted' => [
            'proposal draft' => ['reason'],
            'rejected' => ['reason_min_10', 'effective_date'],
        ],
        'active' => [
            'completed' => ['final_report', 'effective_date'],
        ],
    ];

    /**
     * The statuses a payroll proposal can hold once created, in lifecycle
     * order (the correction targets draw from it).
     *
     * @var list<ProposalStatus>
     */
    private const array PayrollStatuses = [
        ProposalStatus::ProposalDraft,
        ProposalStatus::Submitted,
        ProposalStatus::Approved,
        ProposalStatus::Active,
        ProposalStatus::Completed,
        ProposalStatus::Rejected,
    ];

    /**
     * The TransitionProposal table of a kind, keyed by status value.
     *
     * @return array<string, array<string, list<string>>>
     */
    public static function map(ProposalKind $kind = ProposalKind::Project): array
    {
        return match ($kind) {
            ProposalKind::Project => self::Map,
            ProposalKind::Payroll => self::PayrollMap,
        };
    }

    /**
     * The statuses TransitionProposal may move a proposal to.
     *
     * @return list<ProposalStatus>
     */
    public static function targets(ProposalStatus $from, ProposalKind $kind = ProposalKind::Project): array
    {
        return array_map(
            fn (string $to): ProposalStatus => ProposalStatus::from($to),
            array_keys(self::map($kind)[$from->value] ?? []),
        );
    }

    /**
     * The requirement keys of one move, or null when TransitionProposal may
     * not make it.
     *
     * @return list<string>|null
     */
    public static function requirements(ProposalStatus $from, ProposalStatus $to, ProposalKind $kind = ProposalKind::Project): ?array
    {
        return self::map($kind)[$from->value][$to->value] ?? null;
    }

    /**
     * The statuses CorrectProposalStatus may set: from rejected or completed
     * to any other live status except approved. A payroll proposal is only
     * ever corrected to a status on its own path.
     *
     * @return list<ProposalStatus>
     */
    public static function correctionTargets(ProposalStatus $from, ProposalKind $kind = ProposalKind::Project): array
    {
        if (! $from->isTerminal()) {
            return [];
        }

        $candidates = match ($kind) {
            ProposalKind::Project => ProposalStatus::live(),
            ProposalKind::Payroll => self::PayrollStatuses,
        };

        return array_values(array_filter(
            $candidates,
            fn (ProposalStatus $to): bool => $to !== $from && $to !== ProposalStatus::Approved,
        ));
    }
}
