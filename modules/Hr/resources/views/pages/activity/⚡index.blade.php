<?php

use Illuminate\Database\Query\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Modules\Core\Models\User;
use Modules\Hr\Models\Employee;

/**
 * The old HR app's activity log (hr_activity_log), read only: who changed
 * what and when, before the hub took over. Narrowed by area, event, person
 * and dates, newest first; each row folds out the old and new values.
 * New changes are recorded by the hub's own activity log.
 */
new #[Layout('layouts::desk'), Title('hr_communications.titles.activity')] class extends Component {
    use WithPagination;

    #[Url(except: '')]
    public string $area = '';

    #[Url(except: '')]
    public string $event = '';

    #[Url(except: '')]
    public string $causer = '';

    #[Url(except: '')]
    public string $from = '';

    #[Url(except: '')]
    public string $to = '';

    #[Url(except: '')]
    public string $search = '';

    public function mount(): void
    {
        abort_unless(auth()->user()->isAdministrator() || auth()->user()->can('activity-log.view'), 403);
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['area', 'event', 'causer', 'from', 'to', 'search'], true)) {
            $this->resetPage();
        }
    }

    public function resetFilters(): void
    {
        $this->reset('area', 'event', 'causer', 'from', 'to', 'search');
        $this->resetPage();
    }

    /**
     * @return list<string>
     */
    #[Computed]
    public function areas(): array
    {
        return Cache::remember('hr.activity.areas', 3600, fn (): array => DB::table('hr_activity_log')->whereNotNull('log_name')->distinct()->orderBy('log_name')->pluck('log_name')->map(fn (mixed $name): string => (string) $name)->all());
    }

    /**
     * Everyone who appears as the author of a change: user id => name.
     *
     * @return array<int, string>
     */
    #[Computed]
    public function causers(): array
    {
        $ids = Cache::remember('hr.activity.causers', 3600, fn (): array => DB::table('hr_activity_log')->whereNotNull('causer_id')->distinct()->pluck('causer_id')->map(fn (mixed $id): int => (int) $id)->all());

        return $this->namesOf($ids);
    }

    #[Computed]
    public function rows(): LengthAwarePaginator
    {
        $term = trim($this->search);

        return DB::table('hr_activity_log')
            ->select(['id', 'log_name', 'description', 'subject_type', 'subject_id', 'event', 'causer_id', 'properties', 'created_at'])
            ->when($this->area !== '', fn (Builder $query) => $query->where('log_name', $this->area))
            ->when(in_array($this->event, ['created', 'updated', 'deleted'], true), fn (Builder $query) => $query->where('event', $this->event))
            ->when(ctype_digit($this->causer), fn (Builder $query) => $query->where('causer_id', (int) $this->causer))
            ->when($this->validDate($this->from), fn (Builder $query) => $query->where('created_at', '>=', $this->from.' 00:00:00'))
            ->when($this->validDate($this->to), fn (Builder $query) => $query->where('created_at', '<=', $this->to.' 23:59:59'))
            ->when($term !== '', fn (Builder $query) => $query->where(fn (Builder $match) => $match
                ->where('description', 'like', '%'.App\Support\Like::escape($term).'%')
                ->orWhere('subject_type', 'like', '%'.App\Support\Like::escape($term).'%')
                ->orWhere('properties', 'like', '%'.App\Support\Like::escape($term).'%')))
            ->orderByDesc('id')
            ->paginate(25);
    }

    public function hasFilters(): bool
    {
        return $this->area !== '' || $this->event !== '' || $this->causer !== '' || $this->from !== '' || $this->to !== '' || trim($this->search) !== '';
    }

    /**
     * @param  list<int>  $ids
     * @return array<int, string>
     */
    public function namesOf(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        return User::query()
            ->select(['id', 'email', 'employee_id'])
            ->with('employee:'.implode(',', Employee::NameColumns))
            ->whereIn('id', $ids)
            ->get()
            ->mapWithKeys(fn (User $user): array => [(int) $user->id => (string) ($user->employee?->name ?: $user->email)])
            ->sortBy(fn (string $name): string => mb_strtolower($name))
            ->all();
    }

    /**
     * The changed fields of one entry: field => [old, new].
     *
     * @return array<string, array{0: string, 1: string}>
     */
    public function changesOf(?string $properties): array
    {
        $data = json_decode((string) $properties, true);

        if (! is_array($data)) {
            return [];
        }

        $new = is_array($data['attributes'] ?? null) ? $data['attributes'] : [];
        $old = is_array($data['old'] ?? null) ? $data['old'] : [];
        $changes = [];

        foreach (array_unique([...array_keys($old), ...array_keys($new)]) as $field) {
            $changes[(string) $field] = [$this->shown($old[$field] ?? null), $this->shown($new[$field] ?? null)];
        }

        return $changes;
    }

    private function shown(mixed $value): string
    {
        return match (true) {
            $value === null => '—',
            is_bool($value) => $value ? '✓' : '✗',
            is_scalar($value) => mb_strimwidth((string) $value, 0, 120, '…'),
            default => mb_strimwidth((string) json_encode($value, JSON_UNESCAPED_UNICODE), 0, 120, '…'),
        };
    }

    private function validDate(string $value): bool
    {
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1;
    }
}; ?>

@php
    $rows = $this->rows;
    $names = $this->causers;
    $eventTone = ['created' => 'dk-good', 'updated' => 'dk-act', 'deleted' => 'dk-bad'];
@endphp

<x-desk.register heading-id="hr-activity-heading" :title="__('hr_communications.titles.activity')" :count="$rows->total()">
    <x-slot:figures>{{ __('hr_communications.activity.intro') }}</x-slot:figures>

    <x-slot:search>
        <x-desk.register-search :label="__('hr_communications.activity.search')" wire:model.live.debounce.300ms="search" />
    </x-slot:search>

    <x-slot:filters>
        <x-ui.header-filter-select :label="__('hr_communications.activity.area')" wire:model.live="area" :value="$area" searchable>
            <option value="">{{ __('hr_communications.activity.all_areas') }}</option>
            @foreach ($this->areas as $option)
                <option value="{{ $option }}">{{ str_replace(['_', '-'], ' ', $option) }}</option>
            @endforeach
        </x-ui.header-filter-select>

        <x-ui.header-filter-select :label="__('hr_communications.activity.event')" wire:model.live="event" :value="$event">
            <option value="">{{ __('hr_communications.activity.all_events') }}</option>
            @foreach (['created', 'updated', 'deleted'] as $option)
                <option value="{{ $option }}">{{ __('hr_communications.activity.events.'.$option) }}</option>
            @endforeach
        </x-ui.header-filter-select>

        <x-ui.header-filter-select :label="__('hr_communications.activity.causer')" wire:model.live="causer" :value="$causer" searchable>
            <option value="">{{ __('hr_communications.activity.anyone') }}</option>
            @foreach ($names as $id => $name)
                <option value="{{ $id }}">{{ $name }}</option>
            @endforeach
        </x-ui.header-filter-select>

        <x-ui.form-input type="date" :label="__('hr_communications.activity.from')" wire:model.live="from" />
        <x-ui.form-input type="date" :label="__('hr_communications.activity.to')" wire:model.live="to" />
    </x-slot:filters>

    @if ($rows->isEmpty())
        <x-ui.empty-state icon="clock" :heading="__('hr_communications.activity.empty')">
            @if ($this->hasFilters())
                <x-slot:action>
                    <x-ui.button tone="ghost" size="sm" wire:click="resetFilters">{{ __('common.reset_filters') }}</x-ui.button>
                </x-slot:action>
            @endif
        </x-ui.empty-state>
    @else
        <div wire:loading.class="opacity-50">
            <x-ui.data-table :caption="__('hr_communications.titles.activity')">
                <x-slot:head>
                    <th class="w-40!">{{ __('hr_communications.activity.columns.when') }}</th>
                    <th class="w-44!">{{ __('hr_communications.activity.columns.who') }}</th>
                    <th class="w-28!">{{ __('hr_communications.activity.event') }}</th>
                    <th>{{ __('hr_communications.activity.columns.what') }}</th>
                </x-slot:head>

                @foreach ($rows as $row)
                    @php($changes = $this->changesOf($row->properties))
                    <tr wire:key="activity-{{ $row->id }}">
                        <x-ui.data-table.cell numeric>{{ \Illuminate\Support\Carbon::parse($row->created_at)->translatedFormat(__('formats.datetime')) }}</x-ui.data-table.cell>
                        <x-ui.data-table.cell :label="__('hr_communications.activity.columns.who')">{{ $row->causer_id ? ($names[(int) $row->causer_id] ?? '#'.$row->causer_id) : __('hr_communications.activity.system') }}</x-ui.data-table.cell>
                        <x-ui.data-table.cell :label="__('hr_communications.activity.event')">
                            @if ($row->event)
                                <span class="dk-pill {{ $eventTone[$row->event] ?? 'dk-mute' }}">{{ __('hr_communications.activity.events.'.$row->event) }}</span>
                            @else
                                —
                            @endif
                        </x-ui.data-table.cell>
                        <x-ui.data-table.cell :label="__('hr_communications.activity.columns.what')">
                            <span class="font-semibold">{{ str_replace(['_', '-'], ' ', (string) $row->log_name) }}</span>
                            <span class="type-meta text-secondary-medium dark:text-ink-muted">{{ class_basename((string) $row->subject_type) }}@if ($row->subject_id) #{{ $row->subject_id }}@endif · {{ $row->description }}</span>
                            @if ($changes !== [])
                                <details class="mt-1">
                                    <summary class="cursor-pointer text-sm">{{ trans_choice('hr_communications.activity.fields', count($changes), ['count' => count($changes)]) }}</summary>
                                    <dl class="mt-1 grid grid-cols-[auto_1fr] gap-x-3 gap-y-1 text-sm" dir="ltr">
                                        @foreach ($changes as $field => [$before, $after])
                                            <dt class="font-mono text-secondary-medium dark:text-ink-muted">{{ $field }}</dt>
                                            <dd class="break-all">@if ($before !== '—')<s class="text-secondary-medium">{{ $before }}</s> → @endif{{ $after }}</dd>
                                        @endforeach
                                    </dl>
                                </details>
                            @endif
                        </x-ui.data-table.cell>
                    </tr>
                @endforeach
            </x-ui.data-table>
        </div>

        <flux:pagination :paginator="$rows" />
    @endif
</x-desk.register>
