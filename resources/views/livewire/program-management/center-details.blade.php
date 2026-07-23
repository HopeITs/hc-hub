<div class="relative isolate">

    <div class="space-y-12 ">
        <div class="grid h-full gap-6 md:grid-cols-[1fr_auto] items-start">
            <div
                class="relative overflow-hidden rounded-surface border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-900 backdrop-blur-xl p-6 shadow-e1">
                <div class="absolute inset-0 opacity-50 bg-neutral-100 dark:bg-neutral-800"></div>

                <div class="relative flex flex-col gap-3">
                    <div class="flex flex-row w-full ">
                        <h1 class="text-h2 font-black  w-2/3 text-neutral-900 dark:text-white">
                            {{ $center->name }}
                        </h1>
                        <div class=" flex flex-row justify-end items-center h-12 w-1/3  gap-3">
                            <a href="{{ route('centers.index') }}"
                               class="group relative inline-flex items-center gap-2 rounded-surface border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-800 px-3 py-2 text-neutral-800 dark:text-white shadow-e1 transition-all duration-300  active:translate-y-0">
                                <span class="font-semibold text-small">@lang('general.back')</span>
                            </a>
                            @activerole('HR','super-admin')
                            <a href="{{ route('positions.index', ['center' => $center->id]) }}"
                               class="group relative flex items-center gap-2 rounded-surface px-3 py-2 bg-primary text-white hover:bg-primary shadow-e3 transition-all duration-300 hover:shadow-e3 ">
                                <span class="z-10 text-small font-semibold">@lang('general.view-positions')</span>
                            </a>
                            @endactiverole
                        </div>
                    </div>

                    <p class="text-neutral-600 h-18 dark:text-neutral-300 max-w-prose line-clamp-2">
                        <span>@lang('general.located-in')<span
                                class="ms-1 font-semibold text-neutral-900 dark:text-white">{{ optional($center->city)->name ?? '—' }}</span></span>
                        ,@lang('general.centers-text')
                    </p>


                    <div class="relative mt-2 -ms-1">
                        <div
                            class="inline-flex rounded-surface border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-900 backdrop-blur-xl p-1 shadow-e3">
                            <button
                                wire:click="switchTab('contracts')"
                                class="ui-tab group relative inline-flex items-center gap-2 rounded-surface px-4 py-2 text-body font-semibold transition-all duration-300  {{ $activeTab === 'contracts' ? 'bg-primary text-white' : 'text-neutral-700 dark:text-neutral-300' }}">
                                <span class="z-10">@lang('general.employees-by-contract-type')</span>
                            </button>
                            <button
                                wire:click="switchTab('programs')"
                                class="ui-tab group relative inline-flex items-center gap-2 rounded-surface px-4 py-2 text-body font-semibold transition-all duration-300 focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary-light {{ $activeTab === 'programs' ? 'bg-primary text-white' : 'text-neutral-700 dark:text-neutral-300' }}">
                                <span class="z-10">@lang('general.employees-by-program')</span>
                            </button>
                        </div>
                    </div>

                </div>
            </div>

            <div
                class=" w-full h-full text-center flex items-center  relative overflow-hidden rounded-surface border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-900 backdrop-blur-xl px-4 shadow-e1">
                <div class="relative grid grid-rows-2 gap-4">
                    <div>
                        <p class="text-body font-semibold tracking-wide text-neutral-500 dark:text-neutral-400">@lang('general.total-employees')</p>
                        <p class=" text-h3 font-bold text-secondary-dark dark:text-white">
                            {{ $totalEmployees ?? collect($programData)->sum('count') }}
                        </p>
                    </div>
                    <div>
                        <p class="text-body  font-semibold tracking-wide text-neutral-500 dark:text-neutral-400">{{trans_choice('general.program' ,1)}}</p>
                        <p class="mt-1 text-h3 font-bold text-secondary-dark dark:text-white">
                            {{ is_countable($programData) ? count($programData) : 0 }}
                        </p>
                    </div>
                </div>
            </div>
        </div>


        <div class="pt-4">
            @if ($activeTab === 'contracts')
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                    @foreach ($contractData as $type => $categories)
                        @php $__total = array_sum(array_column($categories['items'], 'count')); @endphp
                        <div
                            class="group relative overflow-hidden rounded-surface border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-900 backdrop-blur-xl p-6 shadow-e3 transition-all duration-300 hover:-translate-y-1 hover:shadow-e3">
                            <div class="relative flex items-center justify-between gap-4">
                                <h3 class="text-h4 font-bold capitalize text-neutral-900 dark:text-white">{{ $categories['label'] }}</h3>
                                <span
                                    class="inline-flex  items-center gap-1  px-3 py-2 text-body font-semibold text-secondary-dark dark:text-secondary-medium">
                                    <i class="ti ti-users"></i>
                                    @lang('general.total') {{ $__total }}
                                </span>
                            </div>

                            <div class="relative mt-4 space-y-3">
                                @foreach ($categories['items'] as $item)
                                    @php
                                        $count = $item['count'];
                                        $label = $item['label'];
                                        $__pct = $__total ? round(($count / $__total) * 100) : 0;
                                    @endphp

                                    <div class="space-y-2">
                                        <div class="flex items-center justify-between text-body">
                                            <span class="text-neutral-600 dark:text-neutral-300">{{ $label }}</span>
                                            <span
                                                class="font-semibold text-neutral-900 dark:text-white">{{ $count }}</span>
                                        </div>

                                        <div
                                            class="h-2 w-full overflow-hidden rounded-full bg-neutral-200 dark:bg-neutral-800">
                                            <div class="h-full rounded-full bg-primary"
                                                 style="width: {{ $__pct }}%"></div>
                                        </div>
                                    </div>
                                @endforeach

                            </div>

                        </div>
                    @endforeach
                </div>

            @elseif ($activeTab === 'programs')
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                    @forelse ($programData as $program)
                        <div
                            class="group relative overflow-hidden rounded-surface border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-900 backdrop-blur-xl p-6 shadow-e3 transition-all duration-300 hover:-translate-y-1 hover:shadow-e3">
                            <h3 class="relative text-h4 font-extrabold text-secondary-dark dark:text-white">
                                {{ $program['program'] ?? 'Unassigned Program' }}
                            </h3>

                            <p class="mt-1 text-body text-secondary-light dark:text-neutral-300">@lang('general.total-employees')</p>
                            <p class="text-h2 font-black mt-3 tracking-normal text-primary dark:text-secondary-medium">
                                {{ $program['count'] }}
                            </p>

                        </div>
                    @empty
                        <div class="col-span-full">
                            <div
                                class="flex flex-col items-center justify-center rounded-surface border border-dashed border-neutral-300 dark:border-neutral-700 bg-white dark:bg-neutral-900 backdrop-blur-xl p-12 text-center shadow-e1">
                                <div
                                    class="mb-4 inline-flex h-14 w-14 items-center justify-center rounded-surface bg-neutral-100 dark:bg-neutral-800 shadow">
                                    <i class="ti ti-info-circle text-h3 text-neutral-500 dark:text-neutral-300"></i>
                                </div>
                                <p class="text-h4 font-semibold text-neutral-800 dark:text-white">No programs found for
                                    this center</p>
                                <p class="mt-1 text-neutral-600 dark:text-neutral-300">Try assigning employees to
                                    programs to populate this view.</p>
                            </div>
                        </div>
                    @endforelse
                </div>
            @endif
        </div>

        <div wire:loading.delay.class.remove="hidden" class="hidden">
            <div class="mt-6 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                @for ($i = 0; $i < 6; $i++)
                    <div
                        class="animate-pulse rounded-surface border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-900 backdrop-blur-xl p-6 shadow">
                        <div class="h-6 w-1/3 rounded bg-neutral-200 dark:bg-neutral-700"></div>
                        <div class="mt-4 space-y-3">
                            <div class="h-3 w-full rounded bg-neutral-200 dark:bg-neutral-700"></div>
                            <div class="h-3 w-5/6 rounded bg-neutral-200 dark:bg-neutral-700"></div>
                            <div class="h-3 w-2/3 rounded bg-neutral-200 dark:bg-neutral-700"></div>
                        </div>
                    </div>
                @endfor
            </div>
        </div>

        <style>
            @media (prefers-reduced-motion: reduce) {
                .ui-tab, a {
                    transition: none !important;
                }
            }
        </style>
    </div>
</div>
