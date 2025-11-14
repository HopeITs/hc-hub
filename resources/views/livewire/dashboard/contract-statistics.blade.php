<div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-8 flex items-center justify-between">
        <h1 class="text-3xl font-bold text-neutral-800 tracking-tight">@lang('general.contract-statistics')</h1>
        <div class="flex items-center gap-3">
            <button wire:click="resetFilters"
                    type="button"
                    class="inline-flex items-center gap-2 rounded-xl border border-neutral-300 bg-white text-neutral-700 px-5 py-2 font-semibold hover:bg-neutral-100 transition">
                @lang('general.reset')
            </button>
        </div>
    </div>

    <div class="mb-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3 items-end">
        <div>
            <label for="city" class="block text-sm font-medium text-neutral-700 mb-1">{{ trans_choice('general.city', 1) }}</label>
            <select id="city" wire:model.lazy="selectedCity"
                    class="w-full rounded-xl border border-neutral-200 bg-white text-neutral-800 shadow-sm focus:ring-4 focus:ring-primary-200 px-3 py-2 transition">
                <option value="">{{ __('general.all') }} {{ trans_choice('general.city', 2) }}</option>
                @foreach($cities as $city)
                    <option value="{{ $city->id }}">{{ $city->name }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="program" class="block text-sm font-medium text-neutral-700 mb-1">{{ trans_choice('general.program', 1) }}</label>
            <select id="program" wire:model.lazy="selectedProgram"
                    class="w-full rounded-xl border border-neutral-200 bg-white text-neutral-800 shadow-sm focus:ring-4 focus:ring-primary-200 px-3 py-2 transition">
                <option value="">{{ __('general.all') }} {{ trans_choice('general.program', 2) }}</option>
                @foreach($programs as $program)
                    <option value="{{ $program->id }}">{{ $program->name }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div wire:loading.flex wire:target="applyFilters, resetFilters" class="justify-center py-6">
        <div class="text-sm text-neutral-500 flex items-center gap-2">
            <div class="h-2 w-2 bg-primary-500 rounded-full animate-pulse"></div>
            @lang('general.updating-statistics')
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" wire:loading.remove wire:target="applyFilters, resetFilters">
        @forelse ($cards as $card)
            <article class="relative overflow-hidden rounded-3xl bg-white border border-neutral-200 shadow-md hover:shadow-lg transition-transform hover:-translate-y-1">
                <div class="absolute top-0 left-0 w-full h-1.5 {{ $card['color'] }}"></div>

                <div class="flex justify-between items-start mt-2 p-4 border-b border-neutral-200">
                    <div class="flex flex-col gap-2">
                        <h3 class="text-lg font-bold text-neutral-900 leading-tight">{{ $card['name'] }}</h3>
                        <div class="text-sm text-neutral-500">
                            {{ trans_choice('general.employee', 2) }}:
                            <span class="font-bold text-primary-600">{{ $card['employees'] }}</span>
                        </div>
                    </div>
                    <div class="text-right flex flex-col gap-2">
                        <span class="px-2 py-0.5 text-xs rounded-full {{ $card['color'] }} text-white font-medium">
                            {{ $cardType === 'program' ? trans_choice('general.program', 1) : trans_choice('general.center', 1) }}
                        </span>
                        @if ($card['centers'] !== null)
                            <p class="text-sm text-neutral-500">
                                {{ trans_choice('general.center', 2) }}: <span class="font-bold text-neutral-700">{{ $card['centers'] }}</span>
                            </p>
                        @endif
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-2 p-3">
                    @foreach (['annual' => __('general.annual'), 'service' => __('general.service'), 'trial' => __('general.trial')] as $key => $label)
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
                                    <div class="flex justify-between"><span class="text-neutral-600">@lang('general.online')</span><span class="font-semibold text-neutral-800">{{ $data['online'] }}</span></div>
                                    <div class="flex justify-between"><span class="text-neutral-600">@lang('general.onsite')</span><span class="font-semibold text-neutral-800">{{ $data['onsite'] }}</span></div>
                                @else
                                    <div class="flex justify-between"><span class="text-neutral-600">@lang('general.full-time')</span><span class="font-semibold text-neutral-800">{{ $data['full'] }}</span></div>
                                    <div class="flex justify-between"><span class="text-neutral-600">@lang('general.part-time')</span><span class="font-semibold text-neutral-800">{{ $data['part'] }}</span></div>
                                @endif

                                <div class="border-t border-neutral-200 pt-1 mt-1 flex justify-between">
                                    <span class="text-neutral-600">@lang('general.total')</span>
                                    <span class="font-bold text-primary-500">{{ $total }}</span>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="flex justify-end p-3">
                    @if ($cardType === 'program' && isset($card['program_slug']))
                        <a href="{{ route('programs.show', ['program' => $card['program_slug']]) }}" class="text-sm font-semibold text-primary-600 hover:underline">
                            @lang('general.show-more') →
                        </a>
                    @elseif ($cardType === 'center' && isset($card['center_id']))
                        <a href="{{ route('centers.show', $card['center_id']) }}" class="text-sm font-semibold text-primary-600 hover:underline">
                            @lang('general.show-more') →
                        </a>
                    @else
                        <a href="javascript:void(0)" class="text-sm font-semibold text-primary-600 hover:underline">
                            @lang('general.show-more') →
                        </a>
                    @endif
                </div>
            </article>
        @empty
            <div class="col-span-full text-center text-neutral-500 py-8">
                <p>@lang('general.no-data-filters')</p>
            </div>
        @endforelse
    </div>
</div>
