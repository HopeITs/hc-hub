<?php

use Flux\Flux;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;
use Modules\Hr\Actions\Settings\DeleteReferenceRow;
use Modules\Hr\Actions\Settings\SaveReferenceRow;
use Modules\Hr\Support\Settings\ReferenceTables;

/*
 * One tab of HR settings: the list, the add/edit dialog and the delete
 * confirmation of a reference table described in ReferenceTables
 * (departments, job levels, leave types...). Reading needs
 * project-basics.view; each write goes through an action that authorizes
 * again through the table's policy.
 */
new class extends Component {
    use WithPagination;

    #[Locked]
    public string $table = '';

    public string $search = '';

    public bool $showFormModal = false;

    #[Locked]
    public ?int $editingId = null;

    /** @var array<string, mixed> */
    public array $form = [];

    public bool $showDeleteModal = false;

    #[Locked]
    public ?int $deletingId = null;

    public string $deletingName = '';

    public function mount(string $table): void
    {
        abort_unless(ReferenceTables::has($table), 404);

        $this->table = $table;

        Gate::authorize('viewAny', $this->definition()['model']);

        $this->resetForm();
    }

    public function updatedSearch(): void
    {
        $this->resetPage($this->pageName());
    }

    /** @return array<string, mixed> */
    private function definition(): array
    {
        return ReferenceTables::get($this->table);
    }

    private function pageName(): string
    {
        return 'p-'.$this->table;
    }

    /** @return LengthAwarePaginator<int, \Illuminate\Database\Eloquent\Model> */
    #[Computed]
    public function rows(): LengthAwarePaginator
    {
        $definition = $this->definition();
        $modelClass = $definition['model'];
        $tableName = (new $modelClass)->getTable();
        $term = trim($this->search);

        $query = $modelClass::query()
            ->select(array_map(
                fn (string $column): string => $tableName.'.'.$column,
                array_values(array_unique(['id', $definition['label'], ...$definition['columns']])),
            ))
            ->when($term !== '' && $definition['search'] !== [], fn (Builder $rows) => $rows->where(function (Builder $match) use ($definition, $term): void {
                foreach ($definition['search'] as $column) {
                    $match->orWhereLike($column, "%{$term}%");
                }
            }))
            ->orderBy($definition['order'][0], $definition['order'][1])
            ->orderBy('id');

        foreach ($definition['in_use'] as $index => [$usingTable, $column]) {
            $query->selectSub(
                DB::table($usingTable)->selectRaw('count(*)')->whereColumn($usingTable.'.'.$column, $tableName.'.id'),
                'usage_'.$index,
            );
        }

        return $query->paginate($definition['paginate'] ?? 50, pageName: $this->pageName());
    }

    public function openCreateModal(): void
    {
        Gate::authorize('create', $this->definition()['model']);

        $this->resetForm();
        $this->showFormModal = true;
    }

    public function openEditModal(int $rowId): void
    {
        $definition = $this->definition();
        $row = $definition['model']::query()->findOrFail($rowId);

        Gate::authorize('update', $row);

        $this->resetForm();
        $this->editingId = $rowId;

        foreach (array_keys($definition['fields']) as $field) {
            $value = $row->getAttributes()[$field] ?? null;
            $this->form[$field] = $value === null ? '' : (string) $value;
        }

        $this->showFormModal = true;
    }

    public function save(): void
    {
        try {
            app(SaveReferenceRow::class)->handle($this->table, $this->editingId, $this->form);
        } catch (ValidationException $exception) {
            throw ValidationException::withMessages(
                collect($exception->errors())->mapWithKeys(fn (array $messages, string $field): array => ['form.'.$field => $messages])->all(),
            );
        }

        $created = $this->editingId === null;

        $this->closeFormModal();
        unset($this->rows);

        Flux::toast(variant: 'success', text: __($created ? 'hr_settings.created' : 'hr_settings.updated'));
    }

    public function closeFormModal(): void
    {
        $this->showFormModal = false;
        $this->resetForm();
    }

    public function confirmDelete(int $rowId): void
    {
        $definition = $this->definition();
        $row = $definition['model']::query()->findOrFail($rowId);

        Gate::authorize('delete', $row);

        $this->deletingId = $rowId;
        $this->deletingName = (string) ($row->getAttributes()[$definition['label']] ?? $rowId);
        $this->showDeleteModal = true;
    }

    public function delete(): void
    {
        if ($this->deletingId === null) {
            return;
        }

        $rowId = $this->deletingId;
        $this->cancelDelete();

        try {
            app(DeleteReferenceRow::class)->handle($this->table, $rowId);
        } catch (ValidationException $exception) {
            unset($this->rows);

            Flux::toast(variant: 'danger', text: (string) collect($exception->errors())->flatten()->first());

            return;
        }

        unset($this->rows);

        Flux::toast(variant: 'success', text: __('hr_settings.deleted'));
    }

    public function cancelDelete(): void
    {
        $this->showDeleteModal = false;
        $this->deletingId = null;
        $this->deletingName = '';
    }

    private function resetForm(): void
    {
        $this->resetValidation();
        $this->editingId = null;
        $this->form = array_fill_keys(array_keys($this->definition()['fields']), '');
    }
};
?>

@php
    $definition = ReferenceTables::get($table);
    $modelClass = $definition['model'];
    $fields = $definition['fields'];
    $rows = $this->rows;
    $user = auth()->user();
    $canCreate = $user->can('create', $modelClass);
    $canUpdate = $user->can('project-basics.update');
    $canDelete = $user->can('project-basics.delete');
    $tracksUsage = $definition['in_use'] !== [];
    $muted = 'text-secondary-medium dark:text-ink-muted';
    $tabLabel = __('hr_settings.tabs.'.$table);
@endphp

<div data-hr-reference-table="{{ $table }}">
    <div class="mb-3 flex flex-wrap items-center gap-2">
        @if ($definition['search'] !== [])
            <x-desk.register-search class="min-w-0 flex-1" :label="__('hr_settings.search_in', ['table' => $tabLabel])" wire:model.live.debounce.300ms="search" />
        @endif

        <span class="text-xs font-semibold tabular-nums {{ $muted }}" data-count-badge>{{ trans_choice('hr_settings.count_rows', $rows->total(), ['count' => $rows->total()]) }}</span>

        @if ($canCreate)
            <x-ui.button tone="primary" icon="plus" class="ms-auto" wire:click="openCreateModal" data-test="hr-settings-add" data-quick-create>
                {{ __('hr_settings.add') }}
            </x-ui.button>
        @endif
    </div>

    @if ($rows->isEmpty())
        <x-ui.empty-state
            icon="rectangle-stack"
            :heading="trim($search) === '' ? __('hr_settings.empty_heading') : __('hr_settings.no_matches')"
            :body="trim($search) === '' ? __('hr_settings.empty_body') : __('common.change_the_search_and_try_again')"
            class="rounded-panel border border-panel-border"
        />
    @else
        <x-ui.data-table :caption="$tabLabel">
            <x-slot:head>
                @foreach ($definition['columns'] as $column)
                    <th>{{ __('hr_settings.fields.'.$column) }}</th>
                @endforeach
                @if ($tracksUsage)
                    <th class="w-28!">{{ __('hr_settings.used_by') }}</th>
                @endif
                @if ($canUpdate || $canDelete)
                    <th class="w-24!"><span class="sr-only">{{ __('common.actions') }}</span></th>
                @endif
            </x-slot:head>

            @foreach ($rows as $row)
                @php
                    $raw = $row->getAttributes();
                    $rowName = (string) ($raw[$definition['label']] ?? $row->getKey());
                    $usage = 0;

                    foreach ($definition['in_use'] as $index => $reference) {
                        $usage += (int) ($raw['usage_'.$index] ?? 0);
                    }
                @endphp
                <tr wire:key="{{ $table }}-{{ $row->getKey() }}" data-reference-row="{{ $row->getKey() }}">
                    @foreach ($definition['columns'] as $column)
                        @php
                            $spec = $fields[$column];
                            $value = $raw[$column] ?? null;
                            $shown = $spec['type'] === 'select' ? ReferenceTables::optionLabel($column, $value) : (string) ($value ?? '');
                        @endphp
                        <x-ui.data-table.cell :label="__('hr_settings.fields.'.$column)" :numeric="in_array($spec['type'], ['integer', 'decimal'], true)">
                            @if ($shown === '')
                                <span class="{{ $muted }}">—</span>
                            @elseif (in_array($spec['type'], ['integer', 'decimal', 'email'], true) || ($spec['dir'] ?? null) === 'ltr')
                                <bdi dir="ltr" @class(['font-semibold' => $loop->first])>{{ $shown }}</bdi>
                            @else
                                <bdi @class(['font-semibold' => $loop->first])>{{ $shown }}</bdi>
                            @endif
                        </x-ui.data-table.cell>
                    @endforeach

                    @if ($tracksUsage)
                        <x-ui.data-table.cell :label="__('hr_settings.used_by')" numeric>
                            <span class="tabular-nums {{ $usage === 0 ? $muted : '' }}">{{ $usage }}</span>
                        </x-ui.data-table.cell>
                    @endif

                    @if ($canUpdate || $canDelete)
                        <td class="px-3.5 py-2.5">
                            <span class="flex items-center justify-end gap-1">
                                @if ($canUpdate)
                                    <x-ui.button tone="ghost" size="sm" icon="pencil-square" class="size-8!" wire:click="openEditModal({{ $row->getKey() }})" :aria-label="__('common.edit_name', ['name' => $rowName])" data-test="hr-settings-edit" />
                                @endif
                                @if ($canDelete)
                                    <x-ui.button tone="ghost" size="sm" icon="trash" class="size-8! text-status-critical!" wire:click="confirmDelete({{ $row->getKey() }})" :aria-label="__('common.delete_name', ['name' => $rowName])" data-test="hr-settings-delete" />
                                @endif
                            </span>
                        </td>
                    @endif
                </tr>
            @endforeach
        </x-ui.data-table>

        @if ($rows->hasPages())
            <div class="mt-4">
                <flux:pagination :paginator="$rows" class="text-secondary-medium! dark:text-ink-muted!" scroll-to="#hr-settings-heading" />
            </div>
        @endif
    @endif

    <x-ui.modal
        wire:model="showFormModal"
        size="sm"
        :title="$editingId === null ? __('hr_settings.add_to', ['table' => $tabLabel]) : __('hr_settings.edit_in', ['table' => $tabLabel])"
        submit="save"
        close="closeFormModal"
        required-note
    >
        @if ($showFormModal)
            <div class="grid gap-y-3">
                @foreach ($fields as $field => $spec)
                    @php
                        $label = __('hr_settings.fields.'.$field);
                        $required = in_array('required', $spec['rules'], true);
                        $dir = $spec['dir'] ?? null;
                    @endphp

                    @if ($spec['type'] === 'select')
                        <x-ui.form-select wire:model="form.{{ $field }}" :label="$label" :value="$form[$field] ?? ''" :placeholder="__('common.select')" :required="$required" wire:key="field-{{ $field }}">
                            @foreach ($spec['options'] ?? [] as $option)
                                <option value="{{ $option }}">{{ ReferenceTables::optionLabel($field, $option) }}</option>
                            @endforeach
                        </x-ui.form-select>
                    @elseif ($spec['type'] === 'textarea')
                        <x-ui.form-textarea wire:model="form.{{ $field }}" :label="$label" :required="$required" maxlength="2000" dir="{{ $dir ?? 'auto' }}" wire:key="field-{{ $field }}" />
                    @elseif ($spec['type'] === 'integer' || $spec['type'] === 'decimal')
                        <x-ui.form-input wire:model="form.{{ $field }}" type="number" :label="$label" :required="$required" min="0" step="{{ $spec['type'] === 'decimal' ? '0.01' : '1' }}" inputmode="{{ $spec['type'] === 'decimal' ? 'decimal' : 'numeric' }}" dir="ltr" wire:key="field-{{ $field }}" />
                    @else
                        <x-ui.form-input wire:model="form.{{ $field }}" type="{{ $spec['type'] === 'email' ? 'email' : 'text' }}" :label="$label" :required="$required" maxlength="255" dir="{{ $spec['type'] === 'email' ? 'ltr' : ($dir ?? 'auto') }}" wire:key="field-{{ $field }}" data-test="hr-settings-field-{{ $field }}" />
                    @endif
                @endforeach
            </div>
        @endif

        <x-slot:actions>
            <x-ui.button type="submit" tone="primary" data-test="hr-settings-save">{{ __('common.save_changes') }}</x-ui.button>
        </x-slot:actions>
    </x-ui.modal>

    <x-ui.confirm-modal
        wire:model="showDeleteModal"
        close="cancelDelete"
        :title="__('hr_settings.delete_title', ['name' => $deletingName])"
        :message="__('hr_settings.delete_message')"
        confirm="delete"
        :confirm-label="__('common.delete')"
        confirm-test="hr-settings-confirm-delete"
    />
</div>
