<?php

declare(strict_types=1);

namespace Modules\Hr\Support\Recruitment;

use Illuminate\Support\Collection;
use Modules\Core\Models\User;
use Modules\Hr\Actions\Recruitment\RecruitmentHiringConversionService as Conversions;
use Modules\Hr\Enums\ContractType as ContractTypeEnum;
use Modules\Hr\Models\Contract;
use Modules\Hr\Models\Employee;
use Modules\Hr\Models\RecruitmentHiringConversion;
use Modules\Hr\Support\Employees\DigitalId;

/**
 * What is left to do for a person just hired from an accepted offer: the
 * guided flow after the conversion. The employee and the contract are made
 * by the conversion itself; this reads what is still open around them — a
 * login account, the ID card (which exists as soon as the employee has a
 * city and a current position with codes, so "issuing" it is making sure it
 * can be built) and the welcome message.
 *
 * The hire may be an existing employee taking a new position, often in
 * another program: no new employee exists then. The conversion adds the new
 * contract but does not end the old one, so this lists any other contract
 * still running and warns when the program changed, because the ID card's
 * link is built from the program and the old printed card stops resolving.
 *
 * It reads and links only. Nothing is created or ended here, and every link
 * points to a page that checks its own permission.
 */
final class OnboardingChecklist
{
    /**
     * @return list<array{key: string, done: bool, url: ?string, problem: ?string, params: array<string, string>, note: ?string}>
     */
    public function for(RecruitmentHiringConversion $conversion, ?User $viewer): array
    {
        $employee = $conversion->employee;

        if (! $conversion->isConverted() || $employee === null) {
            return [];
        }

        $employee->loadMissing('account:id,employee_id,email');

        $existing = ($conversion->employee_payload_json['mode'] ?? null) === Conversions::MODE_LINK;
        $editUrl = $viewer?->can('update', $employee) ? route('hr.employees.edit', ['employee' => $employee->id]) : null;
        $profileUrl = filled($employee->username) ? route('hr.employees.show', ['employee' => $employee->username]) : null;
        $newContract = $conversion->contract_id === null ? null : Contract::query()
            ->select(['id', 'position_id'])
            ->with('position:id,program_id,title_en,title_ar', 'position.program:id,name')
            ->find($conversion->contract_id);

        $steps = [
            $this->step($existing ? 'employee_existing' : 'employee', true, $profileUrl),
            $this->step('contract', $newContract !== null, $newContract === null ? null : route('hr.contracts.show', ['contract' => $newContract->id])),
        ];

        // A transfer or promotion: the old contract still runs next to the new one.
        foreach ($this->otherRunningContracts($employee, $conversion) as $other) {
            $steps[] = $this->step('previous', false, route('hr.contracts.show', ['contract' => $other->id]), params: [
                'position' => (string) $other->position?->title,
                'program' => (string) ($other->position?->program?->name ?? '—'),
            ]);
        }

        $steps[] = $this->step('account', $employee->account !== null, $employee->account !== null ? null : $editUrl);
        $steps[] = $this->cardStep($employee, $newContract, $this->latestEarlierContract($employee, $conversion), $editUrl, $profileUrl);
        // The message itself is picked with the template menu on the page.
        $steps[] = $this->step($existing ? 'announce' : 'welcome', false, null);

        return $steps;
    }

    /**
     * @param  array<string, string>  $params
     * @return array{key: string, done: bool, url: ?string, problem: ?string, params: array<string, string>, note: ?string}
     */
    private function step(string $key, bool $done, ?string $url, ?string $problem = null, array $params = [], ?string $note = null): array
    {
        return ['key' => $key, 'done' => $done, 'url' => $url, 'problem' => $problem, 'params' => $params, 'note' => $note];
    }

    /**
     * The employee's other running contracts: what a transfer leaves behind.
     *
     * @return Collection<int, Contract>
     */
    private function otherRunningContracts(Employee $employee, RecruitmentHiringConversion $conversion): Collection
    {
        return Contract::query()
            ->select(['id', 'employee_id', 'position_id', 'start_date'])
            ->activeNow()
            ->where('type', '!=', ContractTypeEnum::Draft->value)
            ->where('employee_id', $employee->id)
            ->when($conversion->contract_id, fn ($query, $id) => $query->whereKeyNot($id))
            ->with('position:id,program_id,title_en,title_ar', 'position.program:id,name')
            ->orderByDesc('start_date')
            ->get();
    }

    /**
     * The employee's most recent earlier contract, running or already ended
     * (the usual transfer ends the old one by hand first): the program the
     * old card was built from.
     */
    private function latestEarlierContract(Employee $employee, RecruitmentHiringConversion $conversion): ?Contract
    {
        return Contract::query()
            ->select(['id', 'employee_id', 'position_id', 'end_date'])
            ->where('employee_id', $employee->id)
            ->where('type', '!=', ContractTypeEnum::Draft->value)
            ->when($conversion->contract_id, fn ($query, $id) => $query->whereKeyNot($id))
            ->with('position:id,program_id', 'position.program:id,name')
            ->orderByDesc('end_date')
            ->orderByDesc('id')
            ->first();
    }

    /**
     * @return array{key: string, done: bool, url: ?string, problem: ?string, params: array<string, string>, note: ?string}
     */
    private function cardStep(Employee $employee, ?Contract $newContract, ?Contract $previous, ?string $editUrl, ?string $profileUrl): array
    {
        $reason = DigitalId::missingLinkReason($employee);
        $url = $reason === null ? DigitalId::url($employee) : null;

        // The program in the card's link changed: the old printed card no longer resolves.
        $note = null;
        $newProgram = $newContract?->position?->program;
        $oldProgram = $previous?->position?->program;

        if ($url !== null && $newProgram !== null && $oldProgram !== null && ! $newProgram->is($oldProgram)) {
            $note = __('hr_recruitment.applicant_ui.conversions.onboarding.card_changed', ['from' => (string) $oldProgram->name, 'to' => (string) $newProgram->name]);
        }

        return $this->step(
            'card',
            // A changed program is a task: the old printed card stops working.
            $url !== null && $note === null,
            $url !== null ? ($note !== null && $profileUrl !== null ? $profileUrl : $url) : $editUrl,
            $reason === null ? null : __($reason),
            note: $note,
        );
    }
}
