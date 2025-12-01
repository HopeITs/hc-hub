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

        @if(auth()->user()?->hasRole('HR-SYR') || auth()->user()?->hasRole('HR-ALP'))
            <div>
                <x-primary-button wire:click="create">
                    @lang('general.add-new')
                </x-primary-button>
            </div>
        @endif
    </div>

    {{-- Table --}}
    <x-table>
        <x-slot name="headings">
            <x-table.heading
                sortable
                wire:click="sortBy('id')"
                :direction="$sortField === 'id' ? $sortDirection : null">
                ID
            </x-table.heading>

            <x-table.heading
                sortable
                wire:click="sortBy('name')"
                :direction="$sortField === 'name' ? $sortDirection : null">
                @lang('general.department-name')
            </x-table.heading>

            <x-table.heading>@lang('general.code')</x-table.heading>
            <x-table.heading>@lang('general.department-description')</x-table.heading>

            <x-table.heading
                sortable
                wire:click="sortBy('email')"
                :direction="$sortField === 'email' ? $sortDirection : null">
                @lang('general.department-email')
            </x-table.heading>

            <x-table.heading/>
        </x-slot>


        <x-slot name="body">
            @forelse($departments as $item)
                <tr>
                    {{-- ID --}}
                    <x-table.cell class="w-1/12">{{ $item->id }}</x-table.cell>

                    {{-- Name --}}
                    <x-table.cell class="w-4/12">
                        {{ $item->name }}
                    </x-table.cell>
                    <x-table.cell class="w-1/12">
                        {{ $item->code }}
                    </x-table.cell>

                    {{-- Description --}}
                    <x-table.cell class="w-3/12" title="{{ $item->{'description_' . app()->getLocale()} }}">
                        <span class="line-clamp-1">
                            {{ $item->{'description_' . app()->getLocale()} ?: '-' }}
                        </span>
                    </x-table.cell>

                    {{-- Email --}}
                    <x-table.cell class="w-2/12">
                        {{ $item->email ?? '-' }}
                    </x-table.cell>

                    {{-- Actions --}}
                    <x-table.cell class=" whitespace-nowrap text-center">
                        @if(auth()->user()?->hasRole('HR-SYR') || auth()->user()?->hasRole('HR-ALP'))
                            <div class="inline-flex items-center justify-center space-x-1 rtl:space-x-reverse">
                                <x-table.edit-btn wire:click="edit({{ $item->id }})"/>
                                <x-table.delete-btn :modelId="$item->id"/>
                            </div>
                        @endif
                    </x-table.cell>
                </tr>
            @empty
                <x-table.no-data/>
            @endforelse
        </x-slot>

        <x-slot name="pagination">{{ $departments->links() }}</x-slot>
    </x-table>

    {{-- Modal --}}
    <x-modal.dialog id="showModal" :title="$modalTitle" wire:model.defer="showModal" wire:ignore.self>
        <form wire:submit.prevent="save" class="space-y-4">
            <div class="grid grid-cols-2 space-x-2 space-y-4">
                {{-- Name Input  --}}
                <div class="col-span-1">
                    <x-forms.input-label for="name">
                        @lang('general.department-name')
                    </x-forms.input-label>
                    <x-forms.input type="text" id="name" wire:model="item.name"/>
                    <x-error-span>{{ $errors->first('item.name') }}</x-error-span>
                </div>
                <div class="col-span-1">
                    <x-forms.input-label for="code">
                        @lang('general.code')
                    </x-forms.input-label>
                    <x-forms.input type="text" id="code" wire:model="item.code"/>
                    <x-error-span>{{ $errors->first('item.code') }}</x-error-span>
                </div>

                {{-- Email --}}
                <div class="col-span-2">
                    <x-forms.input-label for="email">@lang('general.department-email')</x-forms.input-label>
                    <x-forms.input type="email" id="email" wire:model="item.email"/>
                    <x-error-span>{{ $errors->first('item.email') }}</x-error-span>
                </div>

                {{-- Description Inputs --}}
                <div class="col-span-2">
                    <x-forms.input-label for="description_en">
                        @lang('general.department-description') (EN)
                    </x-forms.input-label>
                    <x-forms.textarea type="text" id="description_en" wire:model="item.description_en"/>
                    <x-error-span>{{ $errors->first('item.description_en') }}</x-error-span>
                </div>
                <div class="col-span-2">
                    <x-forms.input-label for="description_ar">
                        @lang('general.department-description') (AR)
                    </x-forms.input-label>
                    <x-forms.textarea type="text" id="description_ar" wire:model="item.description_ar"/>
                    <x-error-span>{{ $errors->first('item.description_ar') }}</x-error-span>
                </div>
            </div>

            <div class="flex justify-end space-x-2">
                <x-primary-button type="submit" class="!w-auto">@lang('general.save')</x-primary-button>
                <x-modal.cancel-btn/>
            </div>
        </form>
    </x-modal.dialog>
</div>
