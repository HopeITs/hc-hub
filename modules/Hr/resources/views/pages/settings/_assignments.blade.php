{{--
    The "working here now" panel of the program and center pages
    (ListsAssignedEmployees). $show: which of 'program' / 'center' to name
    beside each position (the page already is the other one).
--}}
@php
    $assignments = $this->assignments;
    $muted = 'text-secondary-medium dark:text-ink-muted';
@endphp

@if ($assignments !== null)
    <x-ui.panel :title="__('hr_settings.people.title')" :count="$assignments->total()" id="hr-assignments" data-hr-assignments>
        <x-slot:toolbar>
            <x-desk.register-search class="w-full sm:w-64" :label="__('hr_settings.people.search')" wire:model.live.debounce.300ms="employeeSearch" />
        </x-slot:toolbar>

        @if ($assignments->isEmpty())
            <x-ui.empty-state icon="users" :heading="trim($employeeSearch) === '' ? __('hr_settings.people.none') : __('hr_settings.no_matches')" class="min-h-40!" />
        @else
            <ul class="divide-y divide-panel-border">
                @foreach ($assignments as $assignment)
                    @php
                        $position = $assignment->position;
                        $place = $show === 'program'
                            ? \Modules\Core\Models\Program::nameOf($position?->program_id)
                            : \Modules\Core\Models\Center::nameOf($position?->center_id);
                    @endphp
                    <li wire:key="assignment-{{ $assignment->id }}" class="flex flex-wrap items-center gap-x-4 gap-y-1 px-4 py-2.5">
                        <div class="flex min-w-0 flex-1 basis-56 items-center gap-2.5">
                            <x-ui.avatar :src="\Modules\Hr\Models\Employee::avatarUrl($assignment->employee_id)" class="size-8 shrink-0 rounded-full" aria-hidden="true" />
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold"><bdi>{{ $assignment->employee?->name ?? '—' }}</bdi></p>
                                <p class="truncate text-xs {{ $muted }}"><bdi>{{ $position?->title ?? '—' }}</bdi></p>
                            </div>
                        </div>
                        <p class="min-w-0 basis-40 truncate text-xs {{ $muted }}"><bdi>{{ $place !== '' ? $place : '—' }}</bdi> · <bdi>{{ \Modules\Core\Models\City::nameOf($position?->city_id) }}</bdi></p>
                        <p class="basis-40 truncate text-xs {{ $muted }}"><bdi>{{ $assignment->contractType?->name ?? '—' }}</bdi></p>
                    </li>
                @endforeach
            </ul>

            @if ($assignments->hasPages())
                <x-slot:footer>
                    <div class="pt-3">
                        <flux:pagination :paginator="$assignments" class="text-secondary-medium! dark:text-ink-muted!" scroll-to="#hr-assignments" />
                    </div>
                </x-slot:footer>
            @endif
        @endif
    </x-ui.panel>
@endif
