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
use Modules\Core\Models\Center;
use Modules\Core\Models\City;
use Modules\Hr\Support\Settings\HrAccess;
use Modules\Hr\Support\Settings\OrgCounts;

/*
 * HR's view of the centers: a read-only list with each center's
 * governorate, working hours, positions and current headcount. A regional
 * HR role sees its own governorates' centers only. Centers are Core rows
 * created from a Tracking proposal: nothing is added or edited here.
 */
new #[Layout('layouts::desk'), Title('hr_settings.titles.centers')] class extends Component {
    use WithPagination;

    #[Url(except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $city = '';

    #[Url(except: '')]
    public string $status = '';

    public function mount(): void
    {
        Gate::authorize('centers.view');

        $this->normalise();
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'city', 'status'], true)) {
            $this->normalise();
            $this->resetPage();
        }
    }

    private function normalise(): void
    {
        if (! in_array($this->status, ['active', 'inactive'], true)) {
            $this->status = '';
        }

        if ($this->city !== '' && ! array_key_exists((int) $this->city, $this->cityOptions)) {
            $this->city = '';
        }
    }

    /** @return list<int>|null */
    private function cityIds(): ?array
    {
        return HrAccess::for(Auth::user())->cityIds();
    }

    /**
     * The governorates the viewer may filter by: id => name.
     *
     * @return array<int, string>
     */
    #[Computed]
    public function cityOptions(): array
    {
        $allowed = $this->cityIds();
        $options = City::options(withNational: false);

        return $allowed === null ? $options : array_intersect_key($options, array_flip($allowed));
    }

    /** @return LengthAwarePaginator<int, Center> */
    #[Computed]
    public function centers(): LengthAwarePaginator
    {
        return HrAccess::for(Auth::user())
            ->scopeToCities(Center::query())
            ->select(['id', 'name_en', 'name_ar', 'city_id', 'open_at', 'close_at', 'address_en', 'address_ar', 'is_active'])
            ->search($this->search)
            ->when($this->city !== '', fn ($query) => $query->where('city_id', (int) $this->city))
            ->when($this->status !== '', fn ($query) => $query->where('is_active', $this->status === 'active'))
            ->withCount('positions')
            ->orderByDesc('is_active')
            ->orderBy(Center::nameColumn())
            ->orderBy('id')
            ->paginate(25);
    }

    /** @return Collection<int, int> */
    #[Computed]
    public function employeeCounts(): Collection
    {
        return OrgCounts::employees('center_id', $this->cityIds());
    }
};
?>

@php
    $centers = $this->centers;
    $employeeCounts = $this->employeeCounts;
    $muted = 'text-secondary-medium dark:text-ink-muted';
    $badge = 'inline-flex items-center gap-1 rounded-md border px-2 py-0.5 text-xs font-semibold whitespace-nowrap';
    $arabic = app()->getLocale() === 'ar';
@endphp

<x-desk.register class="admin-directory @container/directory" heading-id="hr-centers-heading" :title="__('hr_settings.titles.centers')" :count="$centers->total()" data-hr-centers>
    <x-slot:search>
        <x-desk.register-search :label="__('hr_settings.centers.search')" wire:model.live.debounce.300ms="search" />
    </x-slot:search>
    <x-slot:filters>
        @if (count($this->cityOptions) > 1)
            <x-ui.header-filter-select :label="__('common.city')" wire:model.live="city" :value="$city">
                <option value="">{{ __('hr_settings.centers.all_cities') }}</option>
                @foreach ($this->cityOptions as $cityId => $cityName)
                    <option value="{{ $cityId }}">{{ $cityName }}</option>
                @endforeach
            </x-ui.header-filter-select>
        @endif
        <x-ui.header-filter-select :label="__('common.status')" wire:model.live="status" :value="$status">
            <option value="">{{ __('common.all_statuses') }}</option>
            <option value="active">{{ __('common.active') }}</option>
            <option value="inactive">{{ __('hr_settings.inactive') }}</option>
        </x-ui.header-filter-select>
    </x-slot:filters>

    <p class="mb-3 type-meta {{ $muted }}" data-test="hr-centers-read-only">{{ __('hr_settings.centers.read_only_note') }}</p>

    @if ($centers->isEmpty())
        <x-ui.empty-state icon="building-office-2" :heading="__('hr_settings.no_matches')" :body="__('common.change_the_search_or_filters_and_try_again')" />
    @else
        <x-ui.data-table :caption="__('hr_settings.titles.centers')">
            <x-slot:head>
                <th>{{ __('hr_settings.centers.center') }}</th>
                <th class="w-36!">{{ __('common.city') }}</th>
                <th class="w-36!">{{ __('hr_settings.centers.hours') }}</th>
                <th>{{ __('hr_settings.centers.address') }}</th>
                <th class="w-24!">{{ __('hr_settings.cities.positions') }}</th>
                <th class="w-24!">{{ __('hr_settings.cities.employees') }}</th>
                <th class="w-28!">{{ __('common.status') }}</th>
                <th class="w-14!"><span class="sr-only">{{ __('common.actions') }}</span></th>
            </x-slot:head>

            @foreach ($centers as $center)
                @php
                    $address = trim((string) ($arabic ? ($center->address_ar ?: $center->address_en) : ($center->address_en ?: $center->address_ar)));
                    $hours = OrgCounts::hours($center->open_at, $center->close_at);
                @endphp
                <tr wire:key="center-{{ $center->id }}" data-center-row="{{ $center->id }}" @class(['opacity-70' => ! $center->is_active])>
                    <x-ui.data-table.cell :label="__('hr_settings.centers.center')">
                        <bdi class="font-semibold">{{ $center->name }}</bdi>
                        @if ($center->secondary_name !== '' && $center->secondary_name !== $center->name)
                            <span class="block text-xs {{ $muted }}" dir="auto">{{ $center->secondary_name }}</span>
                        @endif
                    </x-ui.data-table.cell>
                    <x-ui.data-table.cell :label="__('common.city')"><bdi>{{ City::nameOf($center->city_id) }}</bdi></x-ui.data-table.cell>
                    <x-ui.data-table.cell :label="__('hr_settings.centers.hours')">
                        @if ($hours !== '')
                            <bdi dir="ltr" class="tabular-nums">{{ $hours }}</bdi>
                        @else
                            <span class="{{ $muted }}">—</span>
                        @endif
                    </x-ui.data-table.cell>
                    <x-ui.data-table.cell :label="__('hr_settings.centers.address')">
                        @if ($address !== '')
                            <span dir="auto">{{ $address }}</span>
                        @else
                            <span class="{{ $muted }}">—</span>
                        @endif
                    </x-ui.data-table.cell>
                    <x-ui.data-table.cell :label="__('hr_settings.cities.positions')" numeric>{{ $center->positions_count }}</x-ui.data-table.cell>
                    <x-ui.data-table.cell :label="__('hr_settings.cities.employees')" numeric>{{ $employeeCounts->get($center->id, 0) }}</x-ui.data-table.cell>
                    <x-ui.data-table.cell :label="__('common.status')">
                        @if ($center->is_active)
                            <span class="{{ $badge }} border-status-good/30 bg-status-good-soft text-status-good">{{ __('common.active') }}</span>
                        @else
                            <span class="{{ $badge }} border-secondary-light text-secondary-dark dark:border-line-strong dark:text-ink">{{ __('hr_settings.inactive') }}</span>
                        @endif
                    </x-ui.data-table.cell>
                    <td class="px-3.5 py-2.5">
                        <span class="flex items-center justify-end">
                            <x-ui.button tone="ghost" size="sm" icon="eye" class="size-8!" :href="route('hr.settings.centers.show', $center->id)" wire:navigate :aria-label="__('common.view_name', ['name' => $center->name])" />
                        </span>
                    </td>
                </tr>
            @endforeach
        </x-ui.data-table>

        @if ($centers->hasPages())
            <div class="mt-4">
                <flux:pagination :paginator="$centers" class="text-secondary-medium! dark:text-ink-muted!" scroll-to="#hr-centers-heading" />
            </div>
        @endif
    @endif
</x-desk.register>
