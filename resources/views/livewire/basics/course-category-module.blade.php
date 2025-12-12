<div>
    <div class="flex justify-between items-center mb-6">
        <livewire:layout.breadcrumbs
            :title="__('Course Categories')"
            :crumbs="[['label' => __('Course Categories')]]"
            :showButton="true" />
        <x-primary-button wire:click="create">
            @lang('general.add-new')
        </x-primary-button>
    </div>

    <div class="bg-white shadow rounded-lg">
        <div class="p-4 border-b flex items-center gap-3 flex-wrap">
            <div class="flex-1">
                <x-forms.input
                    placeholder="{{ __('general.search') }}"
                    wire:model.live.debounce.300ms="search" />
            </div>

            <div class="flex items-center gap-2">
                <select wire:model.live="perPage" class="text-sm border-gray-300 rounded-md">
                    <option value="10">10</option>
                    <option value="15">15</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Name (EN)') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Name (AR)') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ trans_choice('general.course', 2) }}</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('general.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-100">
                    @forelse($categories as $cat)
                        <tr wire:key="cat-{{ $cat->id }}">
                            <td class="px-4 py-3 text-sm text-gray-700">{{ $cat->id }}</td>
                            <td class="px-4 py-3 text-sm text-gray-900">{{ $cat->name_en }}</td>
                            <td class="px-4 py-3 text-sm text-gray-900">{{ $cat->name_ar }}</td>
                            <td class="px-4 py-3 text-sm text-gray-900">{{ $cat->courses_count }}</td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end gap-2">
                                    <x-table.edit-btn wire:click="edit({{ $cat->id }})" />
                                    <x-table.delete-btn wire:click="confirmDelete({{ $cat->id }})" :modelId="$cat->id" />
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-6 text-center text-gray-500">
                                {{ __('general.no-results-found') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-4 py-3">
            {{ $categories->links() }}
        </div>
    </div>

    <x-modal.dialog id="showModal" :title="$modalTitle" wire:model.defer="showModal" wire:ignore.self>
        <form wire:submit.prevent="save" class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <div class="col-span-2 sm:col-span-1">
                    <x-forms.input-label for="name_en">{{ __('Name (EN)') }}</x-forms.input-label>
                    <x-forms.input id="name_en" type="text" wire:model="item.name_en" />
                    <x-error-span>{{ $errors->first('item.name_en') }}</x-error-span>
                </div>

                <div class="col-span-2 sm:col-span-1">
                    <x-forms.input-label for="name_ar">{{ __('Name (AR)') }}</x-forms.input-label>
                    <x-forms.input id="name_ar" type="text" wire:model="item.name_ar" />
                    <x-error-span>{{ $errors->first('item.name_ar') }}</x-error-span>
                </div>
            </div>

            <div class="flex justify-end space-x-2">
                <x-primary-button type="submit" class="!w-auto">@lang('general.save')</x-primary-button>
                <x-modal.cancel-btn/>
            </div>
        </form>
    </x-modal.dialog>
</div>
