@php
    $title = __('Course Categories');
@endphp
<div>
    <div class="flex justify-between">
        <livewire:layout.breadcrumbs :title="$title" :crumbs="[['label' => __('general.project-basics')], ['label' => $title]]" :showButton="true" />
        <div>
            <x-primary-button wire:click="create" :showSpinner="false">
                @lang('general.add-new')
            </x-primary-button>
        </div>
    </div>

    <x-table>
        <x-slot name="headings">
            <x-table.heading wire:click="sortBy('id')" sortable :direction="$sortField === 'id' ? $sortDirection : null">
                ID
            </x-table.heading>

            <x-table.heading wire:click="sortBy('name_en')" sortable :direction="$sortField === 'name_en' ? $sortDirection : null">
                {{ __('Name (EN)') }}
            </x-table.heading>

            <x-table.heading>
                {{ __('Name (AR)') }}
            </x-table.heading>

            <x-table.heading>
                {{ trans_choice('general.course', 2) }}
            </x-table.heading>

            <x-table.heading />
        </x-slot>

        <x-slot name="body">
            @forelse($items as $item)
                <tr>
                    <x-table.cell>{{ $item->id }}</x-table.cell>
                    <x-table.cell>{{ $item->name_en }}</x-table.cell>
                    <x-table.cell>{{ $item->name_ar }}</x-table.cell>
                    <x-table.cell>{{ $item->courses_count }}</x-table.cell>
                    <x-table.cell>
                        <x-table.edit-btn wire:click="edit({{ $item->id }})" />
                        <x-table.delete-btn wire:click="confirmDelete({{ $item->id }})" :modelId="$item->id" />
                    </x-table.cell>
                </tr>
            @empty
                <x-table.no-data />
            @endforelse
        </x-slot>

        <x-slot name="pagination">
            {{ $items->links() }}
        </x-slot>
    </x-table>

    <x-modal.dialog id="showModal" :title="$modalTitle" wire:model.defer="showModal" wire:ignore.self>
        <form wire:submit.prevent="save" class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <x-forms.input-label for="name_en">{{ __('Name (EN)') }}</x-forms.input-label>
                    <x-forms.input type="text" wire:model="item.name_en" id="name_en" />
                    <x-error-span>{{ $errors->first('item.name_en') }}</x-error-span>
                </div>
                <div>
                    <x-forms.input-label for="name_ar">{{ __('Name (AR)') }}</x-forms.input-label>
                    <x-forms.input type="text" wire:model="item.name_ar" id="name_ar" />
                    <x-error-span>{{ $errors->first('item.name_ar') }}</x-error-span>
                </div>
            </div>

            <div class="flex justify-end space-x-2">
                <x-primary-button type="submit" class="!w-auto">@lang('general.save')</x-primary-button>
                <x-modal.cancel-btn />
            </div>
        </form>
    </x-modal.dialog>
</div>
