<?php

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Modules\Core\Models\Program;
use Modules\Hr\Support\Settings\HrAccess;
use Modules\Hr\Support\Settings\OrgCounts;

/*
 * HR's view of the programs: a read-only list with each program's
 * positions, governorates, centers and current headcount. Programs are Core
 * rows created from a Tracking proposal, so HR adds, edits and deletes
 * nothing here (user decision 2026-11-11).
 */
new #[Layout('layouts::desk'), Title('hr_settings.titles.programs')] class extends Component {
    use WithPagination;

    #[Url(except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $status = '';

    public function mount(): void
    {
        Gate::authorize('programs.view');

        $this->normalise();
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'status'], true)) {
            $this->normalise();
            $this->resetPage();
        }
    }

    private function normalise(): void
    {
        if (! in_array($this->status, ['active', 'inactive'], true)) {
            $this->status = '';
        }
    }

    /** @return LengthAwarePaginator<int, Program> */
    #[Computed]
    public function programs(): LengthAwarePaginator
    {
        $term = trim($this->search);

        return Program::query()
            ->select(['id', 'name', 'code', 'slug', 'is_active', 'manager_id', 'start_date', 'end_date'])
            ->with('manager:id,first_name_en,last_name_en,first_name_ar,last_name_ar')
            ->when($term !== '', fn ($query) => $query->where(fn ($match) => $match
                ->whereNameLike($term)
                ->orWhereLike('code', "%{$term}%")
                ->orWhereLike('slug', "%{$term}%")))
            ->when($this->status !== '', fn ($query) => $query->where('is_active', $this->status === 'active'))
            ->orderByDesc('is_active')
            ->orderByName()
            ->paginate(25);
    }

    /** @return list<int>|null */
    private function cityIds(): ?array
    {
        return HrAccess::for(Auth::user())->cityIds();
    }

    /** @return Collection<int, object> */
    #[Computed]
    public function positionCounts(): Collection
    {
        return OrgCounts::positions('program_id', $this->cityIds());
    }

    /** @return Collection<int, int> */
    #[Computed]
    public function employeeCounts(): Collection
    {
        return OrgCounts::employees('program_id', $this->cityIds());
    }
};
?>

@php
    $programs = $this->programs;
    $positionCounts = $this->positionCounts;
    $employeeCounts = $this->employeeCounts;
    $muted = 'text-secondary-medium dark:text-ink-muted';
    $badge = 'inline-flex items-center gap-1 rounded-md border px-2 py-0.5 text-xs font-semibold whitespace-nowrap';
@endphp

<x-desk.register class="admin-directory @container/directory" heading-id="hr-programs-heading" :title="__('hr_settings.titles.programs')" :count="$programs->total()" data-hr-programs>
    <x-slot:search>
        <x-desk.register-search :label="__('hr_settings.programs.search')" wire:model.live.debounce.300ms="search" />
    </x-slot:search>
    <x-slot:filters>
        <x-ui.header-filter-select :label="__('common.status')" wire:model.live="status" :value="$status">
            <option value="">{{ __('common.all_statuses') }}</option>
            <option value="active">{{ __('common.active') }}</option>
            <option value="inactive">{{ __('hr_settings.inactive') }}</option>
        </x-ui.header-filter-select>
    </x-slot:filters>

    <p class="mb-3 type-meta {{ $muted }}" data-test="hr-programs-read-only">{{ __('hr_settings.programs.read_only_note') }}</p>

    @if ($programs->isEmpty())
        <x-ui.empty-state icon="rectangle-stack" :heading="__('hr_settings.no_matches')" :body="__('common.change_the_search_or_filters_and_try_again')" />
    @else
        <x-ui.data-table :caption="__('hr_settings.titles.programs')">
            <x-slot:head>
                <th>{{ __('common.program') }}</th>
                <th class="w-24!">{{ __('hr_settings.fields.code') }}</th>
                <th>{{ __('hr_settings.programs.manager') }}</th>
                <th class="w-24!">{{ __('hr_settings.cities.positions') }}</th>
                <th class="w-24!">{{ __('hr_settings.cities.employees') }}</th>
                <th class="w-24!">{{ __('common.cities') }}</th>
                <th class="w-24!">{{ __('hr_settings.cities.centers') }}</th>
                <th class="w-28!">{{ __('common.status') }}</th>
                <th class="w-14!"><span class="sr-only">{{ __('common.actions') }}</span></th>
            </x-slot:head>

            @foreach ($programs as $program)
                @php
                    $counts = $positionCounts->get($program->id);
                @endphp
                <tr wire:key="program-{{ $program->id }}" data-program-row="{{ $program->id }}" @class(['opacity-70' => ! $program->is_active])>
                    <x-ui.data-table.cell :label="__('common.program')">
                        <bdi class="font-semibold">{{ $program->name }}</bdi>
                        <span class="block font-mono text-xs {{ $muted }}" dir="ltr">{{ $program->slug }}</span>
                    </x-ui.data-table.cell>
                    <x-ui.data-table.cell :label="__('hr_settings.fields.code')"><bdi dir="ltr" class="font-mono text-xs">{{ trim((string) $program->code) ?: '—' }}</bdi></x-ui.data-table.cell>
                    <x-ui.data-table.cell :label="__('hr_settings.programs.manager')">
                        @if ($program->manager !== null)
                            <bdi>{{ $program->manager->name }}</bdi>
                        @else
                            <span class="{{ $muted }}">{{ __('common.not_assigned') }}</span>
                        @endif
                    </x-ui.data-table.cell>
                    <x-ui.data-table.cell :label="__('hr_settings.cities.positions')" numeric>{{ $counts->positions ?? 0 }}</x-ui.data-table.cell>
                    <x-ui.data-table.cell :label="__('hr_settings.cities.employees')" numeric>{{ $employeeCounts->get($program->id, 0) }}</x-ui.data-table.cell>
                    <x-ui.data-table.cell :label="__('common.cities')" numeric>{{ $counts->cities ?? 0 }}</x-ui.data-table.cell>
                    <x-ui.data-table.cell :label="__('hr_settings.cities.centers')" numeric>{{ $counts->centers ?? 0 }}</x-ui.data-table.cell>
                    <x-ui.data-table.cell :label="__('common.status')">
                        @if ($program->is_active)
                            <span class="{{ $badge }} border-status-good/30 bg-status-good-soft text-status-good">{{ __('common.active') }}</span>
                        @else
                            <span class="{{ $badge }} border-secondary-light text-secondary-dark dark:border-line-strong dark:text-ink">{{ __('hr_settings.inactive') }}</span>
                        @endif
                    </x-ui.data-table.cell>
                    <td class="px-3.5 py-2.5">
                        <span class="flex items-center justify-end">
                            <x-ui.button tone="ghost" size="sm" icon="eye" class="size-8!" :href="route('hr.settings.programs.show', $program)" wire:navigate :aria-label="__('common.view_name', ['name' => $program->name])" />
                        </span>
                    </td>
                </tr>
            @endforeach
        </x-ui.data-table>

        @if ($programs->hasPages())
            <div class="mt-4">
                <flux:pagination :paginator="$programs" class="text-secondary-medium! dark:text-ink-muted!" scroll-to="#hr-programs-heading" />
            </div>
        @endif
    @endif
</x-desk.register>
