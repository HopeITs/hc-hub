{{--
    What sits between the register bar and the task views: the chips for any
    filter set behind the Filters button, then one toolbar row with the scope
    tabs on the start side and the list options (group, sort, subtasks) on
    the end side. (The view switch, search, Status, Due, Filters and Clear
    live in the x-desk.register frame.) Included by ⚡index.blade.php, whose
    top PHP block defines $hiddenFilterChips and $hiddenFilterLabels.
--}}
    @if ($hiddenFilterChips !== [])
        <div class="dk-pjq-row" data-test="task-active-filter-chips">
            @foreach ($hiddenFilterChips as $filterKey => $filterValue)
                <button
                    type="button"
                    class="dk-chip dk-pjq-set"
                    @if ($filterKey === 'myTasksOnly') wire:click="toggleMyTasksOnly" @else wire:click="$set('{{ $filterKey }}', '')" @endif
                    wire:key="task-active-filter-{{ $filterKey }}"
                    aria-label="{{ __('tasks.remove_filter', ['filter' => $hiddenFilterLabels[$filterKey].': '.$filterValue]) }}"
                    data-test="task-active-filter-{{ $filterKey }}"
                >
                    <span class="dk-pjq-k">{{ $hiddenFilterLabels[$filterKey] }}</span> <bdi>{{ $filterValue }}</bdi> <x-desk.icon name="x" />
                </button>
            @endforeach
        </div>
    @endif

    @if (! auth()->user()->can('tasks.update') && ! auth()->user()->can('create', App\Models\Task::class))
        <p class="flex items-start gap-2 rounded-xl bg-secondary-soft/40 px-3 py-2 text-xs text-secondary-dark dark:bg-white/[0.04] dark:text-ink-muted" data-test="task-view-only-notice">
            <flux:icon.eye variant="micro" class="mt-0.5 size-3.5 shrink-0" aria-hidden="true" />
            {{ __('tasks.view_only_notice') }}
        </p>
    @endif

    <div class="dk-tk-toolbar">
        {{-- Whose tasks: one click to the viewer's own work, or what waits for their review. --}}
        <nav class="dk-tk-tabs" aria-label="{{ __('tasks.tabs_label') }}" data-test="task-scope-tabs">
            @foreach (['' => __('tasks.tab_all'), 'mine' => __('tasks.tab_mine'), 'assigned' => __('tasks.tab_assigned'), 'review' => __('tasks.tab_review')] as $scopeKey => $scopeLabel)
                <button
                    type="button"
                    wire:click="setScope('{{ $scopeKey }}')"
                    aria-current="{{ $scope === $scopeKey ? 'page' : 'false' }}"
                    data-test="task-scope-{{ $scopeKey === '' ? 'all' : $scopeKey }}"
                >
                    {{ $scopeLabel }}
                    @if ($scopeKey === 'review' && $this->tasksAwaitingMyReview > 0)
                        <span class="dk-num dk-tk-tab-count" data-test="task-scope-review-count">{{ $this->tasksAwaitingMyReview }}</span>
                    @endif
                </button>
            @endforeach
        </nav>

        @if ($view === 'list' || $view === 'board')
            <div class="dk-tk-opts" role="group" aria-label="{{ __('tasks.task_toolbar') }}">
                @if ($view === 'list')
                    <x-ui.header-filter-select :label="__('tasks.group_by')" wire:model.live="group" :value="$group" data-test="task-group-select">
                        <option value="none">{{ __('tasks.group_by') }}: {{ __('tasks.no_grouping') }}</option>
                        <option value="status">{{ __('tasks.group_by') }}: {{ __('common.status') }}</option>
                        <option value="priority">{{ __('tasks.group_by') }}: {{ __('tasks.priority') }}</option>
                        <option value="assignee">{{ __('tasks.group_by') }}: {{ __('tasks.assignee') }}</option>
                        <option value="milestone">{{ __('tasks.group_by') }}: {{ __('tasks.milestone') }}</option>
                    </x-ui.header-filter-select>
                    <div class="dk-tk-sort">
                        <x-ui.header-filter-select :label="__('tasks.sort_by')" wire:model.live="sortField" :value="$sortField" data-test="task-sort-select">
                            <option value="due">{{ __('tasks.sort_by') }}: {{ __('tasks.due_date_2') }}</option>
                            <option value="title">{{ __('tasks.sort_by') }}: {{ __('common.name') }}</option>
                            <option value="status">{{ __('tasks.sort_by') }}: {{ __('common.status') }}</option>
                            <option value="priority">{{ __('tasks.sort_by') }}: {{ __('tasks.priority') }}</option>
                            <option value="assignee">{{ __('tasks.sort_by') }}: {{ __('tasks.assignee') }}</option>
                            <option value="created">{{ __('tasks.sort_by') }}: {{ __('tasks.created_date') }}</option>
                        </x-ui.header-filter-select>
                        <button
                            type="button"
                            class="dk-btn dk-tk-icon-btn"
                            wire:click="$set('sortDirection', '{{ $sortDirection === 'asc' ? 'desc' : 'asc' }}')"
                            aria-label="{{ $sortDirection === 'asc' ? __('tasks.sort_ascending') : __('tasks.sort_descending') }}"
                            title="{{ $sortDirection === 'asc' ? __('tasks.sort_ascending') : __('tasks.sort_descending') }}"
                            data-test="task-sort-direction-toggle"
                        >
                            <flux:icon :name="$sortDirection === 'asc' ? 'bars-arrow-up' : 'bars-arrow-down'" variant="micro" class="size-4" aria-hidden="true" />
                        </button>
                    </div>
                @endif

                {{-- Opens or closes every task's subtasks at once; remembered per browser (task-subtasks.js). --}}
                <button
                    type="button"
                    x-data
                    x-on:click="$store.taskSubtasks.toggleAll()"
                    x-bind:aria-pressed="$store.taskSubtasks.allOpen ? 'true' : 'false'"
                    class="dk-btn"
                    data-test="task-subtasks-toggle-all"
                >
                    <flux:icon.rectangle-stack variant="micro" class="size-4" aria-hidden="true" />
                    <span x-text="$store.taskSubtasks.allOpen ? {{ Illuminate\Support\Js::from(__('tasks.hide_subtasks')) }} : {{ Illuminate\Support\Js::from(__('tasks.show_subtasks')) }}">{{ __('tasks.show_subtasks') }}</span>
                </button>
            </div>
        @endif
    </div>
