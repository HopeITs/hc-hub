<div>
    <div class="flex justify-between">
        <livewire:layout.breadcrumbs :title="trans_choice('general.employee', 2)" :crumbs="[['label' => __('general.project-basics')], ['label' => trans_choice('general.employee', 2)]]" :showButton="true" />
        <div>
            <x-primary-button wire:click="create">
                @lang('general.add_new')
            </x-primary-button>
        </div>
    </div>

    <x-table>
        <x-slot name="headings">
            <x-table.heading>ID</x-table.heading>
            <x-table.heading>@lang('general.name')</x-table.heading>
            <x-table.heading>@lang('general.photo')</x-table.heading>
            <x-table.heading>@lang('general.tech_manager')</x-table.heading>
            <x-table.heading>@lang('general.direct_manager')</x-table.heading>
            <x-table.heading>@lang('general.work_email')</x-table.heading>
            <x-table.heading>@lang('general.actual_job')</x-table.heading>
            <x-table.heading>@lang('general.job_level')</x-table.heading>
            <x-table.heading>@lang('general.contract_type')</x-table.heading>
            <x-table.heading>@lang('general.department')</x-table.heading>
            <x-table.heading>@lang('general.status')</x-table.heading>
            <x-table.heading />
        </x-slot>

        <x-slot name="body">
            @forelse($items as $item)
                <tr>
                    <x-table.cell>{{ $item->id }}</x-table.cell>
                    <x-table.cell>{{ $item->name }}</x-table.cell>
                    <x-table.cell>
                        <img class="w-10 h-10 rounded-full"
                            src="https://flowbite.com/docs/images/people/profile-picture-5.jpg"
                            alt="{{ $item->name }}">
                    </x-table.cell>
                    <x-table.cell>{{ $item->techManager?->name ?? '-' }}</x-table.cell>
                    <x-table.cell>{{ $item->directManager?->name ?? '-' }}</x-table.cell>
                    <x-table.cell>{{ $item->work_email }}</x-table.cell>
                    <x-table.cell>{{ $item->actual_job }}</x-table.cell>
                    <x-table.cell>{{ $item->jobLevel?->name ?? '-' }}</x-table.cell>
                    <x-table.cell>{{ $item->contractType?->name ?? '-' }}</x-table.cell>
                    <x-table.cell>{{ $item->department?->name ?? '-' }}</x-table.cell>
                    <x-table.cell-status :active="$item->is_active" />
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

    <x-modal.dialog id="showModal" :title="$modalTitle" wire:model.defer="showModal" wire:ignore.self>
        <form class="space-y-4" wire:submit.prevent="save">
            <div class="grid grid-cols-2 gap-4">
                <!-- Basic Info -->
                <div>
                    <x-forms.input-label for="name">@lang('general.name')</x-forms.input-label>
                    <x-forms.input type="text" id="name" wire:model="item.name" />
                </div>

                <div>
                    <x-forms.input-label for="dob">@lang('general.dob')</x-forms.input-label>
                    <x-forms.input type="date" id="dob" wire:model="item.dob" />
                </div>

                <div>
                    <x-forms.input-label for="years_of_service">@lang('general.years_of_service')</x-forms.input-label>
                    <x-forms.input type="number" id="years_of_service" wire:model="item.years_of_service" />
                </div>

                <div>
                    <x-forms.input-label for="national_number">@lang('general.national_number')</x-forms.input-label>
                    <x-forms.input type="text" id="national_number" wire:model="item.national_number" />
                </div>

                <div>
                    <x-forms.input-label for="civil_registry_office">@lang('general.civil_registry_office')</x-forms.input-label>
                    <x-forms.input type="text" id="civil_registry_office" wire:model="item.civil_registry_office" />
                </div>

                <div>
                    <x-forms.input-label for="civil_registry_number">@lang('general.civil_registry_number')</x-forms.input-label>
                    <x-forms.input type="text" id="civil_registry_number" wire:model="item.civil_registry_number" />
                </div>

                <div>
                    <x-forms.input-label for="place_of_registration">@lang('general.place_of_registration')</x-forms.input-label>
                    <x-forms.input type="text" id="place_of_registration" wire:model="item.place_of_registration" />
                </div>

                <div>
                    <x-forms.input-label for="gender">@lang('general.gender')</x-forms.input-label>
                    <select id="gender" wire:model="item.gender" class="w-full border-gray-300 rounded shadow-sm">
                        <option value="">--</option>
                        <option value="male">@lang('general.male')</option>
                        <option value="female">@lang('general.female')</option>
                    </select>
                </div>

                <!-- Parents -->
                <div>
                    <x-forms.input-label for="father_name">@lang('general.father_name')</x-forms.input-label>
                    <x-forms.input type="text" id="father_name"
                        wire:model="item.father_name.{{ app()->getLocale() }}" />
                </div>

                <div>
                    <x-forms.input-label for="mother_name">@lang('general.mother_name')</x-forms.input-label>
                    <x-forms.input type="text" id="mother_name"
                        wire:model="item.mother_name.{{ app()->getLocale() }}" />
                </div>

                <!-- Contact -->
                <div>
                    <x-forms.input-label for="nationality">@lang('general.nationality')</x-forms.input-label>
                    <x-forms.input type="text" id="nationality" wire:model="item.nationality" />
                </div>

                <div>
                    <x-forms.input-label for="marital_status">@lang('general.marital_status')</x-forms.input-label>
                    <select id="marital_status" wire:model="item.marital_status"
                        class="w-full border-gray-300 rounded shadow-sm">
                        <option value="">--</option>
                        <option value="single">@lang('general.single')</option>
                        <option value="married">@lang('general.married')</option>
                        <option value="divorced">@lang('general.divorced')</option>
                        <option value="widowed">@lang('general.widowed')</option>
                    </select>
                </div>

                <div>
                    <x-forms.input-label for="children">@lang('general.children')</x-forms.input-label>
                    <x-forms.input type="number" id="children" wire:model="item.children" />
                </div>

                <div>
                    <x-forms.input-label for="work_phone">@lang('general.work_phone')</x-forms.input-label>
                    <x-forms.input type="text" id="work_phone" wire:model="item.work_phone" />
                </div>

                <div>
                    <x-forms.input-label for="personal_phone">@lang('general.personal_phone')</x-forms.input-label>
                    <x-forms.input type="text" id="personal_phone" wire:model="item.personal_phone" />
                </div>

                <div>
                    <x-forms.input-label for="personal_email">@lang('general.personal_email')</x-forms.input-label>
                    <x-forms.input type="email" id="personal_email" wire:model="item.personal_email" />
                </div>

                <div>
                    <x-forms.input-label for="work_email">@lang('general.work_email')</x-forms.input-label>
                    <x-forms.input type="email" id="work_email" wire:model="item.work_email" />
                    <x-error-span>{{ $errors->first('item.work_email') }}</x-error-span>
                </div>

                <!-- Others -->
                <div>
                    <x-forms.input-label for="address">@lang('general.address')</x-forms.input-label>
                    <x-forms.textarea id="address" wire:model="item.address.{{ app()->getLocale() }}" />
                </div>

                <div>
                    <x-forms.input-label for="skills">@lang('general.skills')</x-forms.input-label>
                    <x-forms.textarea id="skills" wire:model="item.skills" />
                </div>

                <div>
                    <x-forms.input-label for="interests">@lang('general.interests')</x-forms.input-label>
                    <x-forms.textarea id="interests" wire:model="item.interests" />
                </div>

                <div class="flex items-center space-x-2 mt-2">
                    <input type="checkbox" id="is_active" wire:model="item.is_active"
                        class="rounded border-gray-300 shadow-sm focus:ring-indigo-500" />
                    <label for="is_active" class="text-sm text-gray-700">@lang('general.active')</label>
                </div>

                <!-- Existing Relationship Fields -->
                <div>
                    <x-forms.input-label for="actual_job">@lang('general.actual_job')</x-forms.input-label>
                    <x-forms.input type="text" id="actual_job" wire:model="item.actual_job" />
                </div>

                <div>
                    <x-forms.input-label for="direct_manager_id">@lang('general.direct_manager')</x-forms.input-label>
                    <x-forms.input type="number" id="direct_manager_id" wire:model="item.direct_manager_id" />
                </div>

                <div>
                    <x-forms.input-label for="tech_manager_id">@lang('general.tech_manager')</x-forms.input-label>
                    <x-forms.input type="number" id="tech_manager_id" wire:model="item.tech_manager_id" />
                </div>

                <div>
                    <x-forms.input-label for="job_level_id">@lang('general.job_level')</x-forms.input-label>
                    <x-forms.input type="number" id="job_level_id" wire:model="item.job_level_id" />
                </div>

                <div>
                    <x-forms.input-label for="contract_type_id">@lang('general.contract_type')</x-forms.input-label>
                    <x-forms.input type="number" id="contract_type_id" wire:model="item.contract_type_id" />
                </div>

                <div>
                    <x-forms.input-label for="department_id">@lang('general.department')</x-forms.input-label>
                    <x-forms.input type="number" id="department_id" wire:model="item.department_id" />
                </div>
            </div>

            <div class="flex justify-end space-x-2">
                <x-primary-button type="submit" class="!w-auto">@lang('general.save')</x-primary-button>
                <x-modal.cancel-btn />
            </div>
        </form>
    </x-modal.dialog>
</div>
