<div>
    <div class="flex justify-between">
        <livewire:layout.breadcrumbs
            :title="trans_choice('general.job-level', 2)"
            :crumbs="[
                 ['label' => __('general.project-basics')],
                ['label' => trans_choice('general.job-level', 2)],
            ]"
            :showButton="true"
        />

        <div>
            @activerole('HR','super-admin')
            <x-primary-button wire:click="create">
                @lang('general.add-new')
            </x-primary-button>
            @endactiverole
        </div>
    </div>

    <x-table>
        <x-slot name="headings">
            <x-table.heading
                sortable
                wire:click="sortBy('id')"
                :direction="$sortField === 'id' ? $sortDirection : null"
            >ID</x-table.heading>

            <x-table.heading
                sortable
                wire:click="sortBy('name_ar')"
                :direction="$sortField === 'name_ar' ? $sortDirection : null"
            >@lang('general.name')(AR)</x-table.heading>

            <x-table.heading
                sortable
                wire:click="sortBy('name_en')"
                :direction="$sortField === 'name_en' ? $sortDirection : null"
            >@lang('general.name')(EN)</x-table.heading>

            <x-table.heading
                sortable
                wire:click="sortBy('grade')"
                :direction="$sortField === 'grade' ? $sortDirection : null"
            ><div class="ltr:pl-12 rtl:pr-12">@lang('general.grade')</div></x-table.heading>

            <x-table.heading
                sortable
                wire:click="sortBy('email')"
                :direction="$sortField === 'email' ? $sortDirection : null"
            >@lang('general.email')</x-table.heading>

            <x-table.heading/>
        </x-slot>

        <x-slot name="body">
            @forelse($items as $item)
                <tr>
                    <x-table.cell>{{ $item->id }}</x-table.cell>
                    <x-table.cell>{{ $item->name_ar }}</x-table.cell>
                    <x-table.cell>{{ $item->name_en }}</x-table.cell>
                    <x-table.cell><div class="text-center">{{ $item->grade }}</div></x-table.cell>
                    <x-table.cell>{{ $item->email ?? '-' }}</x-table.cell>
                    <x-table.cell>
                        <x-table.edit-btn wire:click="edit({{ $item->id }})"></x-table.edit-btn>
{{--                        <x-table.delete-btn :modelId="$item->id"/>--}}
                    </x-table.cell>
                </tr>
            @empty
                <x-table.no-data/>
            @endforelse
        </x-slot>
        <x-slot name="pagination">
            {{ $items->links() }}
        </x-slot>
    </x-table>

    <x-modal.dialog id="showModal" :title="$modalTitle" wire:model.defer="showModal" wire:ignore.self>
        <form class="space-y-4" wire:submit.prevent="save">
            <div class="grid grid-cols-2 gap-4">
                <div class="col-span-1">
                    <x-forms.input-label for="name_ar">@lang('general.name') (AR)</x-forms.input-label>
                    <x-forms.input type="text" id="name_ar" wire:model.defer="item.name_ar"/>
                    <x-error-span>{{ $errors->first('item.name_ar') }}</x-error-span>
                </div>
                <div class="col-span-1">
                    <x-forms.input-label for="name_en">@lang('general.name') (EN)</x-forms.input-label>
                    <x-forms.input type="text" id="name_en" wire:model.defer="item.name_en"/>
                    <x-error-span>{{ $errors->first('item.name_en') }}</x-error-span>
                </div>

                <div class="col-span-2">
                    <x-forms.input-label for="description">@lang('general.description')</x-forms.input-label>
                    <x-forms.input type="text" id="description" wire:model.defer="item.description"/>
                    <x-error-span>{{ $errors->first('item.description') }}</x-error-span>
                </div>

                <div class="col-span-1">
                    <x-forms.input-label for="grade">@lang('general.grade')</x-forms.input-label>
                    <x-forms.input type="number" id="grade" wire:model.defer="item.grade"/>
                    <x-error-span>{{ $errors->first('item.grade') }}</x-error-span>
                </div>

                <div class="col-span-1">
                    <x-forms.input-label for="email">@lang('general.email')</x-forms.input-label>
                    <x-forms.input type="email" id="email" wire:model.defer="item.email"/>
                    <x-error-span>{{ $errors->first('item.email') }}</x-error-span>
                </div>
            </div>

            <div class="flex justify-end space-x-2 pt-4">
                <x-primary-button type="submit" class="!w-auto">@lang('general.save')</x-primary-button>
                <x-modal.cancel-btn/>
            </div>
        </form>
    </x-modal.dialog>
</div>
