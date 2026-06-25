<div>
    <div class="space-y-10">
        <div class="flex items-center justify-between mb-6">
            <livewire:layout.breadcrumbs
                :title="__('general.programs-management')"
                :crumbs="[['label' => trans_choice('general.program', 2)]]"
            />
            @activerole('HR' , 'super-admin' ,'general-manager')
            <a href="{{ route('programs.create') }}"
               class="px-4 py-2 rounded-lg bg-primary text-white text-sm font-medium shadow transition">
                @lang('general.add-new')
            </a>
            @endactiverole
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach ($items as $program)
                <div class="rounded-3xl bg-white shadow-[0px_0px_25px_-14px_rgba(0,_0,_0,_0.30)] border border-gray-200 hover:shadow-md transition flex flex-col h-full">

                    <!-- Smaller Header Section -->
                    <div class="p-4 border-b border-gray-100 flex-shrink-0">
                        <div class="flex items-center justify-between gap-3">
                            <!-- Program name with vertical centering -->
                            <div class="flex-1 min-w-0 flex items-center">
                                <div class="overflow-hidden">
                                    <h2 class="font-bold text-secondary-dark text-[18px] leading-snug line-clamp-2">
                                        @php
                                            // Display slug instead of full name for specific long program names
                                            $displayName = $program->name;
                                            $longNames = [
                                                'Development Impact and Support Center',
                                                'Leadership Empowerment & Achievement Program'
                                            ];

                                            if (in_array($program->name, $longNames)) {
                                                $displayName = $program->slug;
                                            }
                                        @endphp
                                        {{ $displayName }}
                                    </h2>
                                </div>
                            </div>

                            <!-- Actions with vertical centering - Checkbox moved to last -->
                            @activerole('HR','super-admin','general-manager')
                            <div class="flex items-center gap-0.5">
                                <a href="{{ route('programs.show', $program->slug) }}"
                                   class="p-1 text-secondary-medium hover:text-primary hover:bg-gray-50
                                          rounded-lg transition"
                                   title="@lang('general.view')">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                         stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                              d="M2.25 12s3.75-6.75 9.75-6.75S21.75 12 21.75 12
                                                 s-3.75 6.75-9.75 6.75S2.25 12 2.25 12z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                              d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                </a>

                                <a href="{{ route('programs.edit', $program->slug) }}"
                                   class="p-1 text-secondary-medium hover:text-primary hover:bg-gray-50
                                          rounded-lg transition"
                                   title="@lang('general.edit')">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                         stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                              d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                                    </svg>
                                </a>

                                <div class="p-1">
                                    <x-forms.toggle-input
                                        :active="$program->is_active"
                                        :model-id="$program->id"
                                        wire-event="confirmToggleItemStatus"
                                        class="scale-90"
                                    />
                                </div>
                            </div>
                            @endactiverole
                        </div>
                    </div>

                    <!-- Content Section -->
                    <div class="px-5 pt-3 pb-5 grid grid-cols-1 gap-2.5 flex-grow">
                        <!-- Program Manager -->
                        <div class="flex items-center p-2.5 rounded-lg border border-gray-100 bg-gray-100 min-h-[3.5rem]">
                            <x-icons.user class="size-3.5 text-gray-400 flex-shrink-0 me-2.5"/>
                            <div class="flex flex-col gap-0.5 min-w-0">
                                <span class="text-gray-500 text-xs">{{ __('general.program-manager') }}</span>
                                <span class="font-semibold text-secondary-dark text-sm truncate">
                                    {{ $program->program_manager_name ?? '-' }}
                                </span>
                            </div>
                        </div>

                        <!-- Cities & Employees -->
                        <div class="grid grid-cols-2 gap-2.5">
                            <div class="flex items-center p-2.5 rounded-lg border border-gray-100 bg-gray-100 min-h-[3.5rem]">
                                <x-icons.map-pin class="size-3.5 text-gray-400 flex-shrink-0 me-2.5"/>
                                <div class="flex flex-col gap-0.5">
                                    <span class="text-gray-500 text-xs">{{ trans_choice('general.city', 2) }}</span>
                                    <span class="font-semibold text-gray-800 text-base">
                                        {{ $program->cities_count }}
                                    </span>
                                </div>
                            </div>

                            <div class="flex items-center p-2.5 rounded-lg border border-gray-100 bg-gray-100 min-h-[3.5rem]">
                                <x-icons.users class="size-3.5 text-gray-400 flex-shrink-0 me-2.5"/>
                                <div class="flex flex-col gap-0.5">
                                    <span class="text-gray-500 text-xs">{{ trans_choice('general.employee', 2) }}</span>
                                    <span class="font-semibold text-gray-800 text-base">
                                        {{ $program->contracts_count }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Dates -->
                        <div class="grid grid-cols-2 gap-2.5">
                            <div class="flex items-center p-2.5 rounded-lg border border-gray-100 bg-gray-100 min-h-[3.5rem]">
                                <x-icons.calendar class="size-3.5 text-gray-400 flex-shrink-0 me-2.5"/>
                                <div class="flex flex-col gap-0.5 min-w-0">
                                    <span class="text-gray-500 text-xs">{{ __('general.start-date') }}</span>
                                    <span class="font-semibold text-gray-800 text-sm truncate">
                                        {{ $program->start_date?->format('M, Y') ?? '-' }}
                                    </span>
                                </div>
                            </div>

                            <div class="flex items-center p-2.5 rounded-lg border border-gray-100 bg-gray-100 min-h-[3.5rem]">
                                <x-icons.calendar class="size-3.5 text-gray-400 flex-shrink-0 me-2.5"/>
                                <div class="flex flex-col gap-0.5 min-w-0">
                                    <span class="text-gray-500 text-xs">{{ __('general.end-date') }}</span>
                                    <span class="font-semibold text-gray-800 text-sm truncate">
                                        {{ $program->end_date ? $program->end_date?->format('M, Y') : __('general.active') }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            @endforeach
        </div>

        @if ($items->count() >= $perPage)
            <div class="text-center pt-4">
                <button wire:click="loadMore"
                        class="px-4 py-1.5 rounded-lg bg-white border border-secondary-light
                           text-secondary-dark text-sm hover:text-primary hover:border-primary cursor-pointer transition ease-in duration-75">
                    @lang('general.show-more')
                </button>
            </div>
        @endif
    </div>

    <style>
        /* Add this to your CSS file for line-clamp support */
        .line-clamp-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
    </style>
</div>
