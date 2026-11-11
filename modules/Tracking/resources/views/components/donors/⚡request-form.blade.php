<?php

use Modules\Tracking\Livewire\Concerns\ManagesDonorForm;
use Modules\Tracking\Models\Donor;
use Flux\Flux;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\On;
use Livewire\Component;

/*
 * "Add donor" from the proposal form (W-04, D-57): the shared donor form in
 * a nested flow. A donor recorded by someone who cannot approve donors is a
 * pending request; the new donor is handed back to the proposal form, which
 * selects it and shows the pending banner. Lives beside the proposal modal
 * rather than inside it so the two forms never share properties.
 */
new class extends Component {
    use ManagesDonorForm;

    #[On('open-donor-request')]
    public function openRequest(): void
    {
        Gate::authorize('donors.create');

        $this->openCreateModal();
    }

    protected function donorSaved(Donor $donor, bool $isCreate): void
    {
        $this->dispatch('donor-requested', donorId: $donor->id);

        Flux::toast(
            variant: 'success',
            text: $donor->isPending() ? __('donors.donor_request_pending') : __('donors.donor_created'),
        );
    }
};
?>

<div data-donor-request-form>
    <x-tracking::donors.form-modal :open="$showDonorModal" :editing="false" :type-options="$this->donorTypeOptions" />
</div>
