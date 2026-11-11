<?php

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Modules\Hr\Enums\BadgeType;
use Modules\Hr\Models\Badge;
use Modules\Hr\Support\Employees\HrAccess;

/*
 * Every badge (warning, reward, raise) the viewer may see: HR within its
 * cities, the general manager everywhere, a manager for their reports.
 * Badges are given and edited on the employee's profile (Badges tab).
 */
new #[Layout('layouts::desk'), Title('hr_employees.titles.badges')] class extends Component {
    use WithPagination;

    #[Url(except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $type = '';

    public function mount(): void
    {
        Gate::authorize('viewAny', Badge::class);
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'type'], true)) {
            $this->resetPage();
        }
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'type']);
        $this->resetPage();
    }

    #[Computed]
    public function badges(): LengthAwarePaginator
    {
        $user = Auth::user();
        $columns = ['id', 'employee_id', 'type', 'subtype', 'date', 'reason', 'notes', 'amount', 'currency', 'new_position', 'leave_unit'];

        return Badge::query()
            ->visibleTo($user)
            ->select($columns)
            ->with('employee:id,first_name_en,last_name_en,first_name_ar,last_name_ar,username,city_id')
            ->when($this->type !== '', fn ($query) => $query->where('type', (int) $this->type))
            ->when(trim($this->search) !== '', fn ($query) => $query->whereHas('employee', fn ($employee) => $employee->search($this->search)))
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->paginate(25);
    }

    public function showPay(): bool
    {
        return HrAccess::canViewSalary(Auth::user());
    }
};
?>

@use('Modules\Core\Models\City')
@use('Modules\Hr\Support\Employees\EnumLabel')

@php
    $badges = $this->badges;
    $hasFilters = $search !== '' || $type !== '';
    $showPay = $this->showPay();
    $muted = 'text-secondary-medium dark:text-ink-muted';
@endphp

<x-desk.register class="@container/directory" heading-id="hr-badges-heading" :title="__('hr_employees.titles.badges')" :count="$badges->total()">
    <x-slot:search>
        <x-desk.register-search id="hr-badge-search" :label="__('hr_employees.search')" :placeholder="__('hr_employees.search_placeholder')" wire:model.live.debounce.300ms="search" />
    </x-slot:search>

    <x-slot:filters>
        <x-ui.header-filter-select id="hr-badge-type" :label="__('hr_employees.badges.type')" wire:model.live="type" :value="$type">
            <option value="">{{ __('hr_employees.badges.all_types') }}</option>
            @foreach ([BadgeType::Warning, BadgeType::Reward, BadgeType::Salary_Increase] as $badgeType)
                <option value="{{ $badgeType->value }}">{{ EnumLabel::of(BadgeType::class, $badgeType->value) }}</option>
            @endforeach
        </x-ui.header-filter-select>
    </x-slot:filters>

    @if ($badges->isEmpty())
        <x-ui.empty-state icon="star" :heading="__('hr_employees.badges.empty_heading')" :body="$hasFilters ? __('common.change_the_search_or_filters_and_try_again') : __('hr_employees.badges.empty_body')">
            @if ($hasFilters)
                <x-slot:action>
                    <x-ui.button tone="primary" icon="arrow-path" wire:click="clearFilters">{{ __('common.reset_filters') }}</x-ui.button>
                </x-slot:action>
            @endif
        </x-ui.empty-state>
    @else
        <x-ui.data-table :caption="__('hr_employees.titles.badges')">
            <x-slot:head>
                <th class="w-56!">{{ __('hr_employees.badges.employee') }}</th>
                <th>{{ __('hr_employees.badges.kind') }}</th>
                <th>{{ __('hr_employees.badges.reason') }}</th>
                <th class="w-16!"><span class="sr-only">{{ __('common.actions') }}</span></th>
            </x-slot:head>

            @foreach ($badges as $badge)
                <tr wire:key="badge-{{ $badge->id }}" data-badge="{{ $badge->id }}">
                    <td>
                        <span class="block font-semibold" dir="auto">{{ $badge->employee?->name ?? '—' }}</span>
                        <span class="block text-xs {{ $muted }}">{{ City::nameOf($badge->employee?->city_id) }}</span>
                    </td>
                    <td><x-hr::employees.badge-summary :badge="$badge" :show-pay="$showPay" /></td>
                    <td class="max-w-md"><span class="line-clamp-2" dir="auto">{{ $badge->reason ?: '—' }}</span></td>
                    <td>
                        @if ($badge->employee?->username)
                            <x-ui.button tone="ghost" size="sm" icon="eye" :href="route('hr.employees.show', ['employee' => $badge->employee->username, 'tab' => 'badges'])" wire:navigate :aria-label="__('hr_employees.badges.view')" />
                        @endif
                    </td>
                </tr>
            @endforeach
        </x-ui.data-table>

        <div class="mt-3">
            <flux:pagination :paginator="$badges" scroll-to="#hr-badges-heading" />
        </div>
    @endif
</x-desk.register>
