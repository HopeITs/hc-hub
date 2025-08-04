<div>
    {{-- Header --}}
    <div class="flex justify-between">
        <livewire:layout.breadcrumbs
                :title="trans_choice('general.department', 2)"
                :crumbs="[
                ['label' => __('general.project-basics')],
                ['label' => trans_choice('general.department', 2)],
            ]"
                :showButton="true"
        />
        <div>
            <x-primary-button wire:click="create">
                @lang('general.add_new')
            </x-primary-button>
        </div>
    </div>

    {{-- Table --}}
    <x-table>
        <x-slot name="headings">
            <x-table.heading>ID</x-table.heading>
            <x-table.heading sortable>@lang('general.department-name')</x-table.heading>
            <x-table.heading>@lang('general.department-description')</x-table.heading>
            <x-table.heading sortable>@lang('general.department-email')</x-table.heading>
            <x-table.heading/>
        </x-slot>

        <x-slot name="body">
            @forelse($departments as $item)
                <tr>
                    {{-- ID --}}
                    <x-table.cell class="w-1/12">{{ $item->id }}</x-table.cell>

                    {{-- Name --}}
                    <x-table.cell class="w-2/12">
                        {{ $item->name }}
                    </x-table.cell>

                    {{-- Description --}}
                    <x-table.cell class="w-6/12"  title="{{ $item->getTranslation('description', app()->getLocale()) }}">
                        <span class="line-clamp-1">
                            {{ $item->getTranslation('description', app()->getLocale()) ?: '-' }}
                        </span>
                    </x-table.cell>

                    {{-- Email --}}
                    <x-table.cell class="w-2/12">
                        {{ $item->email ?? '-' }}
                    </x-table.cell>

                    {{-- Actions --}}
                    <x-table.cell class=" whitespace-nowrap text-center">
                        <div class="inline-flex items-center justify-center space-x-1 rtl:space-x-reverse">
                            <x-table.edit-btn wire:click="edit({{ $item->id }})" />
                            <x-table.delete-btn :modelId="$item->id" />
                        </div>
                    </x-table.cell>
                </tr>
            @empty
                <x-table.no-data/>
            @endforelse
        </x-slot>




        <x-slot name="pagination">
            {{ $departments->links() }}
        </x-slot>
    </x-table>

    {{-- Modal --}}
    <x-modal.dialog id="showModal" :title="$modalTitle" wire:model.defer="showModal" wire:ignore.self>
        <form wire:submit.prevent="save" class="space-y-4">
            <div class="grid grid-cols-2 space-x-2 space-y-4">
                {{-- Name Input  --}}
                <div class="col-span-2">
                    <x-forms.input-label for="name_en">
                        @lang('general.department-name') (EN)
                    </x-forms.input-label>
                    <x-forms.input type="text" id="name_en" wire:model="item.name.en" />
                    <x-error-span>{{ $errors->first('item.name.en') }}</x-error-span>
                </div>


                {{-- Description Inputs --}}
                @foreach(config('app.supported_locales') as $locale)
                    <div class="col-span-1">
                        <x-forms.input-label for="description_{{ $locale }}">
                            @lang('general.department-description') ({{ strtoupper($locale) }})
                        </x-forms.input-label>
                        <x-forms.input type="text" id="description_{{ $locale }}" wire:model="item.description.{{ $locale }}" />
                        <x-error-span>{{ $errors->first('item.description.' . $locale) }}</x-error-span>
                    </div>
                @endforeach

                {{-- Email --}}
                <div class="col-span-2">
                    <x-forms.input-label for="email">@lang('general.department-email')</x-forms.input-label>
                    <x-forms.input type="email" id="email" wire:model="item.email" />
                    <x-error-span>{{ $errors->first('item.email') }}</x-error-span>
                </div>
            </div>

            <div class="flex justify-end space-x-2">
                <x-primary-button type="submit" class="!w-auto">@lang('general.save')</x-primary-button>
                <x-primary-button type="button" wire:click="cancel" class="!bg-secondary-light !w-auto">
                    @lang('general.cancel')
                </x-primary-button>
            </div>
        </form>
    </x-modal.dialog>
</div>
