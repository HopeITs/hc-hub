<div class="space-y-10">

    {{-- HEADER --}}
    <div class="flex items-center justify-between mb-6">
        <livewire:layout.breadcrumbs
            :title="__('general.programs-management')"
            :crumbs="[['label' => trans_choice('general.program', 2)]]"
        />

        <a href="{{ route('programs.create') }}"
           class="px-4 py-2 rounded-lg bg-primary text-white text-sm font-medium shadow
                  hover:opacity-93 transition">
            @lang('general.add-new')
        </a>
    </div>


    {{-- PROGRAM GRID --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">

        @foreach ($items as $program)
            <div class="rounded-3xl bg-white shadow-sm border border-gray-200 hover:shadow-md transition">

                {{-- HEADER --}}
                <div class="py-3.5 px-5 flex justify-between items-center">
                    <div>
                        <h2 class="font-bold text-secondary-dark text-xl">
                            {{ $program->name }}
                        </h2>

                        {{-- PROGRAM SLUG BADGE --}}
                        <span class="mt-2 inline-block text-xs font-medium px-2 py-0.5
                                     border border-secondary-light text-secondary-dark bg-white rounded-3xl">
                            {{ $program->code . '-' . $program->slug }}
                        </span>
                    </div>

                    {{-- ACTIVE DOT --}}
                    <span class="h-2.5 w-2.5 rounded-full
                        {{ $program->is_active ? 'bg-green-400' : 'bg-gray-400' }}">
                    </span>
                </div>


                {{-- MINI CARD GRID --}}
                <div class="px-5 pt-2 pb-4 grid grid-cols-1 gap-3">

                    {{-- PROGRAM MANAGER (Full width) --}}
                    <div class="flex  items-start p-3 rounded-lg border border-gray-100 bg-gray-100">
                        <x-icons.user class="w-4 h-4 text-gray-400 mr-2" />
                        <div class="flex flex-col gap-1">
                            <span class="text-gray-500 mt-0.5 text-xs">{{ __('general.program-manager') }}</span>
                            <span class="font-semibold text-secondary-dark text-sm">
                                {{ $program->program_manager_name ?? '-' }}
                            </span>
                        </div>
                    </div>


                    {{-- ROW 2: CITIES + EMPLOYEES --}}
                    <div class="grid grid-cols-2 gap-3">

                        {{-- Cities --}}
                        <div class="flex items-start p-3 rounded-lg border border-gray-100 bg-gray-100">
                            <x-icons.map-pin class="w-4 h-4 text-gray-400 mr-2" />
                            <div class="flex flex-col gap-1">
                                <span class="text-gray-500 mt-0.5  text-xs">{{ trans_choice('general.city', 2) }}</span>
                                <span class="font-semibold text-gray-800 text-sm">
                                    {{ $program->cities_count }}
                                </span>
                            </div>
                        </div>

                        {{-- Employees --}}
                        <div class="flex items-start p-3 rounded-lg border border-gray-100 bg-gray-100">
                            <x-icons.users class="w-4 h-4 text-gray-400 mr-2" />
                            <div class="flex flex-col gap-1">
                                <span class="text-gray-500 mt-0.5  text-xs">{{ trans_choice('general.employee', 2) }}</span>
                                <span class="font-semibold text-gray-800 text-sm">
                                    {{ $program->contracts_count }}
                                </span>
                            </div>
                        </div>

                    </div>


                    {{-- ROW 3: START DATE + END DATE --}}
                    <div class="grid grid-cols-2 gap-3">

                        {{-- Start Date --}}
                        <div class="flex items-start p-3 rounded-lg border border-gray-100 bg-gray-100">
                            <x-icons.calendar class="w-4 h-4 text-gray-400 mr-2" />
                            <div class="flex flex-col gap-1">
                                <span class="text-gray-500 mt-0.5  text-xs">{{ __('general.start-date') }}</span>
                                <span class="font-semibold text-gray-800 text-sm">
                                    {{ $program->start_date?->format('M, Y') ?? '-' }}
                                </span>
                            </div>
                        </div>

                        {{-- End Date --}}
                        <div class="flex items-start p-3 rounded-lg border border-gray-100 bg-gray-100">
                            <x-icons.calendar class="w-4 h-4 text-gray-400 mr-2" />
                            <div class="flex flex-col gap-1">
                                <span class="text-gray-500 mt-0.5  text-xs">{{ __('general.end-date') }}</span>
                                <span class="font-semibold text-gray-800 text-sm">
                                    {{ $program->end_date ? $program->end_date?->format('M, Y') : __('general.active') }}
                                </span>
                            </div>
                        </div>

                    </div>

                </div>


                {{-- FOOTER --}}
                <div class="border-t border-secondary-soft px-5 py-3 flex items-center justify-between">

                    <a href="{{ route('programs.show', $program->slug) }}"
                       class="text-primary hover:underline text-sm font-medium">
                        @lang('general.show-more')
                    </a>

                    <div class="flex items-center ">

                        <x-forms.toggle-input
                            :active="$program->is_active"
                            :model-id="$program->id"
                            wire-event="confirmToggleItemStatus"
                        />

                        <x-table.edit-btn :href="route('programs.edit', $program->slug)" class="ltr:ml-1 text-center" />

                        <x-table.delete-btn :modelId="$program->id" class="mb-0.5" />
                    </div>
                </div>

            </div>
        @endforeach

    </div>


    {{-- LOAD MORE --}}
    @if ($items->count() >= $perPage)
        <div class="text-center pt-4">
            <button wire:click="loadMore"
                    class="px-4 py-1.5 rounded-lg bg-white border border-primary-300
                           text-primary-600 text-sm hover:bg-primary-50 transition">
                @lang('general.show-more')
            </button>
        </div>
    @endif

</div>
