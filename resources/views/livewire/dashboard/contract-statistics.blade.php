<div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8  py-12">

    {{-- =======================
         HEADER
    ======================== --}}
    <div class="mb-12 flex items-center  justify-between">
        <h1 class="text-4xl font-black text-neutral-900 tracking-tight">
            @lang('general.contract-statistics')
        </h1>

        <button wire:click="resetFilters"
                class="px-6 py-2.5 text-sm text-secondary-dark font-semibold border border-secondary-soft
                       rounded-lg bg-white hover:shadow-sm cursor-pointer transition">
            @lang('general.reset')
        </button>
    </div>


    {{-- =======================
         FILTERS
    ======================== --}}
    <div class="mb-6 grid gap-8 sm:grid-cols-2 lg:grid-cols-3 ">

        {{-- CITY --}}
        <div class="flex flex-col gap-1.5">
            <label class="text-xs font-semibold text-secondary-dark uppercase tracking-wide">
                {{ trans_choice('general.city', 1) }}
            </label>

            <select wire:model.lazy="selectedCity"
                    class="w-full border border-secondary-soft rounded-lg px-4 py-2.5
                           text-neutral-900 bg-white focus:ring-primary focus:ring-2 transition">
                <option value="">{{ __('general.all') }} {{ trans_choice('general.city', 2) }}</option>
                @foreach($cities as $city)
                    <option value="{{ $city->id }}">{{ $city->name }}</option>
                @endforeach
            </select>
        </div>

        {{-- PROGRAM --}}
        <div class="flex flex-col gap-1.5">
            <label class="text-xs font-semibold text-secondary-dark uppercase tracking-wide">
                {{ trans_choice('general.program', 1) }}
            </label>

            <select wire:model.lazy="selectedProgram"
                    class="w-full border border-secondary-soft rounded-lg px-4 py-2.5
                           text-neutral-900 bg-white focus:ring-primary focus:ring-2 transition">
                <option value="">{{ __('general.all') }} {{ trans_choice('general.program', 2) }}</option>
                @foreach($programs as $program)
                    <option value="{{ $program->id }}">{{ $program->name }}</option>
                @endforeach
            </select>
        </div>

    </div>


    {{-- =======================
         LOADING
    ======================== --}}
    <div wire:loading.flex wire:target="applyFilters, resetFilters" class="justify-center py-10">
        <div class="text-sm text-secondary-dark flex items-center gap-2">
            <span class="w-2 h-2 rounded-full bg-primary animate-pulse"></span>
            @lang('general.updating-statistics')
        </div>
    </div>


    {{-- =======================
         CARDS GRID
    ======================== --}}
    <div wire:loading.remove wire:target="applyFilters, resetFilters"
         class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 ">

        @forelse ($cards as $card)

            {{-- =======================
                 NEW CARD DESIGN
            ======================== --}}
            <article class="border border-secondary-soft rounded-3xl bg-white
                            shadow-[0px_0px_25px_-14px_rgba(0,_0,_0,_0.30)] hover:shadow-md transition-all duration-200
                            hover:-translate-y-1 flex flex-col">

                {{-- HEADER --}}
                <div class="p-6 flex justify-between items-start border-b  border-secondary-soft">
                    <div class="flex flex-col">
                        <span class="text-lg font-black text-neutral-900">
                            {{ $card['name'] }}
                        </span>

                        <span class="text-[13px] text-secondary-dark mt-1.5">
                            {{ trans_choice('general.employee', 2) }}:
                            <span class="font-bold text-primary">{{ $card['employees'] }}</span>
                        </span>
                    </div>

                    <div class="text-right">
                        <span class="inline-block px-2 py-1 text-[10px] font-semibold text-white rounded-md
                                     {{ $card['color'] }}">
                            {{ $cardType === 'program'
                                ? trans_choice('general.program', 1)
                                : trans_choice('general.center', 1) }}
                        </span>

                        @if ($card['centers'] !== null)
                            <p class="text-[13px] text-center text-secondary-dark mt-[10.2px]">
                                {{ trans_choice('general.center', 2) }}:
                                <span class="font-semibold text-neutral-800">{{ $card['centers'] }}</span>
                            </p>
                        @endif
                    </div>
                </div>


                {{-- STATS --}}
                <div class="grid grid-cols-3 gap-3 p-4">

                    @foreach ([
                        'annual'  => __('general.annual'),
                        'service' => __('general.service'),
                        'trial'   => __('general.trial')
                    ] as $key => $label)

                        @php
                            $data = $card[$key];
                            $isService = $key === 'service';

                            $total = $isService
                                ? $data['online'] + $data['onsite']
                                : $data['full'] + $data['part'];
                        @endphp

                        <div class="rounded-2xl bg-neutral-50 border border-neutral-200
                    p-3 shadow-inner hover:bg-neutral-100 transition">

                            {{-- Title --}}
                            <p class="text-xs font-semibold text-neutral-800
                      border-b border-neutral-200 pb-1 mb-1">
                                {{ $label }}
                            </p>

                            <div class="space-y-0.5 text-xs">

                                @if ($isService)
                                    <div class="flex justify-between">
                                        <span class="text-neutral-600">@lang('general.online')</span>
                                        <span class="font-semibold text-neutral-800">{{ $data['online'] }}</span>
                                    </div>

                                    <div class="flex justify-between">
                                        <span class="text-neutral-600">@lang('general.onsite')</span>
                                        <span class="font-semibold text-neutral-800">{{ $data['onsite'] }}</span>
                                    </div>
                                @else
                                    <div class="flex justify-between">
                                        <span class="text-neutral-600">@lang('general.full-time')</span>
                                        <span class="font-semibold text-neutral-800">{{ $data['full'] }}</span>
                                    </div>

                                    <div class="flex justify-between">
                                        <span class="text-neutral-600">@lang('general.part-time')</span>
                                        <span class="font-semibold text-neutral-800">{{ $data['part'] }}</span>
                                    </div>
                                @endif

                                {{-- Total --}}
                                <div class="border-t border-neutral-200 pt-1 mt-1 flex justify-between">
                                    <span class="text-neutral-600">@lang('general.total')</span>
                                    <span class="font-bold text-primary">{{ $total }}</span>
                                </div>

                            </div>
                        </div>
                    @endforeach
                </div>




                {{-- FOOTER --}}
                <div class="p-4 border-t border-secondary-soft">
                    <a href="{{ $cardType === 'program'
                                ? route('programs.show', ['program' => $card['program_slug']])
                                : ($cardType === 'center'
                                    ? route('centers.show', $card['center_id'])
                                    : '#') }}"
                       class="text-sm flex justify-end font-semibold text-primary hover:underline">
                        @lang('general.show-more') →
                    </a>
                </div>

            </article>


        @empty

            <div class="col-span-full text-center py-10 text-secondary-dark">
                @lang('general.no-data-filters')
            </div>

        @endforelse

    </div>

</div>
