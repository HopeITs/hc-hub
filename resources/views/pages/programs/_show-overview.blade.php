{{--
    The program page's Overview tab, built to be read top-down in one glance:

    1. "Needs your attention": the first few items, the rest one click away.
    2. The projects as a card grid in two folding groups, "Needs attention"
       (open) and "On track" (folded once anything needs attention). Each card
       shows pace and three key facts; targets and money fold under "Details".
    3. Program settings, folded.

    Folding is client-side (Alpine, remembered per browser with $persist) and
    the name search runs in the browser, so neither costs a round trip.
    Report, target and money facts render only when the trait read them
    (TracksProgramHealth skips each query without its permission).
--}}
@php
    use App\Enums\ProjectStatus;
    use App\Support\ProjectPace;

    $ledger = $this->projectLedger;
    $attention = $this->attention;
    $showReports = auth()->user()?->can('reports.view') === true;
    $showTargets = auth()->user()?->can('targets.view') === true;
    $showMoney = $this->canViewProgramMoney();
    $reportMonth = \Carbon\CarbonImmutable::today()->subMonthNoOverflow()->translatedFormat('F');
    $attentionPreview = 4;

    $paceLabel = fn (string $pace, \App\Models\Project $project): string => match ($pace) {
        'closed' => $project->status->label(),
        'past_end' => __('programs.pace_past_end'),
        'not_started' => __('programs.pace_not_started'),
        'unmeasured' => __('programs.pace_unmeasured'),
        'complete' => __('programs.pace_complete'),
        'no_window' => __('programs.pace_no_window'),
        'on_track' => __('programs.pace_on_track'),
        'slipping' => __('programs.pace_slipping'),
        default => __('programs.pace_behind'),
    };
    $paceTone = fn (string $pace): string => match (true) {
        in_array($pace, ProjectPace::CRITICAL, true) => 'dk-bad',
        in_array($pace, ProjectPace::WARNING, true) => 'dk-warn',
        in_array($pace, ['on_track', 'complete'], true) => 'dk-good',
        default => 'dk-mute',
    };
    [$flagged, $steady] = collect($ledger)->partition(fn (array $row): bool => $row['needsAttention']);
    $groups = [
        ['key' => 'attention', 'label' => __('programs.group_needs_attention'), 'rows' => $flagged->values()->all(), 'open' => true],
        ['key' => 'steady', 'label' => __('programs.group_steady'), 'rows' => $steady->values()->all(), 'open' => $flagged->isEmpty()],
    ];
@endphp

<div class="dk-prog-ov" x-data="{ query: '' }">
    {{-- 1. Needs your attention --}}
    <section class="dk-prog-fold" x-data="{ open: $persist(true).as('program-attention-open'), all: false }" :class="{ 'dk-prog-fold-on': open }" data-test="program-attention">
        <button type="button" class="dk-prog-fold-h" @click="open = ! open" :aria-expanded="open.toString()" aria-controls="program-attention-body">
            <h2 id="program-attention-heading">{{ __('programs.attention_heading') }}</h2>
            @if ($attention !== [])
                <span @class(['dk-prog-count', 'dk-bad' => in_array('critical', array_column($attention, 'tone'), true)])>{{ count($attention) }}</span>
            @endif
            <x-desk.icon name="chev" class="dk-prog-fold-i" />
        </button>

        <div id="program-attention-body" class="dk-prog-fold-b" x-show="open" x-collapse>
            @if ($attention === [])
                <p class="dk-prog-clear"><x-desk.icon name="check" />{{ $ledger === [] ? __('programs.attention_no_projects') : __('programs.attention_none') }}</p>
            @else
                <ul class="dk-prog-attn">
                    @foreach ($attention as $item)
                        <li wire:key="program-attention-{{ $loop->index }}" @class(['dk-prog-attn-i', 'dk-bad' => $item['tone'] === 'critical', 'dk-warn' => $item['tone'] !== 'critical']) data-attention="{{ $item['key'] }}"
                            @if ($loop->index >= $attentionPreview) x-show="all" x-cloak @endif>
                            <span class="dk-prog-attn-t">
                                {{ $item['text'] }}
                                @if ($item['mine'])
                                    <span class="dk-prog-mine">{{ __('programs.attention_yours') }}</span>
                                @endif
                            </span>
                            <a href="{{ $item['href'] }}" wire:navigate class="dk-attn-a">{{ $item['label'] }}<x-desk.icon name="arrow" class="dk-flip" /></a>
                        </li>
                    @endforeach
                </ul>
                @if (count($attention) > $attentionPreview)
                    <button type="button" class="dk-prog-more" @click="all = ! all" :aria-expanded="all.toString()">
                        <span x-show="! all">{{ trans_choice('programs.attention_show_more', count($attention) - $attentionPreview, ['count' => count($attention) - $attentionPreview]) }}</span>
                        <span x-show="all" x-cloak>{{ __('programs.attention_show_less') }}</span>
                    </button>
                @endif
            @endif
        </div>
    </section>

    {{-- 2. The projects --}}
    <section class="dk-prog-projects" aria-labelledby="program-ledger-heading" data-test="program-ledger">
        <div class="dk-prog-projects-h">
            <h2 id="program-ledger-heading">{{ __('programs.ledger_heading') }} <span class="dk-num">{{ count($ledger) }}</span></h2>
            @if (count($ledger) > 3)
                <label class="dk-prog-search">
                    <span class="sr-only">{{ __('programs.find_a_project') }}</span>
                    <x-desk.icon name="search" />
                    <input type="search" x-model="query" placeholder="{{ __('programs.find_a_project') }}" data-test="program-ledger-search" />
                </label>
            @endif
        </div>

        @if ($ledger === [])
            <p class="dk-prog-clear">{{ __('common.no_projects_yet') }}</p>
        @endif

        @foreach ($groups as $group)
            @continue($group['rows'] === [])
            <div class="dk-prog-group" x-data="{ open: $persist({{ $group['open'] ? 'true' : 'false' }}).as('program-group-{{ $group['key'] }}') }" data-ledger-group="{{ $group['key'] }}" x-effect="if (query !== '') { open = true }">
                <button type="button" class="dk-prog-group-h" @click="open = ! open" :aria-expanded="open.toString()">
                    <span @class(['dk-prog-group-dot', 'dk-warn' => $group['key'] === 'attention', 'dk-good' => $group['key'] !== 'attention'])></span>
                    <span>{{ $group['label'] }}</span>
                    <span class="dk-num">{{ count($group['rows']) }}</span>
                    <span class="dk-prog-chev" :class="{ 'dk-prog-rot': open }"><x-desk.icon name="chev" /></span>
                </button>

                <ul class="dk-prog-cards" x-show="open" x-collapse>
                    @foreach ($group['rows'] as $row)
                        @php
                            $project = $row['project'];
                            $isActive = $project->status === ProjectStatus::Active;
                        @endphp
                        <li class="dk-prog-card" wire:key="program-card-{{ $project->id }}" data-ledger-row
                            x-data="{ details: false }"
                            x-show="query === '' || {{ \Illuminate\Support\Js::from(mb_strtolower($project->name)) }}.includes(query.trim().toLowerCase())">

                            <div class="dk-prog-card-h">
                                <div class="min-w-0">
                                    <h3 class="dk-prog-card-t">{{ $project->name }}</h3>
                                    <p class="dk-prog-card-o"><x-desk.icon name="users" />{{ $project->user?->name ?? __('common.not_assigned') }}</p>
                                </div>
                                <x-ui.button tone="ghost" size="sm" icon="eye" :href="route('projects.show', $project)" wire:navigate :aria-label="__('common.view_name', ['name' => $project->name])" />
                            </div>

                            <div class="dk-prog-card-pace">
                                <div class="dk-prog-card-row">
                                    @if ($row['pace'] !== 'closed')
                                        <span class="dk-prog-pill {{ $paceTone($row['pace']) }}">{{ $paceLabel($row['pace'], $project) }}</span>
                                    @endif
                                    <x-ui.enum-badge :case="$project->status" />
                                    <span class="dk-prog-card-pct dk-num">{{ $row['progress'] === null ? '—' : $row['progress'].'%' }}</span>
                                </div>
                                <span class="dk-prog-bar" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-label="{{ __('programs.milestone_progress') }}" @if ($row['progress'] !== null) aria-valuenow="{{ $row['progress'] }}" @endif>
                                    <i style="width: {{ $row['progress'] ?? 0 }}%"></i>
                                    @if ($row['elapsed'] !== null && $isActive)
                                        <b style="inset-inline-start: {{ round($row['elapsed'], 1) }}%"></b>
                                    @endif
                                </span>
                                @if ($row['elapsed'] !== null && $row['progress'] !== null && $isActive)
                                    <span class="dk-prog-note">{{ __('programs.pace_expected', ['percent' => (int) round($row['elapsed'])]) }}</span>
                                @endif
                            </div>

                            {{-- The three facts that most often need a hand --}}
                            <dl class="dk-prog-facts">
                                <div @class(['dk-bad' => $row['lateMilestones'] > 0])>
                                    <dt><x-desk.icon name="flag" />{{ __('programs.ledger_milestones') }}</dt>
                                    <dd>{{ $row['lateMilestones'] > 0 ? trans_choice('programs.ledger_late_milestones', $row['lateMilestones'], ['count' => $row['lateMilestones']]) : ($row['nextMilestone'] === null ? __('programs.ledger_no_next_milestone') : __('programs.ledger_next_on', ['date' => $row['nextMilestone']->translatedFormat(__('formats.date'))])) }}</dd>
                                </div>
                                <div @class(['dk-bad' => $row['pastDueTasks'] > 0])>
                                    <dt><x-desk.icon name="check" />{{ __('common.tasks') }}</dt>
                                    <dd>{{ $row['pastDueTasks'] > 0 ? trans_choice('programs.ledger_past_due', $row['pastDueTasks'], ['count' => $row['pastDueTasks']]) : trans_choice('programs.ledger_open_tasks', $row['openTasks'], ['count' => $row['openTasks']]) }}</dd>
                                </div>
                                @if ($showReports)
                                    <div @class(['dk-bad' => $row['reportExpected'] && $row['report'] === null]) data-ledger-report>
                                        <dt><x-desk.icon name="file" />{{ __('programs.ledger_report', ['month' => $reportMonth]) }}</dt>
                                        <dd>
                                            @if ($row['report'] !== null)
                                                <x-ui.enum-badge :case="$row['report']" />
                                            @elseif ($row['reportExpected'])
                                                {{ __('programs.ledger_report_missing') }}
                                            @else
                                                <span class="dk-muted">{{ __('programs.ledger_report_not_due') }}</span>
                                            @endif
                                        </dd>
                                    </div>
                                @endif
                            </dl>

                            {{-- Details: targets and money, folded --}}
                            @if ($showTargets || $showMoney)
                                <button type="button" class="dk-prog-details-t" @click="details = ! details" :aria-expanded="details.toString()">
                                    <span x-text="details ? {{ \Illuminate\Support\Js::from(__('programs.details_hide')) }} : {{ \Illuminate\Support\Js::from(__('programs.details_show')) }}">{{ __('programs.details_show') }}</span>
                                    <span class="dk-prog-chev" :class="{ 'dk-prog-rot': details }"><x-desk.icon name="chev" /></span>
                                </button>
                                <dl class="dk-prog-facts dk-prog-details" x-show="details" x-collapse x-cloak>
                                    @if ($showTargets)
                                        <div data-ledger-targets>
                                            <dt><x-desk.icon name="chart" />{{ __('programs.ledger_targets') }}</dt>
                                            <dd>
                                                @if (($row['targets']['total'] ?? 0) === 0)
                                                    <span class="dk-muted">{{ __('programs.ledger_no_targets') }}</span>
                                                @else
                                                    {{ __('programs.ledger_targets_reached', ['reached' => $row['targets']['reached'], 'total' => $row['targets']['total']]) }}
                                                    @if ($row['targets']['percent'] !== null)
                                                        <span class="dk-prog-note">· {{ __('programs.ledger_targets_average', ['percent' => number_format($row['targets']['percent'], 0)]) }}</span>
                                                    @endif
                                                @endif
                                            </dd>
                                        </div>
                                    @endif
                                    @if ($showMoney)
                                        <div @class(['dk-bad' => $row['lateTransfers'] > 0]) data-ledger-funding>
                                            <dt><x-desk.icon name="coins" />{{ __('programs.ledger_funding') }}</dt>
                                            <dd>
                                                @forelse ($row['funding'] ?? [] as $money)
                                                    <span class="block"><x-ui.money :amount="$money['received']" :currency="$money['currency']" /> <span class="dk-prog-note">{{ __('programs.figure_of_committed', ['amount' => number_format($money['committed'], 2), 'currency' => $money['currency']]) }}</span></span>
                                                @empty
                                                    <span class="dk-muted">{{ __('projects.not_funded_yet') }}</span>
                                                @endforelse
                                                @if ($row['lateTransfers'] > 0)
                                                    <span class="block">{{ trans_choice('programs.ledger_late_transfers', $row['lateTransfers'], ['count' => $row['lateTransfers']]) }}</span>
                                                @endif
                                            </dd>
                                        </div>
                                    @endif
                                </dl>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>
        @endforeach

        <p class="dk-prog-clear" x-cloak x-show="query !== '' && ! [...$root.querySelectorAll('[data-ledger-row]')].some((row) => row.textContent.toLowerCase().includes(query.trim().toLowerCase()))">{{ __('programs.ledger_nothing_matches') }}</p>
    </section>

    {{-- 3. Program settings, folded --}}
    <section class="dk-prog-fold" x-data="{ open: $persist(false).as('program-settings-open') }" :class="{ 'dk-prog-fold-on': open }" data-program-donor-attribution>
        <button type="button" class="dk-prog-fold-h" @click="open = ! open" :aria-expanded="open.toString()" aria-controls="program-settings-body">
            <h2>{{ __('programs.settings_heading') }}</h2>
            <span class="dk-prog-fold-hint">{{ __('programs.donor_attribution') }}: {{ $this->program->donor_attribution->label() }}</span>
            <x-desk.icon name="chev" class="dk-prog-fold-i" />
        </button>
        <div id="program-settings-body" class="dk-prog-fold-b" x-show="open" x-collapse x-cloak>
            <div class="dk-prog-setting">
                <div class="min-w-0">
                    <label for="program-donor-attribution" class="dk-prog-setting-l">{{ __('programs.donor_attribution') }}</label>
                    <p class="dk-small dk-muted">{{ __('programs.donor_attribution_help') }}</p>
                </div>
                @can('report-templates.manage')
                    <div class="w-full max-w-72">
                        <flux:select id="program-donor-attribution" size="sm" wire:change="setDonorAttribution($event.target.value)" wire:loading.attr="disabled" wire:target="setDonorAttribution">
                            @foreach (\App\Enums\DonorAttribution::cases() as $attribution)
                                <flux:select.option value="{{ $attribution->value }}" :selected="$this->program->donor_attribution === $attribution">{{ $attribution->label() }}</flux:select.option>
                            @endforeach
                        </flux:select>
                    </div>
                @endcan
            </div>
        </div>
    </section>
</div>
