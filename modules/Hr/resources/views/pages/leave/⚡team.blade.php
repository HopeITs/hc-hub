<?php

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Modules\Hr\Enums\LeaveRequestStatus;
use Modules\Hr\Livewire\Concerns\Leave\ManagesLeaveRequestForm;
use Modules\Hr\Models\LeaveRequest;
use Modules\Hr\Support\Leave\LeaveContext;

/*
 * Team leaves: the requests of the people the user manages (their position
 * reports to one the user holds today), or, for HR, of its governorates
 * (LeaveRequest::manageableBy). A former employee's pending request is left
 * out: nobody decides it. HR files a request for an employee from here.
 */
new #[Layout('layouts::desk'), Title('hr_leave.titles.team')] class extends Component {
    use ManagesLeaveRequestForm;
    use WithPagination;

    public const STATUSES = ['pending', 'accepted', 'rejected'];

    #[Url(except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $status = '';

    #[Url(except: false)]
    public bool $upcoming = false;

    public function mount(): void
    {
        Gate::authorize('viewAny', LeaveRequest::class);
        $this->sanitize();
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'status', 'upcoming'], true)) {
            $this->sanitize();
            $this->resetPage();
        }
    }

    /** @return LengthAwarePaginator<int, LeaveRequest> */
    #[Computed]
    public function requests(): LengthAwarePaginator
    {
        $requests = LeaveRequest::query()
            ->manageableBy(Auth::user())
            ->exceptFormerStaffPending()
            ->search($this->search)
            ->when($this->status !== '', fn ($query) => $query->withStatus(match ($this->status) {
                'accepted' => LeaveRequestStatus::Accepted->value,
                'rejected' => LeaveRequestStatus::Rejected->value,
                default => LeaveRequestStatus::Pending->value,
            }))
            ->when($this->upcoming, fn ($query) => $query->upcoming())
            ->orderBy('status')
            ->orderByDesc('start_at')
            ->orderByDesc('id')
            ->paginate(25);

        LeaveContext::load($requests->getCollection());

        return $requests;
    }

    protected function afterRequestSaved(): void
    {
        unset($this->requests);
    }

    private function sanitize(): void
    {
        if (! in_array($this->status, self::STATUSES, true)) {
            $this->status = '';
        }
    }
};
?>

@php
    $requests = $this->requests;
    $canFileForOthers = \Illuminate\Support\Facades\Gate::allows('createForOthers', \Modules\Hr\Models\LeaveRequest::class);
@endphp

<x-desk.register class="@container" heading-id="hr-team-leaves-heading" :title="__('hr_leave.titles.team')" :count="$requests->total()" data-hr-team-leaves>
    <x-slot:actions>
        <x-ui.button tone="secondary" icon="calendar-days" :href="route('hr.leave.team-calendar')" wire:navigate>{{ __('hr_leave.ui.team_calendar') }}</x-ui.button>
        @can('leaves.manage-balances')
            <x-ui.button tone="secondary" icon="scale" :href="route('hr.leave.balances')" wire:navigate>{{ __('hr_leave.titles.balances') }}</x-ui.button>
        @endcan
        @if ($canFileForOthers)
            <x-ui.button tone="primary" icon="plus" wire:click="openRequestForm" data-test="new-leave-request" data-quick-create>{{ __('hr_leave.ui.new_request_for') }}</x-ui.button>
        @endif
    </x-slot:actions>

    <x-slot:search>
        <x-desk.register-search :label="__('hr_leave.ui.search')" :placeholder="__('hr_leave.ui.search_placeholder')" wire:model.live.debounce.300ms="search" />
    </x-slot:search>

    <x-slot:filters>
        <x-ui.header-filter-select :label="__('hr_leave.ui.status')" wire:model.live="status" :value="$status">
            <option value="">{{ __('hr_leave.ui.all_statuses') }}</option>
            @foreach ($this::STATUSES as $statusOption)
                <option value="{{ $statusOption }}">{{ __('hr_leave.status.'.$statusOption) }}</option>
            @endforeach
        </x-ui.header-filter-select>
        <flux:checkbox wire:model.live="upcoming" :label="__('hr_leave.ui.upcoming_only')" />
    </x-slot:filters>

    @if ($requests->isEmpty())
        @if ($search === '' && $status === '' && ! $upcoming)
            <x-ui.empty-state icon="sun" :heading="__('hr_leave.ui.no_team_requests')" :body="__('hr_leave.ui.no_team_requests_body')" />
        @else
            <x-ui.empty-state icon="sun" :heading="__('hr_leave.ui.no_matching')" :body="__('common.change_the_search_or_filters_and_try_again')" />
        @endif
    @else
        @include('hr::pages.leave._requests-table', ['requests' => $requests, 'showEmployee' => true, 'mine' => false])

        <div class="mt-4">
            <flux:pagination :paginator="$requests" class="text-secondary-medium! dark:text-ink-muted!" scroll-to="#hr-team-leaves-heading" />
        </div>
    @endif

    @include('hr::pages.leave._request-form-modal')
</x-desk.register>
