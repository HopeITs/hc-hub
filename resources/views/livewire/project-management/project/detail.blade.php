<div class="space-y-8">

    <livewire:layout.breadcrumbs :title="__('general.projects-management')" :crumbs="[
        ['label' => __('general.projects-management')],
        ['label' => trans_choice('general.project', 2), 'url' => route('projects.index')],
        ['label' => $project->name],
    ]" />

    <div class="flex justify-between items-center p-6 bg-white rounded-lg shadow-sm border">
        <div>
            <h2 class="text-xl font-bold text-gray-800">{{ $project->getTranslation('name', app()->getLocale()) }}</h2>
            <p class="text-sm text-gray-500 mt-1">
                {{ $project->getTranslation('description', app()->getLocale()) }}
            </p>
        </div>

        <div class="flex items-center space-x-2">
            <x-forms.toggle-input :active="$project->is_active" :model-id="$project->id" wire-event="confirmToggleItemStatus" />
            <x-table.edit-btn :href="route('projects.edit', ['project' => $project->slug])" />
            <x-table.delete-btn :modelId="$project->id" />
        </div>
    </div>

    <div class="bg-white rounded-lg p-6 shadow-sm border space-y-4">
        <h3 class="text-lg font-semibold text-gray-800">@lang('general.project-info')</h3>

        <div class="grid md:grid-cols-2 gap-4 text-sm text-gray-600">
            <div>
                <strong>@lang('general.code'):</strong> {{ $project->code }}
            </div>
            <div>
                <strong>@lang('general.slug'):</strong> {{ $project->slug }}
            </div>
            <div>
                <strong>@lang('general.start_date'):</strong> {{ $project->start_date?->format('d-m-Y') }}
            </div>
            <div>
                <strong>@lang('general.end_date'):</strong> {{ $project->end_date?->format('d-m-Y') ?? __('general.active') }}
            </div>
            <div class="md:col-span-2">
                <strong>@lang('general.description'):</strong>
                <p class="mt-1">{{ $project->getTranslation('description', app()->getLocale()) }}</p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg p-6 shadow-sm border space-y-4">
        <h3 class="text-lg font-semibold text-gray-800">{{ trans_choice('general.center', 2) }}</h3>

        @if ($project->centers->isNotEmpty())
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left text-gray-500">
                    <thead class="text-xs text-gray-700 bg-gray-50">
                        <tr>
                            <th class="px-4 py-2">#</th>
                            <th class="px-4 py-2">@lang('general.name')</th>
                            <th class="px-4 py-2">@lang('general.city')</th>
                            <th class="px-4 py-2">@lang('general.created_at')</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y">
                        @foreach ($project->centers as $index => $center)
                            <tr>
                                <td class="px-4 py-2">{{ $index + 1 }}</td>
                                <td class="px-4 py-2">{{ $center->name }}</td>
                                <td class="px-4 py-2">{{ $center->city->name ?? '-' }}</td>
                                <td class="px-4 py-2">{{ $center->created_at->format('d-m-Y') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="text-sm text-gray-500">@lang('general.no-centers')</p>
        @endif
    </div>

    <div class="bg-white rounded-lg p-6 shadow-sm border space-y-4">
        <h3 class="text-lg font-semibold text-gray-800">{{ trans_choice('general.employee', 2) }}</h3>

        @if ($project->contracts && $project->contracts->isNotEmpty())
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left text-gray-500">
                    <thead class="text-xs text-gray-700 bg-gray-50">
                        <tr>
                            <th class="px-4 py-2">#</th>
                            <th class="px-4 py-2">@lang('general.employee-name')</th>
                            <th class="px-4 py-2">@lang('general.position')</th>
                            <th class="px-4 py-2">@lang('general.contract-start')</th>
                            <th class="px-4 py-2">@lang('general.contract-end')</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y">
                        @foreach ($project->contracts as $index => $contract)
                            <tr>
                                <td class="px-4 py-2">{{ $index + 1 }}</td>
                                <td class="px-4 py-2">{{ $contract->employee->name ?? '-' }}</td>
                                <td class="px-4 py-2">{{ $contract->position }}</td>
                                <td class="px-4 py-2">{{ $contract->start_date?->format('d-m-Y') }}</td>
                                <td class="px-4 py-2">
                                    {{ $contract->end_date?->format('d-m-Y') ?? __('general.ongoing') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="text-sm text-gray-500">@lang('general.no-employees')</p>
        @endif
    </div>

</div>
