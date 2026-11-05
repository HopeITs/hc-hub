<?php

declare(strict_types=1);

namespace App\Livewire\Concerns\Proposals;

use App\Actions\Proposals\ClearProposalOnHold;
use App\Actions\Proposals\SetProposalOnHold;
use App\Enums\ProposalStatus;
use App\Models\Proposal;
use Flux\Flux;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;

/**
 * The "Put on hold" and "Take off hold" dialogs of proposals/show (W-06, D-58).
 *
 * A hold is a flag, not a move, so it has its own two actions rather than an
 * entry in the transition picker. The status the page saw when the dialog
 * opened travels as the expected status, so a proposal someone else moved
 * meanwhile is refused and nothing is written.
 *
 * Neither this trait nor its views touch `on_hold_since`: only the two
 * actions write it (contracts.md §4, enforced by a source scan in
 * ProposalStatusWriteGuardTest). Used by a page that provides
 * forgetReadings().
 */
trait ManagesProposalHold
{
    public bool $showHoldModal = false;

    public bool $showOffHoldModal = false;

    #[Locked]
    public string $holdExpectedStatus = '';

    public string $holdReason = '';

    public string $holdRemindOn = '';

    public string $offHoldNote = '';

    public bool $holdStale = false;

    public function openHoldModal(): void
    {
        $proposal = $this->holdSubject();

        $this->resetHoldForm();
        $this->holdExpectedStatus = $proposal->status->value;
        $this->showHoldModal = true;
    }

    public function closeHoldModal(): void
    {
        $this->showHoldModal = false;
        $this->resetHoldForm();
    }

    /**
     * Put the proposal on hold; the action writes the flag, the reminder and
     * one `on_hold` note in a single transaction.
     */
    public function putOnHold(): void
    {
        $proposal = $this->holdSubject();

        $this->resetValidation();
        $this->holdStale = false;

        try {
            app(SetProposalOnHold::class)->handle($proposal, ProposalStatus::from($this->holdExpectedStatus), [
                'reason' => trim($this->holdReason),
                'remind_on' => $this->holdRemindOn,
            ]);
        } catch (ValidationException $exception) {
            $this->showHoldErrors($exception, ['reason' => 'holdReason', 'remind_on' => 'holdRemindOn']);

            return;
        }

        $this->closeHoldModal();
        $this->forgetReadings();

        Flux::toast(variant: 'success', text: __('proposals.put_on_hold_done'));
    }

    public function openOffHoldModal(): void
    {
        $proposal = $this->holdSubject();

        $this->resetHoldForm();
        $this->holdExpectedStatus = $proposal->status->value;
        $this->showOffHoldModal = true;
    }

    public function closeOffHoldModal(): void
    {
        $this->showOffHoldModal = false;
        $this->resetHoldForm();
    }

    /**
     * Take the proposal off hold. The note is optional: the action writes the
     * standard one when it is empty.
     */
    public function takeOffHold(): void
    {
        $proposal = $this->holdSubject();

        $this->resetValidation();
        $this->holdStale = false;

        try {
            app(ClearProposalOnHold::class)->handle($proposal, ProposalStatus::from($this->holdExpectedStatus), $this->offHoldNote);
        } catch (ValidationException $exception) {
            $this->showHoldErrors($exception, ['note' => 'offHoldNote']);

            return;
        }

        $this->closeOffHoldModal();
        $this->forgetReadings();

        Flux::toast(variant: 'success', text: __('proposals.taken_off_hold_done'));
    }

    /**
     * The proposal both actions act on, authorized before anything else.
     * Hiding a button is never the protection: the actions authorize again.
     */
    private function holdSubject(): Proposal
    {
        $proposal = Proposal::query()->findOrFail($this->proposalId);

        Gate::authorize('update', $proposal);

        return $proposal;
    }

    /**
     * Put a refusal on the dialog. A `status` key is either the stale-status
     * refusal, which raises the alert, or the "only while submitted" gate;
     * both read better above the fields than beside one.
     *
     * @param  array<string, string>  $fields  action error key => component property
     */
    private function showHoldErrors(ValidationException $exception, array $fields): void
    {
        $errors = $exception->errors();
        $this->holdStale = in_array(__('proposals.stale_status'), $errors['status'] ?? [], true);

        foreach ($errors as $key => $messages) {
            foreach ($messages as $message) {
                $this->addError($fields[$key] ?? 'holdGate', $message);
            }
        }
    }

    private function resetHoldForm(): void
    {
        $this->resetValidation();
        $this->holdExpectedStatus = '';
        $this->holdReason = '';
        $this->holdRemindOn = '';
        $this->offHoldNote = '';
        $this->holdStale = false;
    }
}
