<div>
    {{-- Filter bar --}}
    <div class="border-b border-neutral-200 bg-neutral-50/70 px-4 py-4">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
            <div class="relative w-full lg:max-w-xs">
                <span class="pointer-events-none absolute inset-y-0 start-3 flex items-center text-secondary-light">
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path stroke-linecap="round" d="m20 20-3-3"/></svg>
                </span>
                <input type="search" wire:model.live.debounce.350ms="search"
                       placeholder="{{ __('general.search-name-or-position') }}"
                       class="w-full rounded-surface border border-neutral-200 bg-white py-2 ps-8 pe-3 text-body font-medium text-secondary-dark placeholder:text-secondary-light focus:border-secondary-light focus:ring-2 focus:ring-secondary-soft" />
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <button type="button" wire:click="exportCsv"
                        class="flex items-center gap-2 rounded-surface border border-neutral-200 bg-white px-3 py-2 text-small font-semibold text-secondary-medium transition hover:border-secondary-light hover:bg-secondary-soft hover:text-primary">
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v12m0 0 4-4m-4 4-4-4M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2"/></svg>
                    @lang('general.export-csv')
                </button>
                @if($this->activeFilterCount > 0 || filled($search))
                    <button type="button" wire:click="resetFilters"
                            class="rounded-surface px-3 py-2 text-small font-semibold text-primary transition hover:bg-secondary-soft">
                        @lang('general.reset-filters')
                    </button>
                @endif
            </div>
        </div>

        <div class="mt-3 grid gap-2 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
            <x-forms.select-input wire:model.live="program_id">
                <option value="">@lang('general.all-programs')</option>
                @foreach($programs as $program)<option value="{{ $program->id }}">{{ $program->name }}</option>@endforeach
            </x-forms.select-input>
            <x-forms.select-input wire:model.live="department_id">
                <option value="">@lang('general.all-departments')</option>
                @foreach($departments as $department)<option value="{{ $department->id }}">{{ $department->name }}</option>@endforeach
            </x-forms.select-input>
            <x-forms.select-input wire:model.live="center_id">
                <option value="">@lang('general.all-centers')</option>
                @foreach($centers as $center)<option value="{{ $center->id }}">{{ $center->name }}</option>@endforeach
            </x-forms.select-input>
            <x-forms.select-input wire:model.live="city_id">
                <option value="">@lang('general.all-cities')</option>
                @foreach($cities as $city)<option value="{{ $city->id }}">{{ $city->name }}</option>@endforeach
            </x-forms.select-input>
            <x-forms.select-input wire:model.live="job_level_id">
                <option value="">@lang('general.all-job-levels')</option>
                @foreach($jobLevels as $level)<option value="{{ $level->id }}">{{ $level->name }}</option>@endforeach
            </x-forms.select-input>
            <x-forms.select-input wire:model.live="status">
                <option value="">@lang('general.all-statuses')</option>
                <option value="active">@lang('general.active')</option>
                <option value="board">@lang('general.board-member')</option>
            </x-forms.select-input>
        </div>
    </div>

    {{-- Results --}}
    <div class="relative min-h-[480px] px-4 py-4">
        <div wire:loading.flex wire:target="search,program_id,department_id,center_id,city_id,job_level_id,status,nextPage,previousPage,gotoPage"
             class="absolute inset-0 z-10 items-start justify-center pt-24">
            <div class="flex items-center gap-2 rounded-surface border border-neutral-200 bg-white px-4 py-3 text-body font-semibold text-secondary-medium shadow-e3">
                <x-icons.spinner class="size-5 animate-spin text-primary" /> @lang('general.loading')
            </div>
        </div>

        <div class="mb-3 text-body font-semibold text-secondary-medium">
            {{ trans_choice('general.search-results-count', $employees->total(), ['count' => number_format($employees->total())]) }}
        </div>

        @if($employees->isEmpty())
            <div class="flex min-h-[360px] items-center justify-center">
                <div class="max-w-md rounded-surface border border-dashed border-neutral-300 bg-white px-8 py-12 text-center">
                    <span class="mx-auto flex size-14 items-center justify-center rounded-surface bg-secondary-soft text-primary">
                        <x-icons.user class="size-7" />
                    </span>
                    <h2 class="mt-4 text-h4 font-bold text-secondary-dark">@lang('general.no-search-results')</h2>
                    <p class="mt-2 text-body text-secondary-light">@lang('general.no-search-results-help')</p>
                </div>
            </div>
        @else
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                @foreach($employees as $employee)
                    @php($position = $employee->contracts->first()?->position)
                    <button type="button"
                            wire:click="$dispatch('open-profile', { employeeId: {{ $employee->id }} })"
                            wire:key="dir-{{ $employee->id }}"
                            class="group flex items-center gap-3 rounded-surface border border-neutral-200 bg-white p-3 text-start shadow-e1 transition hover:-translate-y-0.5 hover:border-secondary-light hover:shadow-e2">
                        <div class="relative shrink-0">
                            <img src="{{ $employee->getFirstMediaUrl('avatar') }}" alt="{{ $employee->name }}" loading="lazy"
                                 class="size-14 rounded-full object-cover ring-2 ring-white outline outline-1 outline-neutral-200 group-hover:outline-secondary-medium">
                            @if($employee->is_board_member)
                                <span class="absolute -bottom-0.5 -end-0.5 flex size-5 items-center justify-center rounded-full border-2 border-white bg-primary text-caption font-bold text-white">★</span>
                            @endif
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="truncate text-body font-extrabold text-secondary-dark">{{ $employee->name }}</div>
                            <div class="truncate text-small font-semibold text-secondary-medium">{{ $position?->title ?? __('general.not-specified') }}</div>
                            <div class="mt-1 flex items-center gap-1 truncate text-caption text-secondary-light">
                                <x-icons.map-pin class="size-3 shrink-0" />
                                <span class="truncate">{{ $position?->center?->name ?? $position?->city?->name ?? $employee->city?->name ?? __('general.not-specified') }}</span>
                            </div>
                        </div>
                    </button>
                @endforeach
            </div>

            <div class="mt-4">
                {{ $employees->links() }}
            </div>
        @endif
    </div>
</div>
