<div class="space-y-10">

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


    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">

        @foreach ($items as $program)
            <div class="rounded-3xl bg-white shadow-sm border border-gray-200 hover:shadow-md transition">

                <div class=" mt-3 py-2 px-5.5 flex justify-between items-center">
                    <h2 class="font-bold w-[240px] text-secondary-dark text-xl">
                        {{ $program->name }}
                    </h2>

                    <span class=" text-xs font-medium px-2 py-0.5
                                     border border-secondary-light text-secondary-dark bg-white rounded-3xl">
                            {{ $program->code . '-' . $program->slug }}
                        </span>

                </div>


                <div class="px-5 pt-2 pb-4 grid grid-cols-1 gap-3">

                    <div class="flex  items-start p-3 rounded-lg border border-gray-100 bg-gray-100">
                        <x-icons.user class="w-4 h-4 text-gray-400 mr-2" />
                        <div class="flex flex-col gap-1">
                            <span class="text-gray-500 mt-0.5 text-xs">{{ __('general.program-manager') }}</span>
                            <span class="font-semibold text-secondary-dark text-sm">
                                {{ $program->program_manager_name ?? '-' }}
                            </span>
                        </div>
                    </div>


                    <div class="grid grid-cols-2 gap-3">

                        <div class="flex items-start p-3 rounded-lg border border-gray-100 bg-gray-100">
                            <x-icons.map-pin class="w-4 h-4 text-gray-400 mr-2" />
                            <div class="flex flex-col gap-1">
                                <span class="text-gray-500 mt-0.5  text-xs">{{ trans_choice('general.city', 2) }}</span>
                                <span class="font-semibold text-gray-800 text-sm">
                                    {{ $program->cities_count }}
                                </span>
                            </div>
                        </div>

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


                    <div class="grid grid-cols-2 gap-3">

                        <div class="flex items-start p-3 rounded-lg border border-gray-100 bg-gray-100">
                            <x-icons.calendar class="w-4 h-4 text-gray-400 mr-2" />
                            <div class="flex flex-col gap-1">
                                <span class="text-gray-500 mt-0.5  text-xs">{{ __('general.start-date') }}</span>
                                <span class="font-semibold text-gray-800 text-sm">
                                    {{ $program->start_date?->format('M, Y') ?? '-' }}
                                </span>
                            </div>
                        </div>

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

                <div class="border-t border-secondary-soft  flex items-center justify-center px-5 py-2 ">
                    <div class="flex flex-row items-center justify-between w-[200px]">
                        <div class="p-2">
                            <a href="{{ route('programs.show', $program->slug) }}"
                               class="text-secondary-medium hover:underline text-sm font-medium">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                                     viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                                     class="w-5 h-5">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                          d="M2.25 12s3.75-6.75 9.75-6.75S21.75 12 21.75 12
                                   s-3.75 6.75-9.75 6.75S2.25 12 2.25 12z" />
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                          d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>

                            </a>
                        </div>

                        <div class="p-2">
                            <x-forms.toggle-input
                                :active="$program->is_active"
                                :model-id="$program->id"
                                wire-event="confirmToggleItemStatus"
                            />
                        </div>
                        <x-table.edit-btn :href="route('programs.edit', $program->slug)" class=" text-secondary-medium text-center" />

                        <x-table.delete-btn :modelId="$program->id"  />
                    </div>
                </div>

            </div>
        @endforeach
    </div>

    @if ($items->count() >= $perPage)
        <div class="text-center pt-4">
            <button wire:click="loadMore"
                    class="px-4 py-1.5 rounded-lg bg-white border border-secondary-light
                           text-secondary-dark text-sm hover:bg-primary-50 transition">
                @lang('general.show-more')
            </button>
        </div>
    @endif
</div>
