{{-- "My reports" on ⚡index.blade.php: the months waiting on the preparer first ("Needs you"), then one block per project: its name and month strip, what holds the whole project back (said once), its months that still need something, and the validated ones folded away. --}}
@if ($this->myMonths === [])
    <x-ui.empty-state icon="calendar-days" :heading="__('monthly_reports.empty_mine')" :body="__('monthly_reports.empty_mine_body')" />
@else
    <div class="flex flex-col gap-10">
        @if ($this->needsYou !== [])
            <section class="flex flex-col gap-3" aria-labelledby="needs-you-heading" data-needs-you>
                <h2 id="needs-you-heading" class="text-lg font-bold">{{ __('monthly_reports.needs_you') }}</h2>

                <x-ui.panel>
                    <ul class="flex flex-col divide-y divide-secondary-soft dark:divide-line" role="list">
                        @foreach ($this->needsYou as $item)
                            @include('pages.monthly-reports._index-month-row', ['row' => $item['row'], 'rowProject' => $item['project'], 'inThread' => false, 'projectHasIndicators' => true])
                        @endforeach
                    </ul>
                </x-ui.panel>
            </section>
        @endif

        @foreach ($this->visibleGroups as $group)
            @php
                $groupProject = $group['project'];
                $stripLabel = collect($group['strip'])
                    ->map(fn (array $row): string => $row['report']->period->translatedFormat('F Y').': '.($row['overdue'] ? __('monthly_reports.overdue') : __('monthly_reports.states.'.$row['state'])))
                    ->implode(', ');
            @endphp
            <section class="flex flex-col gap-3" aria-labelledby="project-months-{{ $groupProject->id }}" wire:key="project-months-{{ $groupProject->id }}">
                <div class="flex flex-wrap items-center justify-between gap-x-4 gap-y-2">
                    <h2 id="project-months-{{ $groupProject->id }}" class="text-lg font-bold" dir="auto">
                        @can('projects.view')
                            <a href="{{ route('projects.show', $groupProject) }}" wire:navigate class="hover:underline">{{ $groupProject->name }}</a>
                        @else
                            {{ $groupProject->name }}
                        @endcan
                    </h2>

                    {{-- The project's year at a glance: one square per generated month, coloured by its state. --}}
                    <div class="flex flex-wrap items-center gap-1" role="img" aria-label="{{ __('monthly_reports.month_strip_label', ['months' => $stripLabel]) }}" data-month-strip="{{ $groupProject->id }}">
                        @foreach ($group['strip'] as $stripRow)
                            @php
                                $stripState = $stripRow['overdue'] ? 'overdue' : $stripRow['state'];
                            @endphp
                            <span
                                @class([
                                    'size-3.5 rounded-[4px]',
                                    'bg-status-good' => $stripState === 'validated',
                                    'bg-secondary-dark dark:bg-ink' => $stripState === 'submitted',
                                    'bg-status-warn' => in_array($stripState, ['returned', 'reopen_pending'], true),
                                    'bg-primary' => $stripState === 'overdue',
                                    'border-2 border-secondary-light dark:border-line-strong' => in_array($stripState, ['draft', 'upcoming'], true),
                                ])
                                data-strip-month="{{ $stripRow['key'] }}" data-strip-state="{{ $stripState }}"
                                title="{{ $stripRow['report']->period->translatedFormat('F Y') }}"
                            ></span>
                        @endforeach
                    </div>
                </div>

                @if ($group['notice'] !== null)
                    <p class="flex flex-wrap items-center gap-x-3 gap-y-1 rounded-lg bg-status-warn-soft px-3 py-2 text-sm text-secondary-dark dark:text-ink" data-project-notice="{{ $groupProject->id }}">
                        <span class="inline-flex items-center gap-1.5">
                            <flux:icon.exclamation-triangle variant="micro" class="size-4 shrink-0 text-status-warn" aria-hidden="true" />
                            {{ $group['notice'] }}
                        </span>
                        @can('projects.view')
                            <a href="{{ route('projects.show', $groupProject) }}" wire:navigate class="font-semibold underline underline-offset-2">{{ __('monthly_reports.open_project') }}</a>
                        @endcan
                    </p>
                @elseif (! $group['hasIndicators'])
                    <p class="type-meta text-secondary-medium dark:text-ink-muted">{{ __('monthly_reports.project_no_indicators') }}</p>
                @endif

                @if ($group['folded'] !== [])
                    <details class="group" data-validated-fold="{{ $groupProject->id }}">
                        <summary class="inline-flex cursor-pointer list-none items-center gap-1.5 text-sm font-semibold text-secondary-medium hover:text-secondary-dark dark:text-ink-muted dark:hover:text-ink">
                            <flux:icon.chevron-right variant="micro" class="size-4 transition-transform group-open:rotate-90 rtl:-scale-x-100" aria-hidden="true" />
                            {{ trans_choice('monthly_reports.validated_months', count($group['folded']), ['count' => count($group['folded'])]) }}
                        </summary>
                        <ol class="relative mt-1 flex flex-col before:absolute before:inset-y-3 before:start-[5px] before:w-px before:bg-secondary-soft dark:before:bg-line" role="list">
                            @foreach ($group['folded'] as $row)
                                @include('pages.monthly-reports._index-month-row', ['row' => $row, 'rowProject' => $groupProject, 'inThread' => true, 'projectHasIndicators' => $group['hasIndicators']])
                            @endforeach
                        </ol>
                    </details>
                @endif

                @if ($group['open'] !== [])
                    <ol class="relative flex flex-col before:absolute before:inset-y-3 before:start-[5px] before:w-px before:bg-secondary-soft dark:before:bg-line" role="list">
                        @foreach ($group['open'] as $row)
                            @include('pages.monthly-reports._index-month-row', ['row' => $row, 'rowProject' => $groupProject, 'inThread' => true, 'projectHasIndicators' => $group['hasIndicators']])
                        @endforeach
                    </ol>
                @endif
            </section>
        @endforeach
    </div>
@endif
