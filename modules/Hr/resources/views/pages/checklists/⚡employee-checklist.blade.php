<?php

use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Modules\Hr\Actions\Checklists\DeleteChecklist;
use Modules\Hr\Actions\Checklists\DeleteEmployeeItem;
use Modules\Hr\Actions\Checklists\SaveEmployeeChecklist;
use Modules\Hr\Actions\Checklists\SaveEmployeeItem;
use Modules\Hr\Models\Checklist;
use Modules\Hr\Models\Employee;
use Modules\Hr\Models\EmployeeItem;
use Modules\Hr\Support\Checklists\ChecklistPeople;
use Modules\Hr\Support\Checklists\EmployeeChecklistRows;

/*
 * One employee's checklist, for HR, to embed in the employee record:
 *
 *     <livewire:hr::checklists.employee-checklist :employee-id="$employee->id" />
 *
 * Top: the checklist items that apply to the employee, each with a tick, who
 * hands it over and, once delivered, who takes it back (naming a receiver
 * starts the return; "Close" removes a row that came back). Bottom: the
 * employee's personal items (brand, model, serial). Only someone who may edit
 * the employee (employees.update in their governorate) can mount it; every
 * write goes through a Checklists action that checks again.
 */
new class extends Component {
    #[Locked]
    public int $employeeId;

    /** @var list<array<string, mixed>> */
    public array $rows = [];

    public bool $showItemModal = false;

    #[Locked]
    public ?int $editingItemId = null;

    /** @var array{item: string, brand: string, model: string, serial_number: string, status: string, notes: string} */
    public array $itemForm = ['item' => '', 'brand' => '', 'model' => '', 'serial_number' => '', 'status' => '', 'notes' => ''];

    public bool $showDeleteItemModal = false;

    #[Locked]
    public ?int $deletingItemId = null;

    public string $deletingItemName = '';

    public bool $showCloseModal = false;

    #[Locked]
    public ?int $closingChecklistId = null;

    public string $closingItemName = '';

    public function mount(int $employeeId): void
    {
        $this->employeeId = $employeeId;

        Gate::authorize('manage', [Checklist::class, $this->employee()]);

        $this->loadRows();
    }

    private function employee(): Employee
    {
        return Employee::query()->whereKey($this->employeeId)->firstOrFail(['id', 'city_id', 'created_at']);
    }

    private function loadRows(): void
    {
        $this->rows = EmployeeChecklistRows::for($this->employee());
    }

    /**
     * @return Collection<int, Employee>
     */
    #[Computed]
    public function deliverers(): Collection
    {
        return ChecklistPeople::deliverers();
    }

    /**
     * @return Collection<int, EmployeeItem>
     */
    #[Computed]
    public function items(): Collection
    {
        return EmployeeItem::query()
            ->where('employee_id', $this->employeeId)
            ->orderBy('id')
            ->get(['id', 'item', 'brand', 'model', 'serial_number', 'status', 'notes']);
    }

    public function saveChecklist(): void
    {
        $notified = app(SaveEmployeeChecklist::class)->handle($this->employee(), array_map(fn (array $row): array => [
            'item_id' => $row['item_id'] ?? null,
            'is_selected' => (bool) ($row['is_selected'] ?? false),
            'delivered_id' => ($row['delivered_id'] ?? '') === '' ? null : $row['delivered_id'],
            'receiver_id' => ($row['receiver_id'] ?? '') === '' ? null : $row['receiver_id'],
        ], $this->rows));

        $this->resetValidation();
        $this->loadRows();
        $this->dispatch('hr-checklist-counts-changed');

        Flux::toast(variant: 'success', text: $notified > 0
            ? trans_choice('hr_checklists.employee.saved_and_notified', $notified, ['count' => $notified])
            : __('hr_checklists.employee.saved'));
    }

    public function confirmClose(int $checklistId): void
    {
        $row = collect($this->rows)->first(fn (array $row): bool => (int) ($row['checklist_id'] ?? 0) === $checklistId && ($row['is_returned'] ?? false));

        if ($row === null) {
            return;
        }

        $this->closingChecklistId = $checklistId;
        $this->closingItemName = (string) $row['item_name'];
        $this->showCloseModal = true;
    }

    public function closeCycle(): void
    {
        if ($this->closingChecklistId === null) {
            return;
        }

        $checklistId = $this->closingChecklistId;
        $this->cancelClose();

        app(DeleteChecklist::class)->handle($this->employee(), $checklistId);

        $this->loadRows();

        Flux::toast(variant: 'success', text: __('hr_checklists.employee.cycle_closed'));
    }

    public function cancelClose(): void
    {
        $this->showCloseModal = false;
        $this->closingChecklistId = null;
        $this->closingItemName = '';
    }

    public function openItemModal(?int $itemId = null): void
    {
        $this->resetItemForm();

        if ($itemId !== null) {
            $item = $this->items->firstWhere('id', $itemId);

            if ($item === null) {
                return;
            }

            $this->editingItemId = $item->id;
            $this->itemForm = [
                'item' => (string) $item->item,
                'brand' => (string) $item->brand,
                'model' => (string) $item->model,
                'serial_number' => (string) $item->serial_number,
                'status' => (string) $item->status,
                'notes' => (string) $item->notes,
            ];
        }

        $this->showItemModal = true;
    }

    public function saveItem(): void
    {
        try {
            app(SaveEmployeeItem::class)->handle($this->employee(), $this->editingItemId, $this->itemForm);
        } catch (ValidationException $exception) {
            throw ValidationException::withMessages(
                collect($exception->errors())->mapWithKeys(fn (array $messages, string $key): array => ['itemForm.'.$key => $messages])->all(),
            );
        }

        $this->closeItemModal();
        unset($this->items);

        Flux::toast(variant: 'success', text: __('hr_checklists.employee.item_saved'));
    }

    public function closeItemModal(): void
    {
        $this->showItemModal = false;
        $this->resetItemForm();
    }

    public function confirmDeleteItem(int $itemId): void
    {
        $item = $this->items->firstWhere('id', $itemId);

        if ($item === null) {
            return;
        }

        $this->deletingItemId = $item->id;
        $this->deletingItemName = (string) $item->item;
        $this->showDeleteItemModal = true;
    }

    public function deleteItem(): void
    {
        if ($this->deletingItemId === null) {
            return;
        }

        $itemId = $this->deletingItemId;
        $this->cancelDeleteItem();

        app(DeleteEmployeeItem::class)->handle($this->employee(), $itemId);

        unset($this->items);

        Flux::toast(variant: 'success', text: __('hr_checklists.employee.item_deleted'));
    }

    public function cancelDeleteItem(): void
    {
        $this->showDeleteItemModal = false;
        $this->deletingItemId = null;
        $this->deletingItemName = '';
    }

    private function resetItemForm(): void
    {
        $this->resetValidation();
        $this->editingItemId = null;
        $this->itemForm = ['item' => '', 'brand' => '', 'model' => '', 'serial_number' => '', 'status' => '', 'notes' => ''];
    }
};
?>

@php
    $deliverers = $this->deliverers;
    $items = $this->items;
    $muted = 'text-secondary-medium dark:text-ink-muted';
@endphp

<div class="grid gap-6" data-hr-employee-checklist>
    <x-ui.panel :title="__('hr_checklists.employee.checklist_title')" :subtitle="__('hr_checklists.employee.checklist_subtitle')" :clip="false">
        @if ($rows === [])
            <x-ui.empty-state icon="clipboard-document-check" :heading="__('hr_checklists.employee.no_items_heading')" :body="__('hr_checklists.employee.no_items_body')" class="min-h-40!" />
        @else
            <form wire:submit="saveChecklist">
                <ul class="divide-y divide-secondary-soft dark:divide-line">
                    @foreach ($rows as $index => $row)
                        @php
                            $isDelivered = ($row['delivered_status'] ?? null) === 'delivered';
                            $isReturned = (bool) ($row['is_returned'] ?? false);
                            $selected = (bool) ($row['is_selected'] ?? false);
                        @endphp
                        <li wire:key="checklist-row-{{ $row['item_id'] }}" class="grid grid-cols-1 items-start gap-x-6 gap-y-3 px-4 py-3 md:grid-cols-3">
                            <div class="flex min-h-10 flex-wrap items-center gap-x-3 gap-y-1">
                                <label class="flex min-w-0 items-center gap-3">
                                    <input type="checkbox" wire:model.live="rows.{{ $index }}.is_selected" class="size-5 shrink-0 rounded border-secondary-light text-primary dark:border-field-line dark:bg-surface-2">
                                    <span class="font-semibold text-secondary-dark dark:text-ink" dir="auto">{{ $row['item_name'] }}</span>
                                </label>
                                @if ($row['checklist_id'] !== null)
                                    <x-hr::checklists.status :delivered="$isReturned ? 'delivered' : $row['delivered_status']" :returned="$isReturned ? 'received' : $row['returned_status']" />
                                @endif
                                @if ($isReturned)
                                    <x-ui.button tone="ghost" size="sm" icon="archive-box-x-mark" wire:click="confirmClose({{ (int) $row['checklist_id'] }})">{{ __('hr_checklists.employee.close_cycle') }}</x-ui.button>
                                @endif
                            </div>

                            <x-ui.form-select wire:model="rows.{{ $index }}.delivered_id" wire:key="deliverer-{{ $row['item_id'] }}-{{ $selected ? 'on' : 'off' }}" :label="__('hr_checklists.employee.deliver_by')" :placeholder="__('hr_checklists.employee.deliver_by_me')" :disabled="! $selected">
                                <option value="">{{ __('hr_checklists.employee.deliver_by_me') }}</option>
                                @foreach ($deliverers as $deliverer)
                                    <option value="{{ $deliverer->id }}">{{ $deliverer->name }}</option>
                                @endforeach
                            </x-ui.form-select>

                            @if ($isDelivered && ! $isReturned)
                                <x-ui.form-select wire:model="rows.{{ $index }}.receiver_id" wire:key="receiver-{{ $row['item_id'] }}-{{ $selected ? 'on' : 'off' }}" :label="__('hr_checklists.employee.receive_by')" :placeholder="__('hr_checklists.employee.no_return_yet')" :help="__('hr_checklists.employee.receive_help')" :disabled="! $selected">
                                    <option value="">{{ __('hr_checklists.employee.no_return_yet') }}</option>
                                    @foreach ($deliverers as $receiver)
                                        <option value="{{ $receiver->id }}">{{ $receiver->name }}</option>
                                    @endforeach
                                </x-ui.form-select>
                            @endif
                        </li>
                    @endforeach
                </ul>

                <div class="flex justify-end border-t border-panel-border px-4 py-3">
                    <x-ui.button type="submit" tone="primary" data-test="save-employee-checklist">{{ __('hr_checklists.employee.save_checklist') }}</x-ui.button>
                </div>
            </form>
        @endif
    </x-ui.panel>

    <x-ui.panel :title="__('hr_checklists.employee.items_title')" :count="$items->count()">
        <x-slot:toolbar>
            <x-ui.button tone="secondary" size="sm" icon="plus" wire:click="openItemModal" data-test="add-employee-item">{{ __('hr_checklists.employee.add_item') }}</x-ui.button>
        </x-slot:toolbar>

        @if ($items->isEmpty())
            <p class="px-4 py-6 text-center text-sm {{ $muted }}">{{ __('hr_checklists.employee.no_personal_items') }}</p>
        @else
            <ul class="divide-y divide-secondary-soft dark:divide-line">
                @foreach ($items as $item)
                    <li wire:key="employee-item-{{ $item->id }}" class="flex flex-wrap items-center gap-x-4 gap-y-1 px-4 py-3">
                        <div class="min-w-0 flex-1 basis-56">
                            <p class="font-semibold text-secondary-dark dark:text-ink" dir="auto">{{ $item->item }}</p>
                            <p class="mt-0.5 text-sm {{ $muted }}" dir="auto">
                                {{ collect([$item->brand, $item->model, $item->serial_number, $item->status])->filter()->implode(' · ') ?: '—' }}
                            </p>
                            @if (filled($item->notes))
                                <p class="mt-0.5 text-sm {{ $muted }}" dir="auto">{{ $item->notes }}</p>
                            @endif
                        </div>
                        <div class="ms-auto flex shrink-0 items-center gap-1">
                            <x-ui.button tone="ghost" size="sm" icon="pencil-square" class="size-8!" wire:click="openItemModal({{ $item->id }})" :aria-label="__('common.edit_name', ['name' => $item->item])" />
                            <x-ui.button tone="ghost" size="sm" icon="trash" class="size-8! text-status-critical!" wire:click="confirmDeleteItem({{ $item->id }})" :aria-label="__('common.delete_name', ['name' => $item->item])" />
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </x-ui.panel>

    <x-ui.modal
        wire:model="showItemModal"
        size="md"
        :title="$editingItemId === null ? __('hr_checklists.employee.add_item') : __('hr_checklists.employee.edit_item')"
        submit="saveItem"
        close="closeItemModal"
        required-note
    >
        @if ($showItemModal)
            <div class="grid gap-x-4 gap-y-3 sm:grid-cols-2">
                <x-ui.form-input wire:model="itemForm.item" :label="__('hr_checklists.columns.item')" required maxlength="255" class="sm:col-span-2" />
                <x-ui.form-input wire:model="itemForm.brand" :label="__('hr_checklists.employee.brand')" maxlength="255" />
                <x-ui.form-input wire:model="itemForm.model" :label="__('hr_checklists.employee.model')" maxlength="255" />
                <x-ui.form-input wire:model="itemForm.serial_number" :label="__('hr_checklists.employee.serial_number')" maxlength="255" dir="ltr" />
                <x-ui.form-input wire:model="itemForm.status" :label="__('hr_checklists.employee.condition')" maxlength="255" />
                <x-ui.form-textarea wire:model="itemForm.notes" :label="__('hr_checklists.columns.notes')" maxlength="2000" class="sm:col-span-2" />
            </div>
        @endif

        <x-slot:actions>
            <x-ui.button type="submit" tone="primary" data-test="save-employee-item">{{ __('common.save_changes') }}</x-ui.button>
        </x-slot:actions>
    </x-ui.modal>

    <x-ui.confirm-modal
        wire:model="showDeleteItemModal"
        close="cancelDeleteItem"
        :title="__('common.delete_name', ['name' => $deletingItemName])"
        :message="__('hr_checklists.employee.delete_item_message')"
        confirm="deleteItem"
        :confirm-label="__('common.delete')"
    />

    <x-ui.confirm-modal
        wire:model="showCloseModal"
        close="cancelClose"
        tone="neutral"
        :title="__('hr_checklists.employee.close_title', ['name' => $closingItemName])"
        :message="__('hr_checklists.employee.close_message')"
        confirm="closeCycle"
        :confirm-label="__('hr_checklists.employee.close_cycle')"
    />
</div>
