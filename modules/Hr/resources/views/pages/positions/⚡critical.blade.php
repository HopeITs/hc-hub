<?php

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Modules\Hr\Models\Position;
use Modules\Hr\Support\CriticalPositions\CriticalPositionAlerts;
use Modules\Hr\Support\Employees\HrAccess;

/*
 * The critical positions in the viewer's scope and which of them need a
 * replacement. Only those who may open the replacements panel see it (HR of
 * the city, the general manager, the administrators).
 */
new #[Layout('layouts::desk'), Title('hr_positions.critical.title')] class extends Component {
    public function mount(): void
    {
        abort_unless(Auth::user()?->can('positions.view-replacements'), 403);
    }

    /** @return Collection<int, array{position: Position, needs: bool}> */
    #[Computed]
    public function rows(): Collection
    {
        $user = Auth::user();
        $cityIds = HrAccess::cityIds($user);
        $alerts = app(CriticalPositionAlerts::class);

        return Position::query()
            ->where('is_critical', true)
            ->when($cityIds !== null, fn ($query) => $query->inCity($cityIds))
            ->with(['jobLevel:id,name_en,name_ar,grade', 'activeContracts'])
            ->orderBy(app()->getLocale() === 'ar' ? 'title_ar' : 'title_en')
            ->get()
            ->filter(fn (Position $position): bool => Gate::forUser($user)->allows('viewReplacements', $position))
            ->map(fn (Position $position): array => ['position' => $position, 'needs' => $alerts->needsReplacement($position)])
            ->values();
    }
};
?>

@use('Modules\Core\Models\City')

@php
    $rows = $this->rows;
    $muted = 'text-secondary-medium dark:text-ink-muted';
@endphp

<x-desk.register heading-id="hr-critical-positions-heading" :title="__('hr_positions.critical.title')" :count="$rows->count()" data-critical-positions>
    @if ($rows->isEmpty())
        <x-ui.empty-state icon="briefcase" :heading="__('hr_positions.critical.none_title')" :body="__('hr_positions.critical.none_help')" />
    @else
        <x-ui.data-table :caption="__('hr_positions.critical.title')">
            <x-slot:head>
                <th>{{ __('hr_positions.fields.title') }}</th>
                <th>{{ __('common.city') }}</th>
                <th>{{ __('hr_positions.critical.holders') }}</th>
                <th class="w-52!"><span class="sr-only">{{ __('common.actions') }}</span></th>
            </x-slot:head>

            @foreach ($rows as $row)
                @php($position = $row['position'])
                <tr wire:key="critical-{{ $position->id }}" data-critical-position="{{ $position->id }}">
                    <td>
                        <span class="block font-semibold" dir="auto">{{ $position->title }}</span>
                        @if ($row['needs'])
                            <span class="block text-xs font-semibold text-status-critical">{{ __('hr_positions.critical.needs_replacement') }}</span>
                        @endif
                    </td>
                    <td>{{ City::nameOf($position->city_id) }}</td>
                    <td class="text-sm {{ $muted }}" dir="auto">{{ $position->activeContracts->map(fn ($contract) => $contract->employee?->name)->filter()->implode(', ') ?: '—' }}</td>
                    <td>
                        <span class="flex items-center justify-end">
                            <x-ui.button size="sm" :href="route('hr.positions.replacements', $position->id)" wire:navigate data-test="open-replacements">{{ __('hr_positions.critical.suggested_replacements') }}</x-ui.button>
                        </span>
                    </td>
                </tr>
            @endforeach
        </x-ui.data-table>
    @endif
</x-desk.register>
