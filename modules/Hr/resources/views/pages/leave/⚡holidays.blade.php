<?php

use Flux\Flux;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Modules\Hr\Actions\Leave\DeleteHoliday;
use Modules\Hr\Livewire\Concerns\Leave\ManagesHolidayForm;
use Modules\Hr\Models\Holiday;
use Modules\Hr\Support\Leave\HolidayProjects;

/*
 * Official holidays, one-off grants and the birthday holiday window
 * (team-coverage-contract §6.1). National HR sets anything; a program
 * manager sees the national holidays (read only) and those on their own
 * projects, and adds official holidays and grants to those projects (H-6).
 */
new #[Layout('layouts::desk'), Title('hr_leave_calendar.titles.holidays')] class extends Component {
    use ManagesHolidayForm;
    use WithPagination;

    public const KINDS = [Holiday::KIND_OFFICIAL, Holiday::KIND_GRANT, Holiday::KIND_BIRTHDAY];

    #[Url(except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $kind = '';

    public bool $showDeleteModal = false;

    #[Locked]
    public ?int $deletingHolidayId = null;

    public string $deletingHolidayName = '';

    public function mount(): void
    {
        Gate::authorize('viewAny', Holiday::class);

        if (! in_array($this->kind, self::KINDS, true)) {
            $this->kind = '';
        }
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'kind'], true)) {
            if (! in_array($this->kind, self::KINDS, true)) {
                $this->kind = '';
            }

            $this->resetPage();
        }
    }

    /** @return LengthAwarePaginator<int, Holiday> */
    #[Computed]
    public function holidays(): LengthAwarePaginator
    {
        return app(HolidayProjects::class)->visibleTo($this->holidayUser())
            ->select(['id', 'name_en', 'name_ar', 'scope', 'kind', 'start_date', 'end_date', 'is_recurring'])
            ->with(['projects:id,holiday_id,program_id,city_id', 'projects.program:id,name', 'projects.city:id,name_en,name_ar'])
            ->search($this->search)
            ->when($this->kind !== '', fn ($query) => $query->where('kind', $this->kind))
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->paginate(25);
    }

    public function confirmDeleteHoliday(int $holidayId): void
    {
        $holiday = Holiday::query()->with('projects:id,holiday_id,program_id,city_id')->findOrFail($holidayId);

        Gate::authorize('delete', $holiday);

        $this->deletingHolidayId = $holiday->id;
        $this->deletingHolidayName = (string) $holiday->name;
        $this->showDeleteModal = true;
    }

    public function deleteHoliday(): void
    {
        if ($this->deletingHolidayId === null) {
            return;
        }

        $holidayId = $this->deletingHolidayId;
        $this->cancelDeleteHoliday();

        app(DeleteHoliday::class)->handle($this->holidayUser(), $holidayId);

        $this->holidaysChanged();

        Flux::toast(variant: 'success', text: __('hr_leave_calendar.holidays.deleted'));
    }

    public function cancelDeleteHoliday(): void
    {
        $this->showDeleteModal = false;
        $this->deletingHolidayId = null;
        $this->deletingHolidayName = '';
    }

    protected function holidaysChanged(): void
    {
        unset($this->holidays);
    }
};
?>

@php
    $holidays = $this->holidays;
    $user = auth()->user();
    $projects = app(\Modules\Hr\Support\Leave\HolidayProjects::class);
    $muted = 'text-secondary-medium dark:text-ink-muted';
    $listSeparator = app()->getLocale() === 'ar' ? '، ' : ', ';
@endphp

<x-desk.register class="@container" heading-id="hr-holidays-heading" :title="__('hr_leave_calendar.titles.holidays')" :count="$holidays->total()" data-hr-holidays>
    <x-slot:actions>
        <x-ui.button tone="secondary" icon="cake" :href="route('hr.leave.birthday-choices')" wire:navigate>{{ __('hr_leave_calendar.holidays.birthday_choices') }}</x-ui.button>
        <x-ui.button tone="primary" icon="plus" wire:click="openCreateHoliday" data-test="create-holiday" data-quick-create>{{ __('hr_leave_calendar.holidays.add') }}</x-ui.button>
    </x-slot:actions>

    <x-slot:search>
        <x-desk.register-search :label="__('hr_leave_calendar.holidays.search')" :placeholder="__('hr_leave_calendar.holidays.search_placeholder')" wire:model.live.debounce.300ms="search" />
    </x-slot:search>

    <x-slot:filters>
        <x-ui.header-filter-select :label="__('hr_leave_calendar.holidays.kind')" wire:model.live="kind" :value="$kind">
            <option value="">{{ __('hr_leave_calendar.holidays.all_kinds') }}</option>
            @foreach ($this::KINDS as $kindOption)
                <option value="{{ $kindOption }}">{{ __('hr_leave_calendar.holidays.kinds.'.$kindOption) }}</option>
            @endforeach
        </x-ui.header-filter-select>
    </x-slot:filters>

    @unless ($this->isNationalHr)
        <p class="mb-3 type-meta {{ $muted }}">{{ __('hr_leave_calendar.holidays.manager_hint') }}</p>
    @endunless

    @if ($holidays->isEmpty())
        @if ($search === '' && $kind === '')
            <x-ui.empty-state icon="sun" :heading="__('hr_leave_calendar.holidays.empty_heading')" :body="__('hr_leave_calendar.holidays.empty_body')" />
        @else
            <x-ui.empty-state icon="sun" :heading="__('hr_leave_calendar.holidays.no_matching')" :body="__('common.change_the_search_or_filters_and_try_again')" />
        @endif
    @else
        <x-ui.data-table :caption="__('hr_leave_calendar.titles.holidays')">
            <x-slot:head>
                <th>{{ __('hr_leave_calendar.holidays.name') }}</th>
                <th class="w-40!">{{ __('hr_leave_calendar.holidays.kind') }}</th>
                <th>{{ __('hr_leave_calendar.holidays.applies_to') }}</th>
                <th class="w-32!">{{ __('hr_leave_calendar.holidays.from') }}</th>
                <th class="w-32!">{{ __('hr_leave_calendar.holidays.to') }}</th>
                <th class="w-28!">{{ __('hr_leave_calendar.holidays.recurring') }}</th>
                <th class="w-24!"><span class="sr-only">{{ __('common.actions') }}</span></th>
            </x-slot:head>

            @foreach ($holidays as $holiday)
                @php
                    $labels = $holiday->projects->map(fn ($link) => $link->program?->name.' — '.$link->city?->name);
                    $editable = $projects->canChange($user, $holiday);
                @endphp
                <tr wire:key="holiday-{{ $holiday->id }}" data-holiday="{{ $holiday->id }}">
                    <x-ui.data-table.cell :label="__('hr_leave_calendar.holidays.name')"><span class="font-semibold" dir="auto">{{ $holiday->name }}</span></x-ui.data-table.cell>
                    <x-ui.data-table.cell :label="__('hr_leave_calendar.holidays.kind')">{{ __('hr_leave_calendar.holidays.kinds.'.$holiday->kind) }}</x-ui.data-table.cell>
                    <x-ui.data-table.cell :label="__('hr_leave_calendar.holidays.applies_to')">
                        @if ($holiday->isNational())
                            {{ __('hr_leave_calendar.holidays.national') }}
                        @elseif ($labels->isEmpty())
                            <span class="text-status-critical">{{ __('hr_leave_calendar.holidays.no_projects') }}</span>
                        @else
                            <span dir="auto">{{ $labels->take(3)->join($listSeparator) }}</span>
                            @if ($labels->count() > 3)
                                <span class="{{ $muted }}">{{ __('hr_leave_calendar.holidays.more_projects', ['count' => $labels->count() - 3]) }}</span>
                            @endif
                        @endif
                    </x-ui.data-table.cell>
                    <x-ui.data-table.cell :label="__('hr_leave_calendar.holidays.from')"><span class="tabular-nums">{{ $holiday->start_date->translatedFormat(__('formats.date')) }}</span></x-ui.data-table.cell>
                    <x-ui.data-table.cell :label="__('hr_leave_calendar.holidays.to')"><span class="tabular-nums">{{ $holiday->end_date->translatedFormat(__('formats.date')) }}</span></x-ui.data-table.cell>
                    <x-ui.data-table.cell :label="__('hr_leave_calendar.holidays.recurring')">{{ $holiday->is_recurring ? __('hr_leave_calendar.holidays.yes') : __('hr_leave_calendar.holidays.no') }}</x-ui.data-table.cell>
                    <td class="px-3.5 py-2.5">
                        @if ($editable)
                            <span class="flex items-center justify-end gap-1">
                                <x-ui.button tone="ghost" size="sm" icon="pencil-square" class="size-8!" wire:click="openEditHoliday({{ $holiday->id }})" :aria-label="__('common.edit_name', ['name' => $holiday->name])" />
                                <x-ui.button tone="ghost" size="sm" icon="trash" class="size-8! text-status-critical!" wire:click="confirmDeleteHoliday({{ $holiday->id }})" :aria-label="__('common.delete_name', ['name' => $holiday->name])" />
                            </span>
                        @endif
                    </td>
                </tr>
            @endforeach
        </x-ui.data-table>

        <div class="mt-4">
            <flux:pagination :paginator="$holidays" class="text-secondary-medium! dark:text-ink-muted!" scroll-to="#hr-holidays-heading" />
        </div>
    @endif

    @include('hr::pages.leave._holidays-modal')

    <x-ui.confirm-modal
        wire:model="showDeleteModal"
        close="cancelDeleteHoliday"
        :title="__('hr_leave_calendar.holidays.delete_title', ['name' => $deletingHolidayName])"
        :message="__('hr_leave_calendar.holidays.delete_message')"
        confirm="deleteHoliday"
        :confirm-label="__('common.delete')"
        confirm-test="confirm-delete-holiday"
    />
</x-desk.register>
