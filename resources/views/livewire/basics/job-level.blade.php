<div>
    <div class="flex justify-between">
        <livewire:layout.breadcrumbs
            :title="trans_choice('general.job-level', 2)"
            :crumbs="[
                ['label' => __('general.dashboard')],
                ['label' => trans_choice('general.job-level', 2)],
            ]"
            :showButton="true"
        />
        <div>
            <x-primary-button wire:click="create">
                @lang('general.add_new')
            </x-primary-button>
        </div>
    </div>

    <x-table>
        <x-slot name="headings">
            <x-table.heading>ID</x-table.heading>
            <x-table.heading sortable>@lang('general.name')(AR)</x-table.heading>
            <x-table.heading sortable>@lang('general.name')(EN)</x-table.heading>
            <x-table.heading sortable>@lang('general.grade')</x-table.heading>
            <x-table.heading>@lang('general.email')</x-table.heading>
            <x-table.heading/>
        </x-slot>
        <x-slot name="body">
            @forelse($items as $item)
                <tr>
                    <x-table.cell>{{ $item->id }}</x-table.cell>
                    <x-table.cell>{{ $item->getTranslation('name','ar') }}</x-table.cell>
                    <x-table.cell>{{ $item->getTranslation('name','en') }}</x-table.cell>
                    <x-table.cell>{{ $item->grade }}</x-table.cell>
                    <x-table.cell>{{ $item->email }}</x-table.cell>
                    <x-table.cell>
                        <x-table.edit-btn wire:click="edit({{ $item->id }})"></x-table.edit-btn>
                        <x-table.delete-btn :modelId="$item->id"/>
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
                @foreach(config('app.supported_locales') as $locale)
                    <div class="col-span-1">
                        <x-forms.input-label for="name_{{ $locale }}">@lang('general.name') ({{ strtoupper($locale) }})</x-forms.input-label>
                        <x-forms.input type="text" id="name_{{ $locale }}" wire:model.defer="item.name.{{ $locale }}"/>
                        <x-error-span>{{ $errors->first('item.name.' . $locale) }}</x-error-span>
                    </div>
                @endforeach

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
