{{--
    The usage report above the log: totals, the daily trend, the most active people,
    activity by city, the busiest hours and the people with no activity. Included by
    ⚡index; $r is ActivityReport::build().
--}}
@php
    use App\Support\ActivityReport;

    $daily = $r['daily']->values();
    $dMax = max(1, $daily->max('actions'));
    $n = max(1, $daily->count() - 1);
    $points = $daily->map(fn (array $day, int $i): string => round($i / $n * 600, 1).','.round(100 - $day['actions'] / $dMax * 96, 1))->implode(' ');
    $area = '0,100 '.$points.' 600,100';
    $hours = $r['hours'];
    $hMax = max(1, $hours->max('actions'));
    $peakHour = $hours->sortByDesc('actions')->first();
    $cityTotal = max(1, $r['cities']->sum('actions'));
    $uMax = max(1, (int) $r['topUsers']->max('actions'));
    $cMax = max(1, (int) $r['cities']->max('actions'));
    $inactiveShown = $r['inactive']->take($r['inactiveLimit']);
    $muted = 'text-secondary-medium dark:text-ink-muted';
    $bar = 'bg-secondary-dark dark:bg-ink';
@endphp

<div class="grid gap-4" data-activity-report>
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h2 class="text-lg font-semibold">{{ __('activity.report.title') }}</h2>
        <div class="flex flex-wrap items-center gap-2">
            @foreach (ActivityReport::PERIODS as $period)
                <x-ui.button size="sm" :tone="$r['days'] === $period ? 'neutral' : 'secondary'" wire:click="setReportDays({{ $period }})" aria-pressed="{{ $r['days'] === $period ? 'true' : 'false' }}" data-activity-report-days="{{ $period }}">
                    {{ __('activity.report.days_count', ['n' => $period]) }}
                </x-ui.button>
            @endforeach
            <x-ui.button size="sm" icon="arrow-down-tray" wire:click="exportReport" data-activity-report-export>{{ __('activity.report.export') }}</x-ui.button>
        </div>
    </div>

    <div class="grid gap-3 @md:grid-cols-3">
        <x-ui.stat-tile :label="__('activity.report.total')" :value="number_format($r['total'])" icon="bolt" />
        <x-ui.stat-tile :label="__('activity.report.active_users')" :value="number_format($r['activeUsers'])" icon="users" />
        <x-ui.stat-tile :label="__('activity.report.inactive')" :value="number_format($r['inactive']->count())" icon="user-minus" />
    </div>

    {{-- Daily trend: area and line, axis ticks, hover for the exact day. --}}
    <x-ui.panel :title="__('activity.report.daily')">
        <div class="p-4">
            <div class="flex gap-2" dir="ltr">
                <div class="flex h-40 flex-col justify-between text-end text-xs tabular-nums {{ $muted }}">
                    <span>{{ $dMax }}</span><span>{{ round($dMax / 2) }}</span><span>0</span>
                </div>
                <div class="relative h-40 flex-1">
                    <svg viewBox="0 0 600 100" preserveAspectRatio="none" class="absolute inset-0 h-full w-full text-secondary-dark dark:text-ink" role="img" aria-label="{{ __('activity.report.daily') }}">
                        @foreach ([4, 52, 100] as $y)
                            <line x1="0" y1="{{ $y }}" x2="600" y2="{{ $y }}" stroke="currentColor" stroke-opacity=".12" vector-effect="non-scaling-stroke" @if ($y !== 100) stroke-dasharray="3 4" @endif />
                        @endforeach
                        <polygon points="{{ $area }}" fill="currentColor" fill-opacity=".08" />
                        <polyline points="{{ $points }}" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round" vector-effect="non-scaling-stroke" />
                    </svg>
                    <div class="absolute inset-0 flex">
                        @foreach ($daily as $day)
                            <div class="group relative h-full flex-1">
                                <div class="absolute inset-y-0 start-1/2 hidden w-px bg-secondary-dark/30 group-hover:block dark:bg-ink/30"></div>
                                <div class="pointer-events-none absolute -top-1 start-1/2 z-10 hidden -translate-x-1/2 -translate-y-full whitespace-nowrap rounded-md bg-secondary-dark px-2 py-1 text-xs text-white group-hover:block dark:bg-ink dark:text-surface-0">
                                    <span class="tabular-nums">{{ $day['date'] }}</span> · <b class="tabular-nums">{{ $day['actions'] }}</b>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
            <div class="mt-1 flex justify-between ps-8 text-xs tabular-nums {{ $muted }}" dir="ltr">
                <span>{{ $daily->first()['date'] }}</span>
                <span>{{ $daily->last()['date'] }}</span>
            </div>
        </div>
    </x-ui.panel>

    <div class="grid gap-4 @lg:grid-cols-2">
        <x-ui.panel :title="__('activity.report.top_users')">
            <div class="grid gap-3 p-4">
                @forelse ($r['topUsers'] as $i => $row)
                    <div>
                        <div class="flex items-baseline justify-between gap-2">
                            <span class="truncate" dir="auto"><span class="tabular-nums {{ $muted }}">{{ $i + 1 }}.</span> {{ $row['name'] }}</span>
                            <span class="font-semibold tabular-nums">{{ number_format($row['actions']) }}</span>
                        </div>
                        <div class="mt-1 h-2 rounded-full bg-secondary-soft dark:bg-surface-2">
                            <div class="h-2 rounded-full {{ $bar }}" style="width: {{ round($row['actions'] / $uMax * 100) }}%"></div>
                        </div>
                        @if ($row['last'])
                            <div class="mt-0.5 text-xs {{ $muted }}">{{ __('activity.report.last_action') }}: {{ \Illuminate\Support\Carbon::parse($row['last'])->diffForHumans() }}</div>
                        @endif
                    </div>
                @empty
                    <p class="text-sm {{ $muted }}">{{ __('activity.report.none') }}</p>
                @endforelse
            </div>
        </x-ui.panel>

        <x-ui.panel :title="__('activity.report.cities')">
            <div class="grid gap-3 p-4">
                @forelse ($r['cities'] as $city)
                    <div>
                        <div class="flex items-baseline justify-between gap-2">
                            <span class="truncate" dir="auto">{{ $city['name'] }}</span>
                            <span class="text-sm tabular-nums {{ $muted }}">
                                {{ __('activity.report.users_count', ['n' => $city['users']]) }} ·
                                <b class="font-semibold text-secondary-dark dark:text-ink">{{ number_format($city['actions']) }}</b>
                                ({{ round($city['actions'] / $cityTotal * 100) }}%)
                            </span>
                        </div>
                        <div class="mt-1 h-2 rounded-full bg-secondary-soft dark:bg-surface-2">
                            <div class="h-2 rounded-full {{ $bar }}" style="width: {{ round($city['actions'] / $cMax * 100) }}%"></div>
                        </div>
                    </div>
                @empty
                    <p class="text-sm {{ $muted }}">{{ __('activity.report.none') }}</p>
                @endforelse
            </div>
        </x-ui.panel>
    </div>

    {{-- Hours: the busiest hour is solid, the rest softer; hover shows the count. --}}
    <x-ui.panel :title="__('activity.report.hours')">
        <div class="p-4">
            <div class="flex gap-2" dir="ltr">
                <div class="flex h-36 flex-col justify-between text-end text-xs tabular-nums {{ $muted }}">
                    <span>{{ $hMax }}</span><span>0</span>
                </div>
                <div class="flex h-36 flex-1 items-end gap-0.5 border-b border-panel-border">
                    @foreach ($hours as $hour)
                        @php
                            $isPeak = $peakHour && $hour['hour'] === $peakHour['hour'] && $hour['actions'] > 0;
                        @endphp
                        <div class="group relative flex h-full flex-1 items-end">
                            <div class="w-full rounded-t {{ $bar }} {{ $isPeak ? '' : 'opacity-35' }} group-hover:opacity-100" style="height: {{ $hour['actions'] ? max(3, round($hour['actions'] / $hMax * 100)) : 0 }}%"></div>
                            <div class="pointer-events-none absolute start-1/2 top-0 z-10 hidden -translate-x-1/2 -translate-y-full whitespace-nowrap rounded-md bg-secondary-dark px-2 py-1 text-xs text-white group-hover:block dark:bg-ink dark:text-surface-0">
                                <span class="tabular-nums">{{ sprintf('%02d:00', $hour['hour']) }}</span> · <b class="tabular-nums">{{ $hour['actions'] }}</b>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="mt-1 flex justify-between ps-8 text-xs tabular-nums {{ $muted }}" dir="ltr">
                @foreach ([0, 3, 6, 9, 12, 15, 18, 21] as $tick)
                    <span>{{ sprintf('%02d', $tick) }}</span>
                @endforeach
                <span>23</span>
            </div>
            @if ($peakHour && $peakHour['actions'] > 0)
                <p class="mt-2 text-sm {{ $muted }}">{{ __('activity.report.peak_hour') }}: <b class="tabular-nums text-secondary-dark dark:text-ink">{{ sprintf('%02d:00', $peakHour['hour']) }}</b> ({{ number_format($peakHour['actions']) }})</p>
            @endif
        </div>
    </x-ui.panel>

    {{-- Folded away: the list can be long. --}}
    <x-ui.panel>
        <details class="group p-4" data-activity-report-inactive>
            <summary class="flex cursor-pointer list-none items-center justify-between gap-3">
                <span class="font-semibold">{{ __('activity.report.inactive') }} <span class="ms-1 tabular-nums {{ $muted }}">({{ $r['inactive']->count() }})</span></span>
                <flux:icon name="chevron-down" class="size-4 transition group-open:rotate-180 {{ $muted }}" />
            </summary>
            <div class="mt-3">
                @forelse ($inactiveShown as $person)
                    <div class="flex items-baseline justify-between gap-2 border-b border-panel-border py-1.5 last:border-0">
                        <span class="truncate" dir="auto">{{ $person['name'] }}</span>
                        <span class="text-xs {{ $muted }}">{{ __('activity.report.last_seen') }}: {{ $person['last_seen'] ? $person['last_seen']->diffForHumans() : __('activity.report.never') }}</span>
                    </div>
                @empty
                    <p class="text-sm {{ $muted }}">{{ __('activity.report.all_active') }}</p>
                @endforelse
                @if ($r['inactive']->count() > $inactiveShown->count())
                    <p class="mt-2 text-xs {{ $muted }}">{{ __('activity.report.showing', ['n' => $inactiveShown->count(), 'total' => $r['inactive']->count()]) }}</p>
                @endif
            </div>
        </details>
    </x-ui.panel>
</div>
