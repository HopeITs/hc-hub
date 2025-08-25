<div class="space-y-8" x-data="{ activeTab: 'details' }">

    <livewire:layout.breadcrumbs :title="__('general.projects-management')" :crumbs="[
        ['label' => __('general.projects-management')],
        ['label' => trans_choice('general.project', 2), 'url' => route('projects.index')],
        ['label' => $project->name],
    ]" />

    <div class="flex flex-col md:flex-row md:justify-between md:items-center gap-4">
        <div>
            <div class="flex items-center gap-3">
                <h1 class="text-3xl font-bold text-gray-800">{{ $project->getTranslation('name', app()->getLocale()) }}
                </h1>
                <span
                    class="bg-primary-100 text-primary-800 text-xs font-medium px-2.5 py-0.5 rounded-full">{{ $project->code }}</span>
            </div>
            <p class="text-md text-gray-500 mt-2">
                {{ $project->getTranslation('description', app()->getLocale()) }}
            </p>
        </div>

        <div class="flex items-center space-x-2 flex-shrink-0">
            <x-forms.toggle-input :active="$project->is_active" :model-id="$project->id" wire-event="confirmToggleItemStatus" />
            <x-table.edit-btn :href="route('projects.edit', ['project' => $project->slug])" />
            <x-table.delete-btn :modelId="$project->id" />
        </div>
    </div>

    <div class="border-b border-gray-200">
        <nav class="-mb-px flex space-x-6" aria-label="Tabs">
            <a href="#" @click.prevent="activeTab = 'details'"
                :class="{
                    'border-primary text-primary': activeTab === 'details',
                    'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'details'
                }"
                class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors">
                @lang('general.project-info')
            </a>
            <a href="#" @click.prevent="activeTab = 'centers'"
                :class="{
                    'border-primary text-primary': activeTab === 'centers',
                    'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'centers'
                }"
                class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors">
                {{ trans_choice('general.center', 2) }}
            </a>
            <a href="#" @click.prevent="activeTab = 'employees'"
                :class="{
                    'border-primary text-primary': activeTab === 'employees',
                    'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'employees'
                }"
                class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors">
                {{ trans_choice('general.employee', 2) }}
            </a>
        </nav>
    </div>

    <div class="bg-white rounded-lg p-6 shadow-sm border">
        <div x-show="activeTab === 'details'" x-cloak>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-6 text-sm">
                <div class="flex items-start gap-3">
                    <svg class="w-5 h-5 text-gray-400 mt-0.5" xmlns="http://www.w3.org/2000/svg" fill="none"
                        viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M17.25 6.75 22.5 12l-5.25 5.25m-10.5 0L1.5 12l5.25-5.25m7.5-3-4.5 16.5" />
                    </svg>
                    <div>
                        <span class="font-semibold text-gray-800">@lang('general.slug'):</span>
                        <p class="text-gray-600">{{ $project->slug }}</p>
                    </div>
                </div>
                <div class="flex items-start gap-3"></div>
                <div class="flex items-start gap-3">
                    <svg class="w-5 h-5 text-gray-400 mt-0.5" xmlns="http://www.w3.org/2000/svg" fill="none"
                        viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0h18" />
                    </svg>
                    <div>
                        <span class="font-semibold text-gray-800">@lang('general.start_date'):</span>
                        <p class="text-gray-600">{{ $project->start_date?->format('d M, Y') }}</p>
                    </div>
                </div>
                <div class="flex items-start gap-3">
                    <svg class="w-5 h-5 text-gray-400 mt-0.5" xmlns="http://www.w3.org/2000/svg" fill="none"
                        viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0h18" />
                    </svg>
                    <div>
                        <span class="font-semibold text-gray-800">@lang('general.end_date'):</span>
                        <p class="text-gray-600">{{ $project->end_date?->format('d M, Y') ?? __('general.active') }}</p>
                    </div>
                </div>
            </div>
        </div>

        <div x-show="activeTab === 'centers'" x-cloak>
            @if ($project->centers->isNotEmpty())
                <div class="overflow-x-auto border rounded-lg">
                    <table class="w-full text-sm text-left text-gray-500">
                        <thead class="text-xs text-gray-700 bg-gray-50 border-b">
                            <tr>
                                <th class="px-6 py-3 font-medium">@lang('general.name')</th>
                                <th class="px-6 py-3 font-medium">@lang('general.city')</th>
                                <th class="px-6 py-3 font-medium">@lang('general.created_at')</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y">
                            @foreach ($project->centers as $center)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 font-medium text-gray-900">{{ $center->name }}</td>
                                    <td class="px-6 py-4">{{ $center->city->name ?? '-' }}</td>
                                    <td class="px-6 py-4">{{ $center->created_at->format('d M, Y') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-12">
                    <p class="text-gray-500">@lang('general.no-centers')</p>
                </div>
            @endif
        </div>

        <div x-show="activeTab === 'employees'" x-cloak>
            @if ($employees)
                <div class="overflow-x-auto border rounded-lg">
                    <table class="w-full text-sm text-left text-gray-500">
                        <thead class="text-xs text-gray-700 bg-gray-50 border-b">
                            <tr>
                                <th class="px-6 py-3 font-medium">@lang('general.employee-name')</th>
                                <th class="px-6 py-3 font-medium">@lang('general.position')</th>
                                <th class="px-6 py-3 font-medium">@lang('general.contract-start')</th>
                                <th class="px-6 py-3 font-medium">@lang('general.contract-end')</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y">
                            @foreach ($employees as $employee)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 font-medium text-gray-900">
                                        {{ $employee->name ?? '-' }}
                                    </td>
                                    <td class="px-6 py-4">
{{--                                        {{ $contract->position?->requiredPosition?->title }}--}}
{{--                                        @if($contract->position?->center)--}}
{{--                                            — {{ $contract->position->center->getTranslation('name', app()->getLocale()) }}--}}
{{--                                        @endif--}}
                                    </td>
{{--                                    <td class="px-6 py-4">{{ $employee->contract->start_date?->format('d M, Y') }}</td>--}}
                                    <td class="px-6 py-4">
{{--                                        {{ $contract->end_date?->format('d M, Y') ?? __('general.ongoing') }}--}}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-12">
                    <p class="text-gray-500">@lang('general.no-employees')</p>
                </div>
            @endif
        </div>
    </div>
</div>
