<div>
    @php($locale = app()->getLocale())

    <div class="flex justify-between items-center mb-6">
        <livewire:layout.breadcrumbs
            :title="__('general.centers')"
            :crumbs="[
                ['label' =>__('general.projects-management')],
                ['label' => __('general.centers'),   'route' => 'centers.index'],
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

            <x-table.heading>
                @lang('general.positions')
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
                    <x-table.cell class="max-w-56 truncate"
                                  title="{{ optional($item->city)->getTranslation('name', $locale) }}">
                        {{ optional($item->city)->getTranslation('name', $locale) ?? '-' }}
                    </x-table.cell>

                    <x-table.cell class="max-w-56 truncate"
                                  title="{{ optional($item->project)->getTranslation('name', $locale) }}">
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
                        <a
                            href="{{ route('centers.positions', ['center' => $item->id, 'city' => $item->city_id]) }}"
                            @class([
                                // base styles
                                'inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full text-xs md:text-sm font-medium
                                 border shadow-sm transition focus:outline-none focus:ring-2 focus:ring-offset-1 focus:ring-primary/40',

                                // active vs inactive styles
                                'bg-primary/10 text-primary border-primary/20 hover:bg-primary/15 hover:border-primary/30' => $item->is_active,
                                'bg-white text-gray-700 border-gray-200 hover:bg-gray-50 hover:border-gray-300' => ! $item->is_active,
                            ])
                            title="@lang('general.positions')"
                        >
                            <i class="ti ti-briefcase text-base"></i>
                            <span>@lang('general.positions')</span>

                            @if(isset($item->positions_count))
                                <span class="inline-flex items-center justify-center min-w-5 h-5 px-1.5 rounded-full
                         text-[11px] md:text-xs bg-current/10">
                {{ $item->positions_count }}
            </span>
                            @endif
                        </a>
                    </x-table.cell>


                    <x-table.cell class="whitespace-nowrap">
                        <x-table.edit-btn wire:click="edit({{ $item->id }})"/>
                        <x-table.delete-btn :modelId="$item->id"/>
                    </x-table.cell>
                </tr>
            @empty
                <x-table.no-data/>
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
                @foreach(config('app.supported_locales') as $locale)
                    <div>
                        <x-forms.input-label for="name_{{ $locale }}">@lang('general.center-name')
                            ({{ strtoupper($locale) }})
                        </x-forms.input-label>
                        <x-forms.input id="name_{{ $locale }}" type="text" wire:model.defer="item.name.{{ $locale }}"/>
                        <x-error-span>{{ $errors->first('item.name.' . $locale) }}</x-error-span>
                    </div>
                @endforeach
                {{-- City --}}
                <div>
                    <x-forms.input-label for="city_id">{{trans_choice('general.city',1)}}</x-forms.input-label>
                    <x-forms.select-input id="city_id" wire:model="item.city_id">
                        <option value="">{{ __('general.select') }}</option>
                        @foreach($cities as $city)
                            <option value="{{ $city->id }}">
                                {{ $city->name }}
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
                                {{ $project->name }}
                            </option>
                        @endforeach
                    </x-forms.select-input>
                    <x-error-span>{{ $errors->first('item.project_id') }}</x-error-span>
                </div>


                @foreach(config('app.supported_locales') as $locale)
                    <div class="col-span-2">
                        <x-forms.input-label for="address_{{ $locale }}">@lang('general.address')
                            ({{ strtoupper($locale) }})
                        </x-forms.input-label>
                        <x-forms.textarea id="address_{{ $locale }}" wire:model.defer="item.address.{{ $locale }}"
                                          rows="2"/>
                        <x-error-span>{{ $errors->first('item.address.' . $locale) }}</x-error-span>
                    </div>
                @endforeach
            </div>

            <div class="flex justify-end gap-2">
                <x-primary-button type="submit" class="!w-auto">
                    @lang('general.save')
                </x-primary-button>
                <x-modal.cancel-btn/>
            </div>
        </form>
    </x-modal.dialog>
</div>
