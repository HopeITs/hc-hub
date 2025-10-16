<div>
    <div class="flex justify-between items-center mb-6">
        <livewire:layout.breadcrumbs
            :title="__('general.my-leaves')"
            :crumbs="[
                ['label' => __('general.holidays-and-leaves')],

            ]"
            :showButton="true"
        />
        <div>
            <x-primary-button :href="route('employees.create')">
                @lang('general.add-new')
            </x-primary-button>
        </div>
    </div>
    <div class="grid grid-cols-{{$availableLeaves->count()}} gap-4 mb-6">
        @forelse($availableLeaves as $availableLeave)
            <div class="flex flex-col gap-2 rounded-2xl shadow-md p-4">
                <h3 class="font-bold text-secondary-dark">{{$availableLeave->leaveType->name}}</h3>
                <span class=" flex justify-between px-2 py-1 text-secondary-dark"><span
                        class="font-semibold text-secondary-medium text-secondary-dark me-2">Total:</span>{{$availableLeave->deserved}}</span>
                <span class="rounded-full px-2 py-1 bg-gray-200 text-secondary-dark flex justify-between"><span
                        class="font-semibold text-secondary-medium me-2">Remaining:</span>{{ $availableLeave->deserved - $availableLeave->used}}</span>
            </div>
        @empty
            <div class="rounded-2xl shadow-md p-4">
                No Leaves available for you
            </div>

        @endforelse
    </div>

    <livewire:employee.leave.leave-request-table/>
</div>
