<div>
    <div class="flex justify-between">
        <livewire:layout.breadcrumbs
            :title="trans_choice('general.required-positions' , 1)"
            :crumbs="[
                ['label' => __('general.projects-management')],
                ['label' => trans_choice('general.required-positions' , 1)],
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
            <x-table.heading
                wire:click="sortBy('id')"
                sortable
                :direction="$sortField === 'id' ? $sortDirection : null"
            >ID</x-table.heading>

            <x-table.heading
                wire:click="sortBy('title')"
                sortable
                :direction="$sortField === 'title' ? $sortDirection : null"
            >@lang('general.job-title')</x-table.heading>

            <x-table.heading
                wire:click="sortBy('program_name')"
                sortable
                :direction="$sortField === 'program_name' ? $sortDirection : null"
            >@lang('general.required-project')</x-table.heading>

            <x-table.heading
                wire:click="sortBy('is_active')"
                sortable
                :direction="$sortField === 'is_active' ? $sortDirection : null"
            >@lang('general.is-active')</x-table.heading>

            <x-table.heading />
        </x-slot>

        <x-slot name="body">
            @forelse($items as $item)
                <tr>
                    <x-table.cell>{{ $item->id }}</x-table.cell>
                    <x-table.cell>{{ $item->title }}</x-table.cell>

                    <x-table.cell>
                        {{-- CORRECTED: Access name directly --}}
                        {{ optional($item->program)->name ?? '-' }}
                    </x-table.cell>

                    <x-table.cell-status
                        :active="$item->is_active"
                        :model-id="$item->id"
                        wire-event="confirmToggleItemStatus"
                    />

                    <x-table.cell>
                        <x-table.edit-btn wire:click="edit({{ $item->id }})" />
                        <x-table.delete-btn :modelId="$item->id" />
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

    {{-- Modal --}}
    <x-modal.dialog id="showModal" :title="$modalTitle" wire:model.defer="showModal" wire:ignore.self>
        <form wire:submit.prevent="save" class="space-y-4">
            <div class="grid grid-cols-2 space-x-2 space-y-4">
                {{-- Job Title --}}
                <div class="col-span-1">
                    <x-forms.input-label for="title">@lang('general.job-title')</x-forms.input-label>
                    <x-forms.input type="text" id="title" wire:model="item.title" />
                    <x-error-span>{{ $errors->first('item.title') }}</x-error-span>
                </div>

                {{-- Project Selection --}}
                <div class="col-span-1">
                    <x-forms.input-label for="program_id">@lang('general.required-program')</x-forms.input-label>
                    <x-forms.select-input id="program_id" wire:model="item.program_id">
                        <option value="">@lang('general.select')</option>
                        @foreach(\App\Models\Program::orderBy('name')->get() as $program)
                            <option value="{{ $program->id }}">
                                {{-- CORRECTED: Access name directly --}}
                                {{ $program->name }}
                            </option>
                        @endforeach
                    </x-forms.select-input>
                    <x-error-span>{{ $errors->first('item.program_id') }}</x-error-span>
                </div>

                <div class="col-span-2">
                    <x-forms.input-label for="qualifications">@lang('general.qualifications')</x-forms.input-label>
                    <x-forms.textarea id="qualifications" wire:model.defer="item.qualifications" rows="4" />
                    <x-error-span>{{ $errors->first('item.qualifications') }}</x-error-span>
                </div>

            </div>

            {{-- Buttons --}}
            <div class="flex justify-end space-x-2">
                <x-primary-button type="submit" class="!w-auto">@lang('general.save')</x-primary-button>
                <x-modal.cancel-btn/>
            </div>
        </form>
    </x-modal.dialog>
</div>
