<?php

use App\Support\CsvExport;
use Flux\Flux;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Modules\Core\Models\City;
use Modules\Hr\Actions\Checklists\DeleteDefaultDeliverable;
use Modules\Hr\Actions\Checklists\SaveDefaultDeliverable;
use Modules\Hr\Models\ChecklistItem;
use Modules\Hr\Models\DefaultDeliverable;
use Modules\Hr\Models\Employee;
use Modules\Hr\Support\Checklists\ChecklistPeople;
use Modules\Hr\Support\Settings\HrAccess;
use Symfony\Component\HttpFoundation\StreamedResponse;

/*
 * Default deliverables: who hands each checklist item out in each
 * governorate (checklists.manage). A regional HR role sees and writes only
 * its own governorates; the list, the pickers, the export and every write
 * share HrAccess's city list. Writes go through the Checklists actions.
 */
new #[Layout('layouts::desk'), Title('hr_checklists.titles.default_deliverables')] class extends Component {
    use WithPagination;

    private const PER_PAGE = 25;

    #[Url(except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $city = '';

    #[Url(except: '')]
    public string $item = '';

    public bool $showFormModal = false;

    #[Locked]
    public ?int $editingId = null;

    public string $employeeId = '';

    public string $cityId = '';

    public string $itemId = '';

    public bool $showDeleteModal = false;

    #[Locked]
    public ?int $deletingId = null;

    public string $deletingLabel = '';

    public function mount(): void
    {
        Gate::authorize('viewAny', DefaultDeliverable::class);
        $this->normaliseFilters();
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'city', 'item'], true)) {
            $this->normaliseFilters();
            $this->resetPage();
        }

        // The employee list follows the governorate: one picked for another governorate no longer applies.
        if ($property === 'cityId') {
            $this->employeeId = '';
            unset($this->employeeOptions);
        }
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'city', 'item']);
        $this->resetPage();
    }

    /**
     * @return Builder<DefaultDeliverable>
     */
    private function baseQuery(): Builder
    {
        $names = implode(',', ChecklistPeople::NAME_COLUMNS);
        $search = trim($this->search);

        return HrAccess::for(Auth::user())->scopeToCities(DefaultDeliverable::query())
            ->select(['id', 'employee_id', 'city_id', 'item_id'])
            ->with(["employee:{$names}", 'item:id,name'])
            ->when($this->city !== '', fn (Builder $query) => $query->where('city_id', (int) $this->city))
            ->when($this->item !== '', fn (Builder $query) => $query->where('item_id', (int) $this->item))
            ->when($search !== '', fn (Builder $query) => $query->where(fn (Builder $match) => $match
                ->whereHas('employee', fn (Builder $employee) => $employee->search($search))
                ->orWhereHas('item', fn (Builder $item) => $item->whereLike('name', "%{$search}%"))
                ->orWhereHas('city', fn (Builder $city) => $city->search($search))))
            ->orderBy('city_id')
            ->orderBy('item_id')
            ->orderBy('id');
    }

    /**
     * @return LengthAwarePaginator<int, DefaultDeliverable>
     */
    #[Computed]
    public function deliverables(): LengthAwarePaginator
    {
        return $this->baseQuery()->paginate(self::PER_PAGE);
    }

    /**
     * Governorates that have a center, inside the viewer's scope: id => name.
     *
     * @return array<int, string>
     */
    #[Computed]
    public function cityOptions(): array
    {
        $allowed = HrAccess::for(Auth::user())->cityIds();
        $withCenters = City::query()->whereHas('centers')->pluck('id')->flip();

        return collect(City::names())
            ->filter(fn (string $name, int $id): bool => $withCenters->has($id) && ($allowed === null || in_array($id, $allowed, true)))
            ->all();
    }

    /**
     * @return Collection<int, ChecklistItem>
     */
    #[Computed]
    public function itemOptions(): Collection
    {
        return ChecklistItem::query()->orderBy('order')->orderBy('name')->get(['id', 'name']);
    }

    /**
     * Service-department staff living in the governorate picked in the form.
     *
     * @return Collection<int, Employee>
     */
    #[Computed]
    public function employeeOptions(): Collection
    {
        if ($this->cityId === '' || ! array_key_exists((int) $this->cityId, $this->cityOptions)) {
            return new Collection;
        }

        return ChecklistPeople::inServiceDepartments()
            ->where('city_id', (int) $this->cityId)
            ->orderBy(Employee::translatedColumn('first_name'))
            ->get(ChecklistPeople::NAME_COLUMNS);
    }

    public function openCreateModal(): void
    {
        Gate::authorize('create', DefaultDeliverable::class);

        $this->resetForm();
        $this->showFormModal = true;
    }

    public function openEditModal(int $deliverableId): void
    {
        $model = DefaultDeliverable::query()->findOrFail($deliverableId);

        Gate::authorize('update', $model);

        $this->resetForm();
        $this->editingId = $model->id;
        $this->cityId = (string) $model->city_id;
        $this->employeeId = (string) $model->employee_id;
        $this->itemId = (string) $model->item_id;
        $this->showFormModal = true;
    }

    public function save(): void
    {
        $isCreate = $this->editingId === null;

        try {
            app(SaveDefaultDeliverable::class)->handle($this->editingId, [
                'employee_id' => $this->employeeId === '' ? null : $this->employeeId,
                'city_id' => $this->cityId === '' ? null : $this->cityId,
                'item_id' => $this->itemId === '' ? null : $this->itemId,
            ]);
        } catch (\Illuminate\Validation\ValidationException $exception) {
            throw \Illuminate\Validation\ValidationException::withMessages(
                collect($exception->errors())->mapWithKeys(fn (array $messages, string $key): array => [match ($key) {
                    'employee_id' => 'employeeId',
                    'item_id' => 'itemId',
                    default => 'cityId',
                } => $messages])->all(),
            );
        }

        $this->closeFormModal();
        unset($this->deliverables);

        Flux::toast(variant: 'success', text: __($isCreate ? 'hr_checklists.defaults.created' : 'hr_checklists.defaults.updated'));
    }

    public function closeFormModal(): void
    {
        $this->showFormModal = false;
        $this->resetForm();
    }

    public function confirmDelete(int $deliverableId): void
    {
        $names = implode(',', ChecklistPeople::NAME_COLUMNS);
        $model = DefaultDeliverable::query()->with(["employee:{$names}", 'item:id,name'])->findOrFail($deliverableId);

        Gate::authorize('delete', $model);

        $this->deletingId = $model->id;
        $this->deletingLabel = trim(($model->item?->name ?? '').' · '.($model->employee?->name ?? ''), ' ·');
        $this->showDeleteModal = true;
    }

    public function delete(): void
    {
        if ($this->deletingId === null) {
            return;
        }

        $deliverableId = $this->deletingId;
        $this->cancelDelete();

        app(DeleteDefaultDeliverable::class)->handle($deliverableId);

        unset($this->deliverables);

        Flux::toast(variant: 'success', text: __('hr_checklists.defaults.deleted'));
    }

    public function cancelDelete(): void
    {
        $this->showDeleteModal = false;
        $this->deletingId = null;
        $this->deletingLabel = '';
    }

    public function export(): StreamedResponse
    {
        Gate::authorize('viewAny', DefaultDeliverable::class);

        $query = $this->baseQuery();

        $rows = (function () use ($query): Generator {
            foreach ($query->lazy(200) as $row) {
                yield [$row->id, $row->employee?->name ?? '', City::nameOf($row->city_id), $row->item?->name ?? ''];
            }
        })();

        return CsvExport::download('default-deliverables-'.now()->format('Y-m-d').'.csv', [
            __('hr_checklists.columns.id'),
            __('hr_checklists.columns.employee'),
            __('common.city'),
            __('hr_checklists.columns.item'),
        ], $rows);
    }

    private function resetForm(): void
    {
        $this->resetValidation();
        $this->editingId = null;
        $this->employeeId = '';
        $this->cityId = '';
        $this->itemId = '';
        unset($this->employeeOptions);
    }

    private function normaliseFilters(): void
    {
        if ($this->city !== '' && ! array_key_exists((int) $this->city, $this->cityOptions)) {
            $this->city = '';
        }

        if ($this->item !== '' && ! ctype_digit($this->item)) {
            $this->item = '';
        }
    }
};
?>

@php
    $rows = $this->deliverables;
    $canCreate = auth()->user()->can('create', \Modules\Hr\Models\DefaultDeliverable::class);
    $filtersActive = $search !== '' || $city !== '' || $item !== '';
@endphp

<x-desk.register class="@container" heading-id="hr-default-deliverables-heading" :title="__('hr_checklists.titles.default_deliverables')" :count="$rows->total()" data-hr-default-deliverables>
    <x-slot:search>
        <x-desk.register-search :label="__('hr_checklists.search')" :placeholder="__('hr_checklists.defaults.search_placeholder')" wire:model.live.debounce.300ms="search" />
    </x-slot:search>
    <x-slot:filters>
        <x-ui.header-filter-select :label="__('common.city')" wire:model.live="city" :value="$city">
            <option value="">{{ __('hr_checklists.all_cities') }}</option>
            @foreach ($this->cityOptions as $cityOptionId => $cityOptionName)
                <option value="{{ $cityOptionId }}">{{ $cityOptionName }}</option>
            @endforeach
        </x-ui.header-filter-select>

        <x-ui.header-filter-select :label="__('hr_checklists.columns.item')" wire:model.live="item" :value="$item">
            <option value="">{{ __('hr_checklists.all_items') }}</option>
            @foreach ($this->itemOptions as $itemOption)
                <option value="{{ $itemOption->id }}">{{ $itemOption->name }}</option>
            @endforeach
        </x-ui.header-filter-select>
    </x-slot:filters>

    <x-slot:actions>
        <x-ui.button tone="action" icon="arrow-down-tray" wire:click="export" data-test="export-default-deliverables">{{ __('hr_checklists.export') }}</x-ui.button>
        @if ($canCreate)
            <x-ui.button tone="primary" icon="plus" wire:click="openCreateModal" data-test="create-default-deliverable" data-quick-create>{{ __('hr_checklists.defaults.add') }}</x-ui.button>
        @endif
    </x-slot:actions>

    @if ($rows->isEmpty())
        <x-ui.empty-state icon="clipboard-document-list" :heading="$filtersActive ? __('hr_checklists.empty.no_matching') : __('hr_checklists.defaults.empty_heading')" :body="$filtersActive ? __('common.change_the_search_or_filters_and_try_again') : __('hr_checklists.defaults.empty_body')">
            <x-slot:action>
                @if ($filtersActive)
                    <x-ui.button tone="secondary" size="sm" wire:click="clearFilters">{{ __('common.reset_filters') }}</x-ui.button>
                @elseif ($canCreate)
                    <x-ui.button tone="primary" icon="plus" wire:click="openCreateModal">{{ __('hr_checklists.defaults.add') }}</x-ui.button>
                @endif
            </x-slot:action>
        </x-ui.empty-state>
    @else
        <x-ui.data-table :caption="__('hr_checklists.titles.default_deliverables')">
            <x-slot:head>
                <th class="w-20!">{{ __('hr_checklists.columns.id') }}</th>
                <th>{{ __('hr_checklists.columns.employee') }}</th>
                <th>{{ __('common.city') }}</th>
                <th>{{ __('hr_checklists.columns.item') }}</th>
                <th class="w-24!"><span class="sr-only">{{ __('common.actions') }}</span></th>
            </x-slot:head>

            @foreach ($rows as $row)
                @php
                    $rowLabel = trim(($row->item?->name ?? '').' · '.($row->employee?->name ?? ''), ' ·');
                    $canEdit = auth()->user()->can('update', $row);
                @endphp
                <tr wire:key="default-deliverable-{{ $row->id }}">
                    <x-ui.data-table.cell :label="__('hr_checklists.columns.id')" class="tabular-nums">{{ $row->id }}</x-ui.data-table.cell>
                    <x-ui.data-table.cell :label="__('hr_checklists.columns.employee')"><span class="font-semibold" dir="auto">{{ $row->employee?->name ?? '—' }}</span></x-ui.data-table.cell>
                    <x-ui.data-table.cell :label="__('common.city')"><span dir="auto">{{ \Modules\Core\Models\City::nameOf($row->city_id) ?: '—' }}</span></x-ui.data-table.cell>
                    <x-ui.data-table.cell :label="__('hr_checklists.columns.item')"><span dir="auto">{{ $row->item?->name ?? '—' }}</span></x-ui.data-table.cell>
                    <td class="px-3.5 py-2.5">
                        @if ($canEdit)
                            <span class="flex items-center justify-end gap-1">
                                <x-ui.button tone="ghost" size="sm" icon="pencil-square" class="size-8!" wire:click="openEditModal({{ $row->id }})" :aria-label="__('common.edit_name', ['name' => $rowLabel])" />
                                <x-ui.button tone="ghost" size="sm" icon="trash" class="size-8! text-status-critical!" wire:click="confirmDelete({{ $row->id }})" :aria-label="__('common.delete_name', ['name' => $rowLabel])" />
                            </span>
                        @endif
                    </td>
                </tr>
            @endforeach
        </x-ui.data-table>

        <div class="mt-4">
            <flux:pagination :paginator="$rows" class="text-secondary-medium! dark:text-ink-muted!" scroll-to="#hr-default-deliverables-heading" />
        </div>
    @endif

    <x-ui.modal
        wire:model="showFormModal"
        size="sm"
        :title="$editingId === null ? __('hr_checklists.defaults.add') : __('hr_checklists.defaults.edit')"
        :description="__('hr_checklists.defaults.dialog_description')"
        submit="save"
        close="closeFormModal"
        required-note
    >
        @if ($showFormModal)
            <div class="grid gap-y-3">
                <x-ui.form-select wire:model.live="cityId" :label="__('common.city')" :placeholder="__('hr_checklists.defaults.choose_city')" required>
                    @foreach ($this->cityOptions as $cityOptionId => $cityOptionName)
                        <option value="{{ $cityOptionId }}">{{ $cityOptionName }}</option>
                    @endforeach
                </x-ui.form-select>

                <x-ui.form-select wire:model="employeeId" wire:key="deliverable-employee-{{ $cityId }}" :label="__('hr_checklists.columns.employee')" :placeholder="__('hr_checklists.defaults.choose_employee')" :help="__('hr_checklists.defaults.employee_help')" :disabled="$cityId === ''" required>
                    @foreach ($this->employeeOptions as $employeeOption)
                        <option value="{{ $employeeOption->id }}">{{ $employeeOption->name }}</option>
                    @endforeach
                </x-ui.form-select>

                <x-ui.form-select wire:model="itemId" :label="__('hr_checklists.columns.item')" :placeholder="__('hr_checklists.defaults.choose_item')" required>
                    @foreach ($this->itemOptions as $itemOption)
                        <option value="{{ $itemOption->id }}">{{ $itemOption->name }}</option>
                    @endforeach
                </x-ui.form-select>
            </div>
        @endif

        <x-slot:actions>
            <x-ui.button type="submit" tone="primary" data-test="save-default-deliverable">{{ __('common.save_changes') }}</x-ui.button>
        </x-slot:actions>
    </x-ui.modal>

    <x-ui.confirm-modal
        wire:model="showDeleteModal"
        close="cancelDelete"
        :title="__('hr_checklists.defaults.delete_title', ['name' => $deletingLabel])"
        :message="__('hr_checklists.defaults.delete_message')"
        confirm="delete"
        :confirm-label="__('common.delete')"
        confirm-test="confirm-delete-default-deliverable"
    />
</x-desk.register>
