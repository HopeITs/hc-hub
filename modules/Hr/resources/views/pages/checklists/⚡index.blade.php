<?php

use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Modules\Hr\Livewire\Concerns\Checklists\ReadsChecklistLists;
use Modules\Hr\Models\Checklist;
use Modules\Hr\Support\Checklists\ChecklistWorkflow;

/*
 * The four checklist lists of the old HR app on one page: my items
 * (checklists.view-own), items I must hand over (checklists.deliver), items I
 * must take back (checklists.receive) and my department's finished hand-overs
 * (checklists.manage). The route name picks the list; the route and mount()
 * both check that list's permission. Confirming a hand-over or a return goes
 * through ChecklistWorkflow, which re-scopes the id to the signed-in employee.
 */
new #[Layout('layouts::desk'), Title('hr_checklists.titles.index')] class extends Component {
    use ReadsChecklistLists;
    use WithPagination;

    /** list => [route name, policy ability] */
    public const LISTS = [
        'mine' => ['hr.checklists.index', 'viewOwn'],
        'delivers' => ['hr.checklists.pending-delivers', 'deliver'],
        'returns' => ['hr.checklists.pending-returns', 'receive'],
        'done' => ['hr.checklists.done-requests', 'viewDone'],
    ];

    #[Locked]
    public string $list = 'mine';

    /** @var array<int|string, string> a note per row, typed before confirming */
    public array $notes = [];

    #[Url(except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $action = '';

    #[Url(except: '')]
    public string $item = '';

    #[Url(except: '')]
    public string $person = '';

    public function mount(): void
    {
        $routeName = (string) request()->route()?->getName();

        foreach (self::LISTS as $list => [$name]) {
            if ($name === $routeName) {
                $this->list = $list;
            }
        }

        $this->authorizeList();
        $this->normaliseFilters();
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'action', 'item', 'person'], true)) {
            $this->normaliseFilters();
            $this->resetPage();
        }
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'action', 'item', 'person']);
        $this->resetPage();
    }

    public function markDelivered(int $checklistId): void
    {
        Gate::authorize('deliver', Checklist::class);

        $employeeId = Auth::user()?->employee_id;
        abort_if($employeeId === null, 403);

        app(ChecklistWorkflow::class)->markDelivered($checklistId, $employeeId, $this->noteFor($checklistId));

        unset($this->notes[$checklistId], $this->pendingDelivers);
        $this->dispatch('hr-checklist-counts-changed');

        Flux::toast(variant: 'success', text: __('hr_checklists.marked_delivered'));
    }

    public function markReceived(int $checklistId): void
    {
        Gate::authorize('receive', Checklist::class);

        $employeeId = Auth::user()?->employee_id;
        abort_if($employeeId === null, 403);

        app(ChecklistWorkflow::class)->markReceived($checklistId, $employeeId, $this->noteFor($checklistId));

        unset($this->notes[$checklistId], $this->pendingReturns);
        $this->dispatch('hr-checklist-counts-changed');

        Flux::toast(variant: 'success', text: __('hr_checklists.marked_received'));
    }

    private function authorizeList(): void
    {
        Gate::authorize(self::LISTS[$this->list][1], Checklist::class);
    }

    private function noteFor(int $checklistId): ?string
    {
        $note = trim((string) ($this->notes[$checklistId] ?? ''));

        return $note === '' ? null : mb_substr($note, 0, 1000);
    }

    private function normaliseFilters(): void
    {
        if (! in_array($this->action, ['delivered', 'received'], true)) {
            $this->action = '';
        }

        if ($this->item !== '' && ! ctype_digit($this->item)) {
            $this->item = '';
        }

        if ($this->person !== '' && ! ctype_digit($this->person)) {
            $this->person = '';
        }
    }
};
?>

@php
    $user = auth()->user();
    $hasEmployee = $user->employee_id !== null;

    $tabs = [];

    foreach ($this::LISTS as $key => [$routeName, $ability]) {
        if ($user->can($ability, \Modules\Hr\Models\Checklist::class)) {
            $tabs[$key] = ['route' => $routeName, 'label' => __('hr_checklists.lists.'.$key)];
        }
    }

    $count = match ($list) {
        'mine' => $this->myItems->count(),
        'delivers' => $this->pendingDelivers->count(),
        'returns' => $this->pendingReturns->count(),
        default => $this->doneRequests->total(),
    };

    $filtersActive = $search !== '' || $action !== '' || $item !== '' || $person !== '';
@endphp

<x-desk.register class="@container" heading-id="hr-checklists-heading" :title="__('hr_checklists.lists.'.$list)" :count="$count" data-hr-checklists="{{ $list }}">
    @if ($list === 'done' && $this->departmentContext !== null)
        <x-slot:search>
            <x-desk.register-search :label="__('hr_checklists.search')" :placeholder="__('hr_checklists.search_placeholder')" wire:model.live.debounce.300ms="search" />
        </x-slot:search>
        <x-slot:filters>
            <x-ui.header-filter-select :label="__('hr_checklists.columns.action')" wire:model.live="action" :value="$action">
                <option value="">{{ __('hr_checklists.all_actions') }}</option>
                <option value="delivered">{{ __('hr_checklists.status.delivered') }}</option>
                <option value="received">{{ __('hr_checklists.status.received') }}</option>
            </x-ui.header-filter-select>

            <x-ui.header-filter-select :label="__('hr_checklists.columns.item')" wire:model.live="item" :value="$item">
                <option value="">{{ __('hr_checklists.all_items') }}</option>
                @foreach ($this->itemOptions as $itemOption)
                    <option value="{{ $itemOption->id }}">{{ $itemOption->name }}</option>
                @endforeach
            </x-ui.header-filter-select>

            <x-ui.header-filter-select :label="__('hr_checklists.columns.done_by')" wire:model.live="person" :value="$person">
                <option value="">{{ __('hr_checklists.all_people') }}</option>
                @foreach ($this->departmentPeople as $personOption)
                    <option value="{{ $personOption->id }}">{{ $personOption->name }}</option>
                @endforeach
            </x-ui.header-filter-select>
        </x-slot:filters>
    @endif

    @if (count($tabs) > 1)
        <nav class="mb-4 flex flex-wrap items-center gap-1 border-b border-secondary-soft dark:border-line" aria-label="{{ __('hr_checklists.titles.index') }}">
            @foreach ($tabs as $key => $tab)
                <a
                    href="{{ route($tab['route']) }}"
                    wire:navigate
                    wire:key="checklist-tab-{{ $key }}"
                    @if ($key === $list) aria-current="page" @endif
                    @class([
                        'inline-flex min-h-11 touch-manipulation items-center gap-1.5 border-b-2 px-3 text-sm font-medium transition-colors sm:min-h-10',
                        'border-primary text-secondary-dark dark:text-ink' => $key === $list,
                        'border-transparent text-secondary-medium hover:text-secondary-dark dark:text-ink-muted dark:hover:text-ink' => $key !== $list,
                    ])
                >{{ $tab['label'] }}</a>
            @endforeach
        </nav>
    @endif

    @if ($list !== 'done' && ! $hasEmployee)
        <x-ui.empty-state icon="user" :heading="__('hr_checklists.no_employee_heading')" :body="__('hr_checklists.no_employee_body')" />
    @elseif ($list === 'mine')
        @include('hr::pages.checklists._index-mine')
    @elseif ($list === 'delivers')
        @include('hr::pages.checklists._index-pending', [
            'rows' => $this->pendingDelivers,
            'confirm' => 'markDelivered',
            'confirmLabel' => __('hr_checklists.mark_delivered'),
            'personLabel' => __('hr_checklists.columns.deliver_to'),
            'emptyHeading' => __('hr_checklists.empty.delivers_heading'),
            'emptyBody' => __('hr_checklists.empty.delivers_body'),
        ])
    @elseif ($list === 'returns')
        @include('hr::pages.checklists._index-pending', [
            'rows' => $this->pendingReturns,
            'confirm' => 'markReceived',
            'confirmLabel' => __('hr_checklists.mark_received'),
            'personLabel' => __('hr_checklists.columns.receive_from'),
            'emptyHeading' => __('hr_checklists.empty.returns_heading'),
            'emptyBody' => __('hr_checklists.empty.returns_body'),
        ])
    @else
        @include('hr::pages.checklists._index-done', ['filtersActive' => $filtersActive])
    @endif
</x-desk.register>
