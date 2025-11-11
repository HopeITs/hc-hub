<div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-8 flex items-center justify-between">
        <h1 class="text-3xl font-bold text-neutral-800 tracking-tight">Contract Statistics</h1>
        <div class="flex items-center gap-3">
            <button wire:click="applyFilters"
                    type="button"
                    class="inline-flex items-center gap-2 rounded-xl bg-primary-500 text-white px-5 py-2 font-semibold shadow hover:bg-primary-600 transition-transform hover:scale-105">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M10 18h4v-2h-4v2Zm-7-8v2h18v-2H3Zm3-6v2h12V4H6Zm-3 16h18v-2H3v2Z"/>
                </svg>
                Apply
            </button>

            <button wire:click="resetFilters"
                    type="button"
                    class="inline-flex items-center gap-2 rounded-xl border border-neutral-300 bg-white text-neutral-700 px-5 py-2 font-semibold hover:bg-neutral-100 transition">
                Reset
            </button>
        </div>
    </div>

    <div class="mb-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3 items-end">
        <div>
            <label for="city" class="block text-sm font-medium text-neutral-700 mb-1">City</label>
            <select id="city" wire:model="selectedCity"
                    class="w-full rounded-xl border border-neutral-200 bg-white text-neutral-800 shadow-sm focus:ring-4 focus:ring-primary-200 px-3 py-2 transition">
                <option value="">All Cities</option>
                @foreach($cities as $city)
                    <option value="{{ $city->id }}">{{ $city->name_en }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="program" class="block text-sm font-medium text-neutral-700 mb-1">Program</label>
            <select id="program" wire:model="selectedProgram"
                    class="w-full rounded-xl border border-neutral-200 bg-white text-neutral-800 shadow-sm focus:ring-4 focus:ring-primary-200 px-3 py-2 transition">
                <option value="">All Programs</option>
                @foreach($programs as $program)
                    <option value="{{ $program->id }}">{{ $program->name }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div wire:loading.flex class="justify-center py-6">
        <div class="text-sm text-neutral-500 flex items-center gap-2">
            <div class="h-2 w-2 bg-primary-500 rounded-full animate-pulse"></div>
            Updating statistics...
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" wire:loading.remove>
        @forelse ($cards as $card)
            <article class="relative overflow-hidden rounded-3xl bg-white border border-neutral-200 shadow-md hover:shadow-lg transition-transform hover:-translate-y-1">
                <div class="absolute top-0 left-0 w-full h-1.5 {{ $card['color'] }}"></div>

                <div class="flex justify-between items-start mt-2 p-4 border-b border-neutral-200">
                    <div class="flex flex-col gap-2">
                        <h3 class="text-lg font-bold text-neutral-900 leading-tight">{{ $card['program'] }}</h3>
                        <div class="text-sm text-neutral-500">
                            Employees:
                            <span class="font-bold text-primary-600">{{ $card['employees'] }}</span>
                        </div>
                    </div>
                    <div class="text-right flex flex-col gap-2">
                        <span class="px-2 py-0.5 text-xs rounded-full {{ $card['color'] }} text-white font-medium">Program</span>
                        <p class="text-sm text-neutral-500">
                            Centers: <span class="font-bold text-neutral-700">{{ $card['centers'] }}</span>
                        </p>
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

                        <div class="rounded-xl bg-neutral-50 border border-neutral-200 p-2.5 shadow-inner hover:bg-neutral-100 transition">
                            <p class="text-xs font-semibold text-neutral-800 border-b border-neutral-200 pb-1 mb-1">{{ $label }}</p>
                            <div class="space-y-0.5 text-xs">
                                @if ($isService)
                                    <div class="flex justify-between"><span class="text-neutral-600">Online</span><span class="font-semibold text-neutral-800">{{ $data['online'] }}</span></div>
                                    <div class="flex justify-between"><span class="text-neutral-600">Onsite</span><span class="font-semibold text-neutral-800">{{ $data['onsite'] }}</span></div>
                                @else
                                    <div class="flex justify-between"><span class="text-neutral-600">Full</span><span class="font-semibold text-neutral-800">{{ $data['full'] }}</span></div>
                                    <div class="flex justify-between"><span class="text-neutral-600">Part</span><span class="font-semibold text-neutral-800">{{ $data['part'] }}</span></div>
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
        @empty
            <p class="col-span-full text-center text-neutral-500 py-8">No data found for the selected filters.</p>
        @endforelse
    </div>
</div>
