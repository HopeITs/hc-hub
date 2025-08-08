<div>
    @php($locale = app()->getLocale())

    <div class="flex justify-between items-center mb-6">
        <livewire:layout.breadcrumbs
            :title="__('general.centers')"
            :crumbs="[
                ['label' => __('general.dashboard')],
                ['label' => __('general.centers')],
            ]"
            :showButton="true"
        />
        <div class="flex items-center gap-2">
            <x-primary-button wire:click="create">
                @lang('general.add_new')
            </x-primary-button>
        </div>
    </div>

    <x-table>
        <x-slot name="headings">
            <x-table.heading
                wire:click="sortBy('id')"
                sortable
                :direction="$sortField === 'id' ? $sortDirection : null"
            >
                ID
            </x-table.heading>

            <x-table.heading wire:click="sortBy('name')" sortable
                             :direction="$sortField === 'name' ? $sortDirection : null">
                @lang('general.center-name')
            </x-table.heading>


            <x-table.heading wire:click="sortBy('city_name')" sortable
                             :direction="$sortField === 'city_name' ? $sortDirection : null">
                @lang('general.center-city')
            </x-table.heading>

            <x-table.heading wire:click="sortBy('project_name')" sortable
                             :direction="$sortField === 'project_name' ? $sortDirection : null">
                @lang('general.required-project')
            </x-table.heading>

            <x-table.heading wire:click="sortBy('address')" sortable
                             :direction="$sortField === 'address' ? $sortDirection : null">
                @lang('general.address')
            </x-table.heading>

            <x-table.heading
                wire:click="sortBy('is_active')"
                sortable
                :direction="$sortField === 'is_active' ? $sortDirection : null"
            >
                @lang('general.is-active')
            </x-table.heading>

            <x-table.heading />
        </x-slot>

        <x-slot name="body">
            @forelse($items as $item)
                <tr wire:key="center-{{ $item->id }}">
                    <x-table.cell class="whitespace-nowrap">
                        {{ $item->id }}
                    </x-table.cell>

                    {{-- Translatable name --}}
                    <x-table.cell class="max-w-64 truncate" title="{{ $item->getTranslation('name', $locale) }}">
                        {{ $item->getTranslation('name', $locale) ?? '-' }}
                    </x-table.cell>

                    {{-- City & Project names (null-safe, translatable) --}}
                    <x-table.cell class="max-w-56 truncate" title="{{ optional($item->city)->getTranslation('name', $locale) }}">
                        {{ optional($item->city)->getTranslation('name', $locale) ?? '-' }}
                    </x-table.cell>

                    <x-table.cell class="max-w-56 truncate" title="{{ optional($item->project)->getTranslation('name', $locale) }}">
                        {{ optional($item->project)->getTranslation('name', $locale) ?? '-' }}
                    </x-table.cell>

                    {{-- Translatable address --}}
                    <x-table.cell class="max-w-72 truncate" title="{{ $item->getTranslation('address', $locale) }}">
                        {{ $item->getTranslation('address', $locale) ?? '-' }}
                    </x-table.cell>

                    {{-- Active toggle (same as Required Positions) --}}
                    <x-table.cell-status
                        :active="$item->is_active"
                        :model-id="$item->id"
                        wire-event="confirmToggleItemStatus"
                    />

                    <x-table.cell class="whitespace-nowrap">
                        <x-table.edit-btn wire:click="edit({{ $item->id }})" />
                        <x-table.delete-btn :modelId="$item->id" />
                    </x-table.cell>
                </tr>
            @empty
                <x-table.no-data />
            @endforelse
        </x-slot>

        <x-slot name="pagination">
            {{ $items->onEachSide(1)->links() }}
        </x-slot>
    </x-table>

    {{-- Modal --}}
    <x-modal.dialog id="showModal" :title="$modalTitle" wire:model.defer="showModal" wire:ignore.self>
        <form wire:submit.prevent="save" class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
                {{-- Name (EN / AR) --}}
                <div>
                    <x-forms.input-label for="name_en">@lang('general.center-name') (EN)</x-forms.input-label>
                    <x-forms.input id="name_en" type="text" wire:model.defer="item.name.en" />
                    <x-error-span>{{ $errors->first('item.name.en') }}</x-error-span>
                </div>
                <div>
                    <x-forms.input-label for="name_ar">@lang('general.center-name') (AR)</x-forms.input-label>
                    <x-forms.input id="name_ar" type="text" wire:model.defer="item.name.ar" dir="rtl" />
                    <x-error-span>{{ $errors->first('item.name.ar') }}</x-error-span>
                </div>

                {{-- City --}}
                <div>
                    <x-forms.input-label for="city_id">@lang('general.city')</x-forms.input-label>
                    <x-forms.select-input id="city_id" wire:model="item.city_id">
                        <option value="">{{ __('general.select') }}</option>
                        @foreach($cities as $city)
                            <option value="{{ $city->id }}">
                                {{ $city->getTranslation('name', $locale) }}
                            </option>
                        @endforeach
                    </x-forms.select-input>
                    <x-error-span>{{ $errors->first('item.city_id') }}</x-error-span>
                </div>

                {{-- Project --}}
                <div>
                    <x-forms.input-label for="project_id">@lang('general.required-project')</x-forms.input-label>
                    <x-forms.select-input id="project_id" wire:model="item.project_id">
                        <option value="">{{ __('general.select') }}</option>
                        @foreach($projects as $project)
                            <option value="{{ $project->id }}">
                                {{ $project->getTranslation('name', $locale) }}
                            </option>
                        @endforeach
                    </x-forms.select-input>
                    <x-error-span>{{ $errors->first('item.project_id') }}</x-error-span>
                </div>

                {{-- Address (EN / AR) --}}
                <div class="col-span-2">
                    <x-forms.input-label for="address_en">@lang('general.address') (EN)</x-forms.input-label>
                    <x-forms.textarea id="address_en" wire:model.defer="item.address.en" rows="2" />
                    <x-error-span>{{ $errors->first('item.address.en') }}</x-error-span>
                </div>
                <div class="col-span-2">
                    <x-forms.input-label for="address_ar">@lang('general.address') (AR)</x-forms.input-label>
                    <x-forms.textarea id="address_ar" wire:model.defer="item.address.ar" rows="2" dir="rtl" />
                    <x-error-span>{{ $errors->first('item.address.ar') }}</x-error-span>
                </div>
            </div>

            <div class="flex justify-end gap-2">
                <x-primary-button type="submit" class="!w-auto">
                    @lang('general.save')
                </x-primary-button>
                <x-modal.cancel-btn />
            </div>
        </form>
    </x-modal.dialog>
</div>
