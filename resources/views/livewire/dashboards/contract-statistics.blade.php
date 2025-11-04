<div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-8" x-data>
    <div class="mb-8 flex items-center justify-between">
        <h1 class="text-3xl font-bold text-neutral-800 tracking-tight">Contract Statistics</h1>
        <div class="hidden sm:flex items-center gap-2 text-sm text-neutral-500">
            <div class="h-1 w-1 rounded-full bg-primary-500 animate-pulse"></div>
        </div>
    </div>

    <div class="mb-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3 items-end">
        <div>
            <label for="city" class="block text-sm font-medium text-neutral-700 mb-1">City</label>
            <select
                id="city"
                wire:model.live="city"
                class="w-full rounded-xl border border-neutral-200 bg-white text-neutral-800 shadow-sm focus:ring-4 focus:ring-primary-200 px-3 py-2 transition">
                <option value="">Choose a city</option>
                <option value="Syria">Syria</option>
                <option value="Aleppo">Aleppo</option>
                <option value="Damascus">Damascus</option>
                <option value="Homs">Homs</option>
            </select>
        </div>

        <div>
            <label for="program" class="block text-sm font-medium text-neutral-700 mb-1">Program</label>
            <select
                id="program"
                wire:model.live="program"
                class="w-full rounded-xl border border-neutral-200 bg-white text-neutral-800 shadow-sm focus:ring-4 focus:ring-primary-200 px-3 py-2 transition">
                <option value="">Choose a program</option>
                <option value="Hope Center">Hope Center</option>
                <option value="Disc">Disc</option>
                <option value="Study Zone">Study Zone</option>
            </select>
        </div>

        <div class="flex gap-3 lg:justify-end">
            <button
                type="button"
                class="inline-flex items-center gap-2 rounded-xl bg-primary-500 text-white px-5 py-2 font-semibold shadow hover:bg-primary-600 transition-transform hover:scale-105">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M10 18h4v-2h-4v2Zm-7-8v2h18v-2H3Zm3-6v2h12V4H6Zm-3 16h18v-2H3v2Z"/>
                </svg>
                Apply
            </button>

            <button
                type="button"
                wire:click="resetFilters"
                class="inline-flex items-center gap-2 rounded-xl border border-neutral-300 bg-white text-neutral-700 px-5 py-2 font-semibold hover:bg-neutral-100 transition">
                Reset
            </button>
        </div>
    </div>

    @php
        $cards = [
            [
                'program'   => 'Hope Center',
                'employees' => 148,
                'centers'   => 12,
                'annual'    => ['full' => 64, 'part' => 22],
                'service'   => ['online' => 41, 'onsite' => 43],
                'trial'     => ['full' => 10, 'part' => 8],
                'color'     => 'bg-primary-500',
            ],
            [
                'program'   => 'Study Zone',
                'employees' => 203,
                'centers'   => 9,
                'annual'    => ['full' => 92, 'part' => 35],
                'service'   => ['online' => 71, 'onsite' => 56],
                'trial'     => ['full' => 11, 'part' => 7],
                'color'     => 'bg-neutral-800',
            ],
            [
                'program'   => 'DISC',
                'employees' => 57,
                'centers'   => 5,
                'annual'    => ['full' => 12, 'part' => 6],
                'service'   => ['online' => 23, 'onsite' => 9],
                'trial'     => ['full' => 5, 'part' => 2],
                'color'     => 'bg-secondary-medium',
            ],
        ];
    @endphp

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach ($cards as $card)
            <article
                class="relative overflow-hidden rounded-3xl bg-white border border-neutral-200 shadow-md hover:shadow-lg transition-transform hover:-translate-y-1">

                <div class="absolute top-0 left-0 w-full h-1.5 {{ $card['color'] }}"></div>

                <div class="flex justify-between items-star mt-2  p-4 border-b border-neutral-200">
                    <div class="flex flex-col gap-7 ">
                        <div class="text-center flex flex-row justify-between h-full">
                            <h3 class="text-lg font-bold text-neutral-900 leading-tight">{{ $card['program'] }}</h3>
                        </div>
                        <div class="text-center flex flex-row justify-start h-full gap-3">
                            <p class="text-sm text-neutral-500">Employees:</p>
                            <p class="text-md font-bold mt-0.5 text-primary  leading-none">{{ $card['employees'] }}</p>
                        </div>

                    </div>

                    <div class="text-left flex flex-col justify-end h-full gap-7">
                        <div class="flex items-center ">
                                <span
                                    class="px-2 py-0.5 text-xs rounded-full {{ $card['color'] }} text-white font-medium">
                                    Program
                                </span>
                        </div>
                        <p class="text-md  text-neutral-500">Centers: <span
                                class="font-bold text-md text-neutral-700">{{ $card['centers'] }}</span></p>
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-2 p-3">
                    @foreach (['annual' => 'Annual', 'service' => 'Service', 'trial' => 'Trial'] as $key => $label)
                        @php
                            $data = $card[$key];
                            $isService = $key === 'service';
                            $total = $isService
                                ? ($data['online'] + $data['onsite'])
                                : ($data['full'] + $data['part']);
                        @endphp

                        <div
                            class="rounded-xl bg-neutral-50 border border-neutral-200 p-2.5 shadow-inner hover:bg-neutral-100 transition">
                            <p class="text-xs font-semibold text-neutral-800 border-b border-neutral-200 pb-1 mb-1">{{ $label }}</p>
                            <div class="space-y-0.5 text-xs">
                                @if ($isService)
                                    <div class="flex justify-between">
                                        <span class="text-neutral-600">Online</span>
                                        <span class="font-semibold text-neutral-800">{{ $data['online'] }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-neutral-600">Onsite</span>
                                        <span class="font-semibold text-neutral-800">{{ $data['onsite'] }}</span>
                                    </div>
                                @else
                                    <div class="flex justify-between">
                                        <span class="text-neutral-600">Full</span>
                                        <span class="font-semibold text-neutral-800">{{ $data['full'] }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-neutral-600">Part</span>
                                        <span class="font-semibold text-neutral-800">{{ $data['part'] }}</span>
                                    </div>
                                @endif

                                <div class="border-t border-neutral-200 pt-1 mt-1 flex justify-between">
                                    <span class="text-neutral-600">Total</span>
                                    <span class="font-bold text-primary-500">{{ $total }}</span>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="flex justify-end p-3">
                    <a href="javascript:void(0)" class="text-sm font-semibold text-primary-600 hover:underline">
                        View Details →
                    </a>
                </div>
            </article>
        @endforeach
    </div>
</div>
