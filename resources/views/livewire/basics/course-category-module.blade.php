<div>
    @if (!$isCreatePage)
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

        @if($showFormModal)
            <div class="fixed inset-0 z-50 flex items-center justify-center">
                <div class="absolute inset-0 bg-black/50" wire:click="backToIndex"></div>
                <div class="relative bg-white rounded-lg shadow max-w-2xl w-full mx-4">
                    <div class="flex items-center justify-between border-b px-6 py-4">
                        <h2 class="text-lg font-semibold">
                            {{ $editingId ? __('general.edit') : __('general.add-new') }}
                        </h2>
                        <button type="button" class="text-gray-500 hover:text-gray-800" wire:click="backToIndex">&times;</button>
                    </div>

                    <div class="p-6">
                        <form wire:submit.prevent="save" class="space-y-4">
                            <div class="grid grid-cols-2 gap-4">
                                <div class="col-span-2 sm:col-span-1">
                                    <x-forms.input-label for="name_en">{{ __('Name (EN)') }}</x-forms.input-label>
                                    <x-forms.input id="name_en" type="text" wire:model.live="name_en" />
                                    <x-error-span>{{ $errors->first('name_en') }}</x-error-span>
                                </div>

                                <div class="col-span-2 sm:col-span-1">
                                    <x-forms.input-label for="name_ar">{{ __('Name (AR)') }}</x-forms.input-label>
                                    <x-forms.input id="name_ar" type="text" wire:model.live="name_ar" />
                                    <x-error-span>{{ $errors->first('name_ar') }}</x-error-span>
                                </div>
                            </div>

                            <div class="flex justify-end gap-2">
                                <x-primary-button type="submit">@lang('general.save')</x-primary-button>
                                <x-secondary-button type="button" wire:click="backToIndex">
                                    @lang('general.cancel')
                                </x-secondary-button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif
    @else
        <div class="flex justify-between items-center mb-6">
            <livewire:layout.breadcrumbs
                :title="$editingId ? __('general.edit') : __('general.add-new')"
                :crumbs="[['label' => __('Course Categories')]]"
                :showButton="false" />
            <x-secondary-button wire:click="backToIndex">
                {{ __('general.back') }}
            </x-secondary-button>
        </div>

        <div class="mt-0 p-6 bg-white shadow rounded-lg">
            <h2 class="text-xl font-semibold mb-4">
                {{ $editingId ? __('general.edit') : __('general.add-new') }}
            </h2>

            <form wire:submit.prevent="save" class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div class="col-span-2 sm:col-span-1">
                        <x-forms.input-label for="name_en">{{ __('Name (EN)') }}</x-forms.input-label>
                        <x-forms.input id="name_en" type="text" wire:model.live="name_en" />
                        <x-error-span>{{ $errors->first('name_en') }}</x-error-span>
                    </div>

                    <div class="col-span-2 sm:col-span-1">
                        <x-forms.input-label for="name_ar">{{ __('Name (AR)') }}</x-forms.input-label>
                        <x-forms.input id="name_ar" type="text" wire:model.live="name_ar" />
                        <x-error-span>{{ $errors->first('name_ar') }}</x-error-span>
                    </div>
                </div>

                <div class="flex justify-end gap-2">
                    <x-primary-button type="submit">@lang('general.save')</x-primary-button>
                    <x-secondary-button type="button" wire:click="backToIndex">
                        @lang('general.cancel')
                    </x-secondary-button>
                </div>
            </form>
        </div>
    @endif
</div>
