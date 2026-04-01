<div class="relative isolate">

    <div class="space-y-10 ">
        <div class="grid h-full gap-6 md:grid-cols-[1fr_auto] items-start">
            <div
                class="relative overflow-hidden rounded-3xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-900 backdrop-blur-xl p-6 shadow-sm">
                <div class="absolute inset-0 opacity-50 bg-neutral-100 dark:bg-neutral-800"></div>

                <div class="relative flex flex-col gap-3">
                    <div class="flex flex-row w-full ">
                        <h1 class="text-3xl font-black  w-2/3 text-neutral-900 dark:text-white">
                            {{ $center->name }}
                        </h1>
                        <div class=" flex flex-row justify-end items-center h-12 w-1/3  gap-3">
                            <a href="{{ route('centers.index') }}"
                               class="group relative inline-flex items-center gap-2 rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-800 px-3 py-2 text-neutral-800 dark:text-white shadow-sm transition-all duration-300  active:translate-y-0">
                                <span class="font-semibold text-[12px]">@lang('general.back')</span>
                            </a>
                            @activerole('HR','super-admin')
                            <a href="{{ route('positions.index', ['center' => $center->id]) }}"
                               class="group relative flex items-center gap-2 rounded-xl px-3 py-2 bg-primary-500 text-white hover:bg-primary-600 shadow-lg transition-all duration-300 hover:shadow-xl ">
                                <span class="z-10 text-[12px] font-semibold">@lang('general.view-positions')</span>
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
                            class="inline-flex rounded-2xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-900 backdrop-blur-xl p-1 shadow-lg">
                            <button
                                wire:click="switchTab('contracts')"
                                class="ui-tab group relative inline-flex items-center gap-2 rounded-xl px-4 py-2 text-sm font-semibold transition-all duration-300  {{ $activeTab === 'contracts' ? 'bg-primary-500 text-white' : 'text-neutral-700 dark:text-neutral-300' }}">
                                <span class="z-10">@lang('general.employees-by-contract-type')</span>
                            </button>
                            <button
                                wire:click="switchTab('programs')"
                                class="ui-tab group relative inline-flex items-center gap-2 rounded-xl px-4 py-2 text-sm font-semibold transition-all duration-300 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500/60 {{ $activeTab === 'programs' ? 'bg-primary-500 text-white' : 'text-neutral-700 dark:text-neutral-300' }}">
                                <span class="z-10">@lang('general.employees-by-program')</span>
                            </button>
                        </div>
                    </div>

                </div>
            </div>

            <div
                class=" w-full h-full text-center flex items-center  relative overflow-hidden rounded-3xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-900 backdrop-blur-xl px-4 shadow-sm">
                <div class="relative grid grid-rows-2 gap-4">
                    <div>
                        <p class="text-[14px] font-semibold tracking-wide text-neutral-500 dark:text-neutral-400">@lang('general.total-employees')</p>
                        <p class=" text-2xl font-bold text-secondary-dark dark:text-white">
                            {{ $totalEmployees ?? collect($programData)->sum('count') }}
                        </p>
                    </div>
                    <div>
                        <p class="text-[14px]  font-semibold tracking-wide text-neutral-500 dark:text-neutral-400">{{trans_choice('general.program' ,1)}}</p>
                        <p class="mt-1 text-2xl font-bold text-secondary-dark dark:text-white">
                            {{ is_countable($programData) ? count($programData) : 0 }}
                        </p>
                    </div>
                </div>
            </div>
        </div>


        <div class="pt-4">
            @if ($activeTab === 'contracts')
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-7">
                    @foreach ($contractData as $type => $categories)
                        @php $__total = array_sum(array_column($categories['items'], 'count')); @endphp
                        <div
                            class="group relative overflow-hidden rounded-3xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-900 backdrop-blur-xl p-6 shadow-lg transition-all duration-300 hover:-translate-y-1 hover:shadow-2xl">
                            <div class="relative flex items-center justify-between gap-4">
                                <h3 class="text-lg font-bold capitalize text-neutral-900 dark:text-white">{{ $categories['label'] }}</h3>
                                <span
                                    class="inline-flex  items-center gap-1  px-3 py-2 text-[13px] font-semibold text-secondary-dark dark:text-primary-300">
                                    <i class="ti ti-users"></i>
                                    @lang('general.total') {{ $__total }}
                                </span>
                            </div>

                            <div class="relative mt-5 space-y-3">
                                @foreach ($categories['items'] as $item)
                                    @php
                                        $count = $item['count'];
                                        $label = $item['label'];
                                        $__pct = $__total ? round(($count / $__total) * 100) : 0;
                                    @endphp

                                    <div class="space-y-1.5">
                                        <div class="flex items-center justify-between text-sm">
                                            <span class="text-neutral-600 dark:text-neutral-300">{{ $label }}</span>
                                            <span
                                                class="font-semibold text-neutral-900 dark:text-white">{{ $count }}</span>
                                        </div>

                                        <div
                                            class="h-2 w-full overflow-hidden rounded-full bg-neutral-200 dark:bg-neutral-800">
                                            <div class="h-full rounded-full bg-primary-500"
                                                 style="width: {{ $__pct }}%"></div>
                                        </div>
                                    </div>
                                @endforeach

                            </div>

                        </div>
                    @endforeach
                </div>

            @elseif ($activeTab === 'programs')
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-7">
                    @forelse ($programData as $program)
                        <div
                            class="group relative overflow-hidden rounded-3xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-900 backdrop-blur-xl p-6 shadow-lg transition-all duration-300 hover:-translate-y-1 hover:shadow-2xl">
                            <h3 class="relative text-lg font-extrabold text-secondary-dark dark:text-white">
                                {{ $program['program'] ?? 'Unassigned Program' }}
                            </h3>

                            <p class="mt-1 text-sm text-secondary-light dark:text-neutral-300">@lang('general.total-employees')</p>
                            <p class="text-3xl font-black mt-3 tracking-normal text-primary dark:text-primary-400">
                                {{ $program['count'] }}
                            </p>

                        </div>
                    @empty
                        <div class="col-span-full">
                            <div
                                class="flex flex-col items-center justify-center rounded-3xl border border-dashed border-neutral-300 dark:border-neutral-700 bg-white dark:bg-neutral-900 backdrop-blur-xl p-12 text-center shadow-sm">
                                <div
                                    class="mb-4 inline-flex h-14 w-14 items-center justify-center rounded-2xl bg-neutral-100 dark:bg-neutral-800 shadow">
                                    <i class="ti ti-info-circle text-2xl text-neutral-500 dark:text-neutral-300"></i>
                                </div>
                                <p class="text-lg font-semibold text-neutral-800 dark:text-white">No programs found for
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
            <div class="mt-6 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-7">
                @for ($i = 0; $i < 6; $i++)
                    <div
                        class="animate-pulse rounded-3xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-900 backdrop-blur-xl p-6 shadow">
                        <div class="h-6 w-1/3 rounded bg-neutral-200 dark:bg-neutral-700"></div>
                        <div class="mt-5 space-y-3">
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
