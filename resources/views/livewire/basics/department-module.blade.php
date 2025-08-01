<div>
    {{-- Breadcrumb --}}
    <livewire:layout.breadcrumbs
        :title="trans_choice('general.department', 2)"
        :crumbs="[
            ['label' => __('general.dashboard')],
            ['label' => trans_choice('general.department', 2)],
        ]"
        :showButton="true"
    />

    {{-- Add New Button --}}
    <div class="flex justify-end mb-4">
        <x-primary-button wire:click="create">
            @lang('general.add_new')
        </x-primary-button>
    </div>

    {{-- Data Table --}}
    <x-table>
        <x-slot name="headings">
            <x-table.heading>ID</x-table.heading>
            <x-table.heading>@lang('general.department-name')</x-table.heading>
            <x-table.heading>@lang('general.department-description')</x-table.heading>
            <x-table.heading>@lang('general.department-email')</x-table.heading>
            <x-table.heading />
        </x-slot>

        <x-slot name="body">
            @forelse($departments as $item)
                <tr class="text-sm hover:bg-gray-50 border-b">
                    <x-table.cell class="text-center w-12">
                        {{ $item->id }}
                    </x-table.cell>

                    <x-table.cell class="text-right rtl:text-right ltr:text-left font-medium whitespace-nowrap">
                        {{ $item->name }}
                    </x-table.cell>

                    <x-table.cell class="text-right rtl:text-right ltr:text-left whitespace-normal break-words max-w-[350px]">
                        {{ $item->getTranslation('description', app()->getLocale()) ?: '-' }}
                    </x-table.cell>

                    <x-table.cell class="text-center whitespace-nowrap">
                        {{ $item->email ?? '-' }}
                    </x-table.cell>

                    <x-table.cell class="text-center space-x-2">
                        <x-table.edit-btn wire:click="edit({{ $item->id }})" />
                        <x-table.delete-btn :modelId="$item->id" />
                    </x-table.cell>
                </tr>
            @empty
                <x-table.no-data />
            @endforelse
        </x-slot>

        <x-slot name="pagination">
            <div class="px-4 py-2">
                {{ $departments->links() }}
            </div>
        </x-slot>
    </x-table>

    {{-- Modal Dialog --}}
    <x-modal.dialog id="showModal" :title="$modalTitle" wire:model.defer="showModal" wire:ignore.self>
        <form wire:submit.prevent="save" class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
                {{-- Localized Name Inputs --}}
                @foreach(config('app.supported_locales') as $locale)
                    <div class="col-span-1">
                        <x-forms.input-label for="name_{{ $locale }}">
                            @lang('general.department-name') ({{ strtoupper($locale) }})
                        </x-forms.input-label>
                        <x-forms.input
                            id="name_{{ $locale }}"
                            type="text"
                            wire:model.defer="item.name.{{ $locale }}"
                        />
                        <x-error-span>{{ $errors->first('item.name.' . $locale) }}</x-error-span>
                    </div>
                @endforeach

                {{-- Localized Description Inputs --}}
                @foreach(config('app.supported_locales') as $locale)
                    <div class="col-span-1">
                        <x-forms.input-label for="description_{{ $locale }}">
                            @lang('general.department-description') ({{ strtoupper($locale) }})
                        </x-forms.input-label>
                        <x-forms.input
                            id="description_{{ $locale }}"
                            type="text"
                            wire:model.defer="item.description.{{ $locale }}"
                        />
                        <x-error-span>{{ $errors->first('item.description.' . $locale) }}</x-error-span>
                    </div>
                @endforeach

                {{-- Email --}}
                <div class="col-span-2">
                    <x-forms.input-label for="email">@lang('general.department-email')</x-forms.input-label>
                    <x-forms.input
                        id="email"
                        type="email"
                        wire:model.defer="item.email"
                    />
                    <x-error-span>{{ $errors->first('item.email') }}</x-error-span>
                </div>
            </div>

            <div class="flex justify-end space-x-2">
                <x-primary-button type="submit">@lang('general.save')</x-primary-button>
                <x-primary-button type="button" wire:click="cancel" class="!bg-secondary-light">
                    @lang('general.cancel')
                </x-primary-button>
            </div>
        </form>
    </x-modal.dialog>
</div>
