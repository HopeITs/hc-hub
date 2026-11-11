<?php

use Flux\Flux;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Modules\Hr\Actions\Leave\DeleteLeaveRequest;
use Modules\Hr\Enums\LeaveRequestStatus;
use Modules\Hr\Livewire\Concerns\Leave\ManagesLeaveRequestForm;
use Modules\Hr\Models\Contract;
use Modules\Hr\Models\LeaveBalance;
use Modules\Hr\Models\LeaveRequest;
use Modules\Hr\Support\Leave\LeaveContext;
use Modules\Hr\Support\Leave\LeaveEligibility;
use Modules\Hr\Support\Leave\LeaveRequestPricing;

/*
 * My leaves: the signed-in employee's balances on their current contract and
 * their requests, newest first, by status. A pending request can be edited
 * or withdrawn. ?new=1 (the team calendar's "Request leave") opens the form.
 */
new #[Layout('layouts::desk'), Title('hr_leave.titles.mine')] class extends Component {
    use ManagesLeaveRequestForm;
    use WithPagination;

    public const TABS = ['all', 'pending', 'accepted', 'rejected'];

    #[Url(except: 'all')]
    public string $tab = 'all';

    public bool $showWithdrawModal = false;

    #[Locked]
    public ?int $withdrawingId = null;

    public function mount(): void
    {
        if (! in_array($this->tab, self::TABS, true)) {
            $this->tab = 'all';
        }

        if (request()->boolean('new')) {
            $this->openRequestForm();
        }
    }

    public function updatedTab(): void
    {
        if (! in_array($this->tab, self::TABS, true)) {
            $this->tab = 'all';
        }

        $this->resetPage();
    }

    #[Computed]
    public function contract(): ?Contract
    {
        $employeeId = Auth::user()?->employee_id;

        return $employeeId === null ? null : LeaveContext::contractOf((int) $employeeId);
    }

    /** @return LengthAwarePaginator<int, LeaveRequest> */
    #[Computed]
    public function requests(): LengthAwarePaginator
    {
        $employeeId = Auth::user()?->employee_id;

        $requests = LeaveRequest::query()
            ->when($employeeId === null, fn ($query) => $query->whereRaw('1 = 0'))
            ->when($employeeId !== null, fn ($query) => $query->ofEmployee((int) $employeeId))
            ->when($this->tab !== 'all', fn ($query) => $query->withStatus(match ($this->tab) {
                'accepted' => LeaveRequestStatus::Accepted->value,
                'rejected' => LeaveRequestStatus::Rejected->value,
                default => LeaveRequestStatus::Pending->value,
            }))
            ->orderByDesc('start_at')
            ->orderByDesc('id')
            ->paginate(20);

        LeaveContext::load($requests->getCollection());

        return $requests;
    }

    public function confirmWithdraw(int $leaveRequestId): void
    {
        Gate::authorize('delete', LeaveContext::request($leaveRequestId));

        $this->withdrawingId = $leaveRequestId;
        $this->showWithdrawModal = true;
    }

    public function withdraw(): void
    {
        if ($this->withdrawingId === null) {
            return;
        }

        $id = $this->withdrawingId;
        $this->cancelWithdraw();

        app(DeleteLeaveRequest::class)->handle(Auth::user(), $id);
        $this->afterRequestSaved();

        Flux::toast(variant: 'success', text: __('hr_leave.ui.withdrawn'));
    }

    public function cancelWithdraw(): void
    {
        $this->showWithdrawModal = false;
        $this->withdrawingId = null;
    }

    protected function afterRequestSaved(): void
    {
        unset($this->requests, $this->contract);
    }
};
?>

@php
    $contract = $this->contract;
    $requests = $this->requests;
    $user = auth()->user();
    $eligibility = app(\Modules\Hr\Support\Leave\LeaveEligibility::class);
    $perDay = $contract === null ? null : app(\Modules\Hr\Support\Leave\LeaveRequestPricing::class)->hoursPerDayFor($contract);
    $muted = 'text-secondary-medium dark:text-ink-muted';
    $tabs = collect($this::TABS)->map(fn ($key) => ['key' => $key, 'label' => __('hr_leave.ui.tabs.'.$key)])->all();
@endphp

<x-desk.register class="@container" heading-id="hr-my-leaves-heading" :title="__('hr_leave.titles.mine')" :count="$requests->total()" data-hr-my-leaves>
    <x-slot:actions>
        <x-ui.button tone="secondary" icon="calendar-days" :href="route('hr.leave.team-calendar')" wire:navigate>{{ __('hr_leave.ui.team_calendar') }}</x-ui.button>
        @if ($this->canRequestForSelf())
            <x-ui.button tone="primary" icon="plus" wire:click="openRequestForm" data-test="new-leave-request" data-quick-create>{{ __('hr_leave.ui.new_request') }}</x-ui.button>
        @endif
    </x-slot:actions>

    @if ($user->employee_id === null)
        <x-ui.empty-state icon="user" :heading="__('hr_leave.ui.no_employee_heading')" :body="__('hr_leave.ui.no_employee_body')" />
    @else
        <section class="mb-6" aria-labelledby="hr-my-balances-heading" data-my-balances>
            <h2 id="hr-my-balances-heading" class="type-label mb-2 {{ $muted }}">{{ __('hr_leave.ui.my_balances') }}</h2>
            @if ($contract === null || $contract->leaveBalances->isEmpty())
                <p class="text-sm {{ $muted }}">{{ __('hr_leave.ui.no_balances') }}</p>
            @else
                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($contract->leaveBalances->sortBy('type_id') as $balance)
                        <div class="rounded-xl border border-secondary-soft bg-white p-4 dark:border-line dark:bg-surface-1" wire:key="balance-{{ $balance->id }}" data-balance="{{ $balance->id }}">
                            <p class="type-label {{ $muted }}">{{ $balance->leaveType?->name }}</p>
                            <p class="mt-1 text-lg font-semibold">{{ \Modules\Hr\Support\Leave\LeaveFormat::hours($eligibility->remainingHours($balance), $perDay) }}</p>
                            <p class="mt-0.5 text-xs {{ $muted }}">{{ __('hr_leave.ui.used_of', ['used' => \Modules\Hr\Support\Leave\LeaveFormat::hours((float) $balance->used, $perDay), 'total' => \Modules\Hr\Support\Leave\LeaveFormat::hours((float) $balance->deserved, $perDay)]) }}</p>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>

        <x-ui.relation-tabs :tabs="$tabs" :active="$tab" class="mb-3" />

        @if ($requests->isEmpty())
            <x-ui.empty-state icon="sun" :heading="__('hr_leave.ui.no_requests')" :body="__('hr_leave.ui.no_requests_body')" />
        @else
            @include('hr::pages.leave._requests-table', ['requests' => $requests, 'showEmployee' => false, 'mine' => true])

            <div class="mt-4">
                <flux:pagination :paginator="$requests" class="text-secondary-medium! dark:text-ink-muted!" scroll-to="#hr-my-leaves-heading" />
            </div>
        @endif
    @endif

    @include('hr::pages.leave._request-form-modal')

    <x-ui.confirm-modal
        wire:model="showWithdrawModal"
        close="cancelWithdraw"
        :title="__('hr_leave.ui.withdraw_title')"
        :message="__('hr_leave.ui.withdraw_message')"
        confirm="withdraw"
        :confirm-label="__('hr_leave.ui.withdraw')"
        confirm-test="confirm-withdraw-leave"
    />
</x-desk.register>
