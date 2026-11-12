{{--
    The employee directory's table. Included by ⚡index.blade.php; reads
    $employees (the paginator), $hasFilters and the page's presence dots.
--}}
@use('Modules\Core\Models\City')
@use('Modules\Hr\Enums\EmployeeStatus')
@use('Modules\Hr\Models\Employee')

@php
    $muted = 'text-secondary-medium dark:text-ink-muted';
    $canUpdate = auth()->user()->can('employees.update');
    $canDelete = auth()->user()->can('employees.delete');
    $presence = $this->presence;
@endphp

@if ($employees->isEmpty())
    <x-ui.empty-state icon="users" :heading="__('hr_employees.empty.heading')" :body="$hasFilters ? __('common.change_the_search_or_filters_and_try_again') : __('hr_employees.empty.body')">
        @if ($hasFilters)
            <x-slot:action>
                <x-ui.button tone="primary" icon="arrow-path" wire:click="clearFilters">{{ __('common.reset_filters') }}</x-ui.button>
            </x-slot:action>
        @endif
    </x-ui.empty-state>
@else
    <x-ui.data-table :caption="__('hr_employees.titles.index')">
        <x-slot:head>
            <th>{{ __('common.name') }}</th>
            <th>{{ __('hr_employees.fields.position') }}</th>
            <th>{{ __('hr_employees.fields.work_email') }}</th>
            <th class="w-36!">{{ __('common.city') }}</th>
            <th class="w-40!">{{ __('common.status') }}</th>
            <th class="w-36!"><span class="sr-only">{{ __('common.actions') }}</span></th>
        </x-slot:head>

        @foreach ($employees as $employee)
            @php
                $status = EmployeeStatus::for($employee);
                $position = $employee->contracts->sortByDesc('start_date')->first()?->position;
                $reviewAfter = match ($status) {
                    EmployeeStatus::Incomplete => Employee::DRAFT_REVIEW_DAYS,
                    EmployeeStatus::AwaitingContract => Employee::AWAITING_REVIEW_DAYS,
                    default => null,
                };
                $needsReview = $reviewAfter !== null && ($employee->updated_at?->lt(now()->subDays($reviewAfter)) ?? false);
            @endphp
            <tr wire:key="employee-{{ $employee->id }}" data-employee="{{ $employee->id }}">
                <td>
                    <span class="flex min-w-0 items-center gap-2.5">
                        <span class="relative shrink-0">
                            <x-ui.avatar :src="Employee::photoUrl($employee->id)" class="size-9 rounded-full" />
                            <x-hr::employees.presence :status="$presence[$employee->id] ?? null" on-avatar />
                        </span>
                        <span class="min-w-0">
                            <span class="block truncate font-semibold" dir="auto">{{ $employee->name }}</span>
                            <span class="block truncate text-xs {{ $muted }}" dir="auto">{{ $employee->secondary_name }}</span>
                        </span>
                    </span>
                </td>
                <td>
                    @if ($position !== null)
                        <span class="block" dir="auto">{{ $position->title }}</span>
                    @else
                        <span class="{{ $muted }}">—</span>
                    @endif
                </td>
                <td>
                    @if ($employee->account?->email)
                        <span dir="ltr" class="break-all">{{ $employee->account->email }}</span>
                    @else
                        <span class="{{ $muted }}">—</span>
                    @endif
                </td>
                <td>{{ City::nameOf($employee->city_id) ?: '—' }}</td>
                <td>
                    <x-hr::employees.status-badge :status="$status" />
                    @if ($needsReview)
                        <span class="ms-1 rounded-md bg-status-warn-soft px-1.5 py-0.5 text-xs font-semibold text-status-warn" data-test="needs-review">{{ __('hr_employees.needs_review') }}</span>
                    @endif
                </td>
                <td>
                    <span class="flex items-center justify-end gap-1">
                        @if ($employee->username)
                            <x-ui.button tone="ghost" size="sm" icon="eye" :href="route('hr.employees.show', $employee->username)" wire:navigate :aria-label="__('common.view_name', ['name' => $employee->name])" />
                        @endif
                        @if ($canUpdate)
                            <x-ui.button tone="ghost" size="sm" icon="pencil-square" :href="route('hr.employees.edit', $employee->id)" wire:navigate :aria-label="__('common.edit_name', ['name' => $employee->name])" />
                            @unless ($employee->isDraft() || $employee->isAwaitingContract())
                                <x-ui.button tone="ghost" size="sm" :icon="$employee->is_active ? 'pause' : 'play'" wire:click="confirm('toggle', {{ $employee->id }})" :aria-label="$employee->is_active ? __('hr_employees.actions.deactivate_name', ['name' => $employee->name]) : __('hr_employees.actions.activate_name', ['name' => $employee->name])" />
                            @endunless
                        @endif
                        @if ($canDelete)
                            <x-ui.button tone="ghost" size="sm" icon="trash" wire:click="confirm('delete', {{ $employee->id }})" :aria-label="__('common.delete_name', ['name' => $employee->name])" />
                        @endif
                    </span>
                </td>
            </tr>
        @endforeach
    </x-ui.data-table>

    <div class="mt-3">
        <flux:pagination :paginator="$employees" scroll-to="#hr-employees-heading" />
    </div>
@endif
