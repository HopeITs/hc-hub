@php
    use App\Enums\MaritalStatus;
@endphp

<div>
    <div class="flex justify-between">
        <livewire:layout.breadcrumbs :title="trans_choice('general.employee', 2)" :crumbs="[['label' => __('general.project-basics')], ['label' => trans_choice('general.employee', 2)]]" :showButton="true" />
        <div>
            <x-primary-link href="{{ route('employees.form') }}">@lang('general.add_new')</x-primary-link>
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
                            src="https://flowbite.com/docs/images/people/profile-picture-5.jpg" alt="{{ $item->name }}">
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
                        <x-table.edit-btn :href="route('employees.edit', ['employee' => $item->id])">
                            Edit
                        </x-table.edit-btn>
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
</div>
